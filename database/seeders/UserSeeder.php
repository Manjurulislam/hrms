<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // System admin (not linked to employee)
        $admin = User::create([
            'name'     => 'System Admin',
            'email'    => 'admin@mail.com',
            'password' => Hash::make('pass234'),
        ]);
        $admin->roles()->attach(Role::where('name', 'Super Admin')->first());

        // Create user accounts for all employees
        $employees = Employee::all();
        $employeeRole = Role::where('name', 'Employee')->first();
        $managerRole = Role::where('name', 'Manager')->first();

        foreach ($employees as $employee) {
            $user = User::create([
                'name'        => $employee->full_name,
                'email'       => $employee->email,
                'password'    => Hash::make('password'),
                'employee_id' => $employee->id,
                'status'      => $employee->status,
            ]);

            // Assign role based on designation level
            if ($employee->designation && $employee->designation->level <= 3) {
                $user->roles()->attach($managerRole);
            } else {
                $user->roles()->attach($employeeRole);
            }
        }
    }
}
