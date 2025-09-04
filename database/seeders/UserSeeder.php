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
        User::truncate();

        // Normal Users (can have multiple roles)
        $normalUsers = [
            [
                'name'     => 'System Admin',
                'email'    => 'admin@mail.com',
                'password' => Hash::make('pass234'),
                'roles'    => ['Super Admin']
            ],
            [
                'name'     => 'HR Manager',
                'email'    => 'hr.manager@mail.com',
                'password' => Hash::make('pass234'),
                'roles'    => ['Analysis']
            ],
            [
                'name'     => 'Operations Manager',
                'email'    => 'ops.manager@mail.com',
                'password' => Hash::make('pass234'),
                'roles'    => ['Admin']
            ],
        ];

        // Create normal users
        foreach ($normalUsers as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);

            $user = User::create($userData);

            // Assign multiple roles to normal users
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $user->roles()->attach($role);
                }
            }
        }

        // Fetch employees and create user accounts for them
        $employees = Employee::all();

        foreach ($employees as $employee) {
            // Create user account for each employee
            $userData = [
                'name'        => $employee->first_name . ' ' . $employee->last_name,
                'email'       => $employee->email,
                'password'    => Hash::make('password'), // Default password
                'employee_id' => $employee->id,
                'status'      => $employee->status,
            ];

            $user = User::create($userData);

            // Assign default Employee role (employees can only have one role)
            $role = Role::where('name', 'Employee')->first();

            if ($role) {
                $user->roles()->attach($role);
            }
        }
    }
}
