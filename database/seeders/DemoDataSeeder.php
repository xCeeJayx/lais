<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{Role, User, Office, Division, Employee, ApprovalStep, LeaveType};

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Offices
        $car = Office::updateOrCreate(
            ['office_code' => 'DENR-CAR-REG'],
            ['name' => 'DENR CAR Regional Office', 'address' => 'Baguio City']
        );

        $r1 = Office::updateOrCreate(
            ['office_code' => 'DENR-R1-REG'],
            ['name' => 'DENR Region I Regional Office', 'address' => 'San Fernando City, La Union']
        );

        // Divisions per office
        $carMIS = Division::updateOrCreate(['office_id' => $car->id, 'name' => 'MIS Office'], []);
        $carADM = Division::updateOrCreate(['office_id' => $car->id, 'name' => 'Administrative Division'], []);

        $r1MIS  = Division::updateOrCreate(['office_id' => $r1->id, 'name' => 'MIS Office'], []);
        $r1ADM  = Division::updateOrCreate(['office_id' => $r1->id, 'name' => 'Administrative Division'], []);

        // Roles
        $role = fn(string $key) => Role::where('key', $key)->firstOrFail();

        // Super Admin (no employee record required)
        $super = User::updateOrCreate(
            ['email' => 'super@demo.test'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        $super->roles()->syncWithoutDetaching([$role('super_admin')->id]);

        // Office Admins
        $carAdmin = $this->makeUserWithEmployee(
            'car.admin@demo.test', 'CAR Office Admin', 'password',
            $car->id, $carADM->id, ['office_admin']
        );

        $r1Admin = $this->makeUserWithEmployee(
            'r1.admin@demo.test', 'R1 Office Admin', 'password',
            $r1->id, $r1ADM->id, ['office_admin']
        );

        // Employees (one per office)
        $carEmp = $this->makeUserWithEmployee(
            'car.emp@demo.test', 'CAR Employee', 'password',
            $car->id, $carMIS->id, ['employee']
        );

        $r1Emp = $this->makeUserWithEmployee(
            'r1.emp@demo.test', 'R1 Employee', 'password',
            $r1->id, $r1MIS->id, ['employee']
        );

        // Approvers per office:
        // Step 1 (Division Chief) - must match division
        $this->makeUserWithEmployee(
            'car.divchief@demo.test', 'CAR Division Chief (MIS)', 'password',
            $car->id, $carMIS->id, ['approver_division_chief']
        );

        $this->makeUserWithEmployee(
            'r1.divchief@demo.test', 'R1 Division Chief (MIS)', 'password',
            $r1->id, $r1MIS->id, ['approver_division_chief']
        );

        // Step 2-4 office-level approvers
        $this->makeUserWithEmployee(
            'car.personnel@demo.test', 'CAR Personnel', 'password',
            $car->id, null, ['approver_personnel']
        );

        $this->makeUserWithEmployee(
            'car.chiefpersonnel@demo.test', 'CAR Chief Personnel', 'password',
            $car->id, null, ['approver_chief_personnel']
        );

        $this->makeUserWithEmployee(
            'car.ard@demo.test', 'CAR ARD-MS', 'password',
            $car->id, null, ['approver_ard_ms']
        );

        // Approval steps per office (DENR chain)
        $this->seedSteps($car->id);
        $this->seedSteps($r1->id);

        // Leave Types (minimum)
        $types = [
            ['code' => 'VL', 'name' => 'Vacation Leave'],
            ['code' => 'FL', 'name' => 'Mandatory/Forced Leave'],
            ['code' => 'SL', 'name' => 'Sick Leave'],
            ['code' => 'ML', 'name' => 'Maternity Leave'],
            ['code' => 'PL', 'name' => 'Paternity Leave'],
            ['code' => 'SPL', 'name' => 'Special Privilege Leave'],
            ['code' => 'SOLO', 'name' => 'Solo Parent Leave'],
            ['code' => 'STUDY', 'name' => 'Study Leave'],
            ['code' => 'VAWC', 'name' => '10-Day VAWC Leave'],
            ['code' => 'REHAB', 'name' => 'Rehabilitation Privilege'],
            ['code' => 'WOMEN', 'name' => 'Special Leave Benefits for Women'],
            ['code' => 'CALAMITY', 'name' => 'Special Emergency (Calamity) Leave'],
            ['code' => 'ADOPT', 'name' => 'Adoption Leave'],
            ['code' => 'MON', 'name' => 'Monetization of Leave Credits'],
            ['code' => 'TL', 'name' => 'Terminal Leave'],
            ];

            foreach ($types as $t) {
            LeaveType::updateOrCreate(['code' => $t['code']], [
                'code' => $t['code'],
                'name' => $t['name'],
                'is_active' => true,
            ]);
            }}


    private function makeUserWithEmployee(
        string $email, string $name, string $password,
        int $officeId, ?int $divisionId, array $roleKeys
    ): User {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        $roleIds = Role::whereIn('key', $roleKeys)->pluck('id')->all();
        $user->roles()->syncWithoutDetaching($roleIds);

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            ['office_id' => $officeId, 'division_id' => $divisionId, 'position_title' => 'Staff']
        );

        return $user;
    }

    private function seedSteps(int $officeId): void
    {
        $steps = [
            ['step_order' => 1, 'role_key' => 'approver_division_chief', 'name' => 'Division Chief'],
            ['step_order' => 2, 'role_key' => 'approver_personnel', 'name' => 'Personnel'],
            ['step_order' => 3, 'role_key' => 'approver_chief_personnel', 'name' => 'Chief Personnel'],
            ['step_order' => 4, 'role_key' => 'approver_ard_ms', 'name' => 'ARD-MS'],
        ];

        foreach ($steps as $s) {
            ApprovalStep::updateOrCreate(
                ['office_id' => $officeId, 'step_order' => $s['step_order']],
                ['role_key' => $s['role_key'], 'name' => $s['name']]
            );
        }
    }
}
