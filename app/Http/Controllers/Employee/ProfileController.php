<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display the logged-in employee's information.
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->load(['employee.office', 'employee.division']);
        $employee = $user->employee;

        return view('employee.profile', compact('user', 'employee'));
    }
}
