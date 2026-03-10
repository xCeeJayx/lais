<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Division;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'office', 'division']);

        // Search Filter
        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Office Filter
        if ($officeId = $request->get('office_id')) {
            $query->where('office_id', $officeId);
        }

        $employees = $query->paginate(10)->withQueryString();
        $offices = Office::all(); // For filter dropdown

        return view('admin.employees.index', compact('employees', 'offices'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $offices = Office::all();
        $divisions = Division::all();
        // Fetch only relevant roles (exclude super_admin for safety)
        $roles = Role::where('key', '!=', 'super_admin')->get();

        return view('admin.employees.create', compact('offices', 'divisions', 'roles'));
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'office_id' => 'required|exists:offices,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_title' => 'required|string|max:255',
            'salary_grade' => 'nullable|integer|min:1|max:33',
            'roles' => 'array' // Array of role IDs
        ]);

        DB::transaction(function () use ($request) {
            // 1. Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Create Employee Profile
            Employee::create([
                'user_id' => $user->id,
                'office_id' => $request->office_id,
                'division_id' => $request->division_id,
                'position_title' => $request->position_title,
                'salary_grade' => $request->salary_grade,
                'status' => 'active',
            ]);

            // 3. Assign Roles (Always add 'employee' role + selected roles)
            $roles = $request->roles ?? [];
            // Ensure 'employee' role is always assigned
            $employeeRole = Role::where('key', 'employee')->first();
            if ($employeeRole && !in_array($employeeRole->id, $roles)) {
                $roles[] = $employeeRole->id;
            }

            $user->roles()->sync($roles);
        });

        return redirect()->route('admin.employees.index')
                         ->with('success', 'Employee created successfully.');
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit($id)
    {
        $employee = Employee::with('user.roles')->findOrFail($id);
        $offices = Office::all();
        $divisions = Division::where('office_id', $employee->office_id)->get(); // Load divisions for selected office
        $allDivisions = Division::all(); // Needed for JS filtering if user changes office
        $roles = Role::where('key', '!=', 'super_admin')->get();

        return view('admin.employees.edit', compact('employee', 'offices', 'divisions', 'allDivisions', 'roles'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $user = $employee->user;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'office_id' => 'required|exists:offices,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_title' => 'required|string|max:255',
            'salary_grade' => 'nullable|integer',
            'status' => 'required|in:active,inactive,suspended',
            'roles' => 'array'
        ]);

        DB::transaction(function () use ($request, $employee, $user) {
            // 1. Update User
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            $user->update($userData);

            // 2. Update Employee Profile
            $employee->update([
                'office_id' => $request->office_id,
                'division_id' => $request->division_id,
                'position_title' => $request->position_title,
                'salary_grade' => $request->salary_grade,
                'status' => $request->status,
            ]);

            // 3. Update Roles
            $roles = $request->roles ?? [];
            // Ensure 'employee' role is preserved unless explicitly removed (but usually we keep it)
            $employeeRole = Role::where('key', 'employee')->first();
            if ($employeeRole && !in_array($employeeRole->id, $roles)) {
                $roles[] = $employeeRole->id;
            }
            $user->roles()->sync($roles);
        });

        return redirect()->route('admin.employees.index')
                         ->with('success', 'Employee updated successfully.');
    }
}
