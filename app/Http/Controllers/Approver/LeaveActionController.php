<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalStep, LeaveApplication, LeaveApproval, LeaveCredit, Role};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveStatusUpdated;

class LeaveActionController extends Controller
{
    public function show(Request $request, int $id)
    {
        $leave = LeaveApplication::with([
            'employee.user','employee.division','leaveType','office',
            'approvals.approver',
            'attachments'
        ])->findOrFail($id);

        // 1. Fetch Credits & History
        $credits = LeaveCredit::where('employee_id', $leave->employee_id)->first();

        $history = LeaveApplication::with('leaveType')
            ->where('employee_id', $leave->employee_id)
            ->where('id', '!=', $id)
            ->latest('date_filed')
            ->take(5)
            ->get();

        // 2. Build Timeline
        $steps = ApprovalStep::where('office_id', $leave->office_id)->orderBy('step_order')->get();

        // Fetch readable Role Names from 'roles' table
        $roleNames = Role::whereIn('key', $steps->pluck('role_key'))
                        ->pluck('name', 'key');

        $approvalsByStep = $leave->approvals->keyBy('step_order');

        $timeline = $steps->map(function ($step) use ($leave, $approvalsByStep, $roleNames) {
            $ap = $approvalsByStep->get($step->step_order);

            $title = $roleNames[$step->role_key] ?? $step->name ?? $step->role_key;

            if ($ap) {
                return [
                    'step_order' => $step->step_order,
                    'role_key'   => $step->role_key,
                    'title'      => $title,
                    'state'      => $ap->action,
                    'remarks'    => $ap->remarks,
                    'actor'      => $ap->approver->first_name . ' ' . $ap->approver->last_name,
                    'acted_at'   => $ap->acted_at ?? $ap->created_at,
                ];
            }

            $isCurrent = ((int)$leave->current_step_order === (int)$step->step_order);

            return [
                'step_order' => $step->step_order,
                'role_key'   => $step->role_key,
                'title'      => $title,
                'state'      => $isCurrent ? 'current' : 'upcoming',
                'remarks'    => null,
                'actor'      => null,
                'acted_at'   => null,
            ];
        });

        // 3. CHECK PERMISSION
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->loadMissing('roles', 'employee');

        $canAction = false;

        if ($leave->status === 'pending' && $user->employee?->office_id === $leave->office_id) {
            $currentStepDef = $steps->firstWhere('step_order', $leave->current_step_order);

            if ($currentStepDef && $user->hasRole($currentStepDef->role_key)) {
                if ($currentStepDef->step_order === 1 && $user->hasRole('approver_division_chief')) {
                    if ($leave->employee->division_id === $user->employee->division_id) {
                        $canAction = true;
                    }
                } else {
                    $canAction = true;
                }
            }
        }

        return view('approver.review', compact('leave', 'timeline', 'credits', 'history', 'canAction'));
    }

    public function action(Request $request, int $id)
    {
        $request->validate([
            'action' => 'required|in:approved,returned,disapproved',
            'remarks' => 'nullable|string|max:2000',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->loadMissing('roles', 'employee');

        $leave = LeaveApplication::with('employee.user')->lockForUpdate()->findOrFail($id);

        $this->authorizeAction($user, $leave);

        $action = $request->input('action');
        $remarks = $request->input('remarks');

        if (in_array($action, ['returned', 'disapproved'], true) && blank($remarks)) {
            return back()->withErrors(['remarks' => 'Remarks are required when returning or disapproving.']);
        }

        DB::transaction(function () use ($request, $leave, $user, $action, $remarks) {

            // NEW: If Chief Personnel, save the Leave Credits Certification Details
            if ($user->hasRole('approver_chief_personnel')) {
                $details = $leave->details_json ?? [];

                $fieldsToSave = [
                    'credits_as_of',
                    'vl_earned', 'vl_less', 'vl_balance',
                    'sl_earned', 'sl_less', 'sl_balance'
                ];

                foreach($fieldsToSave as $field) {
                    if ($request->has($field)) {
                        $details[$field] = $request->input($field);
                    }
                }

                $leave->details_json = $details;;
            }

            // 1. Log the Approval
            LeaveApproval::create([
                'leave_application_id' => $leave->id,
                'step_order' => $leave->current_step_order,
                'approver_user_id' => $user->id,
                'action' => $action,
                'remarks' => $remarks,
                'acted_at' => Carbon::now(),
            ]);

            // 2. Update Leave Status
            $statusChanged = false;

            if ($action === 'approved') {
                $maxStep = ApprovalStep::where('office_id', $leave->office_id)->max('step_order');

                if ($leave->current_step_order < (int)$maxStep) {
                    $leave->current_step_order += 1;
                    $leave->status = 'pending';
                } else {
                    $leave->status = 'approved';
                    $statusChanged = true;
                }
            } elseif ($action === 'returned') {
                $leave->status = 'returned';
                $statusChanged = true;
            } elseif ($action === 'disapproved') {
                $leave->status = 'disapproved';
                $statusChanged = true;
            }

            $leave->save();

            // 3. SEND EMAIL NOTIFICATION
            if ($statusChanged && $leave->employee && $leave->employee->user) {
                try {
                    Mail::to($leave->employee->user->email)->send(
                        new LeaveStatusUpdated($leave, $remarks, $user->first_name . ' ' . $user->last_name)
                    );
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send leave email: ' . $e->getMessage());
                }
            }
        });

        return redirect()->route('approver.inbox')->with('status', 'Application processed successfully.');
    }

    private function authorizeAction(\App\Models\User $user, LeaveApplication $leave): void
    {
        if ($leave->status !== 'pending') abort(403, 'Leave is not pending.');
        if (!$user->employee || $user->employee->office_id !== $leave->office_id) abort(403, 'Office mismatch.');

        $step = ApprovalStep::where('office_id', $leave->office_id)
            ->where('step_order', $leave->current_step_order)
            ->firstOrFail();

        if (!$user->hasRole($step->role_key)) abort(403, 'Not assigned to this step.');

        if ($step->step_order === 1) {
            $leaveDivision = $leave->employee()->value('division_id');
            if ($leaveDivision !== $user->employee->division_id) abort(403, 'Division mismatch.');
        }
    }
}
