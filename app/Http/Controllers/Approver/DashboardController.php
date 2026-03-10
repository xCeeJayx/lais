<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Now the editor knows this is a Model, so loadMissing works
        $user->loadMissing(['roles', 'employee']);

        $officeId = $user->employee?->office_id;

        // Ensure roleKeys() exists in your User model, otherwise use: $user->roles->pluck('key')->toArray()
        $roleKeys = method_exists($user, 'roleKeys') ? $user->roleKeys() : $user->roles->pluck('key')->toArray();

        // ---------------------------------------------------------
        // 1. Calculate PENDING (Match Inbox Logic exactly)
        // ---------------------------------------------------------
        $pendingCount = 0;

        if ($officeId) {
            // Find which steps belong to this user
            $myStepOrders = ApprovalStep::where('office_id', $officeId)
                ->whereIn('role_key', $roleKeys)
                ->pluck('step_order')
                ->all();

            if (!empty($myStepOrders)) {
                $query = LeaveApplication::query()
                    ->where('office_id', $officeId)
                    ->where('status', 'pending')
                    ->whereIn('current_step_order', $myStepOrders);

                // Apply Division Chief restriction (Step 1 = Own Division only)
                if (in_array(1, $myStepOrders, true) && $user->hasRole('approver_division_chief')) {
                    $divisionId = $user->employee?->division_id;
                    $query->whereHas('employee', fn($q) => $q->where('division_id', $divisionId));
                }

                $pendingCount = $query->count();
            }
        }

        // ---------------------------------------------------------
        // 2. Calculate PROCESSED (Count your specific actions)
        // ---------------------------------------------------------
        $processedCount = LeaveApproval::where('approver_user_id', $user->id)
            ->whereIn('action', ['approved', 'disapproved', 'returned'])
            ->count();

        $stats = [
            'pending' => $pendingCount,
            'processed' => $processedCount,
            'male'   => \App\Models\Employee::where('sex', 'M')->count(),
            'female' => \App\Models\Employee::where('sex', 'F')->count(),
        ];

        return view('approver.dashboard', compact('stats'));
    }
}
