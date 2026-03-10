<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalStep, LeaveApplication, Division};
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('roles', 'employee');

        // Use standard Laravel pluck to safely get the role keys
        $roleKeys = $user->roles->pluck('key')->toArray();

        $officeId = $user->employee?->office_id;
        if (!$officeId) abort(403, 'No office assigned to your account.');

        // 1. Determine which steps this user handles
        $myStepOrders = ApprovalStep::where('office_id', $officeId)
            ->whereIn('role_key', $roleKeys)
            ->pluck('step_order')
            ->toArray();

        if (empty($myStepOrders)) {
            return view('approver.inbox', [
                'leaves' => collect(),
                'divisions' => Division::where('office_id', $officeId)->get()
            ]);
        }

        // 2. Start Query
        $query = LeaveApplication::query()
            ->with(['employee.user', 'employee.division', 'leaveType'])
            ->where('office_id', $officeId)
            ->where('status', 'pending');

        // 3. FIXED: Advanced Step & Division Restriction
        $divisionId = $user->employee?->division_id;

        $query->where(function ($q) use ($myStepOrders, $user, $divisionId) {
            foreach ($myStepOrders as $step) {
                // If this is Step 1 and the user is the Division Chief, lock it to their division
                if ($step == 1 && $user->hasRole('approver_division_chief')) {
                    $q->orWhere(function ($subQuery) use ($step, $divisionId) {
                        $subQuery->where('current_step_order', $step)
                                 ->whereHas('employee', fn($empQuery) => $empQuery->where('division_id', $divisionId));
                    });
                } else {
                    // For all other steps (Personnel, ARD), they can see all divisions in the office
                    $q->orWhere('current_step_order', $step);
                }
            }
        });

        // 4. Filter by Division (From Dropdown)
        if ($request->filled('division_id')) {
            $query->whereHas('employee', fn($q) =>
                $q->where('division_id', $request->division_id)
            );
        }

        // 5. Filter by Date
        if ($request->filled('date_from')) {
            $query->whereDate('date_filed', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_filed', '<=', $request->date_to);
        }

        $leaves = $query->latest()->paginate(15)->withQueryString();
        $divisions = Division::where('office_id', $officeId)->get();

        return view('approver.inbox', compact('leaves', 'divisions'));
    }
}
