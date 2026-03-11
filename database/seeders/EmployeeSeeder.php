<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            // CEO - Moinur (no manager)
            [
                'id_no'          => 'SWT001',
                'first_name'     => 'Moinur',
                'last_name'      => 'Rahman',
                'email'          => 'moinur@softwindtech.com',
                'phone'          => '+880-1712-000001',
                'gender'         => 'male',
                'company_id'     => 1,
                'department_id'  => 1,
                'designation_id' => 1, // CEO
                'manager_id'     => null,
                'emp_status'     => 'confirmed',
                'status'         => true,
                'joining_date'   => Carbon::parse('2020-01-01'),
            ],
            // CTO - Mafuz (reports to CEO)
            [
                'id_no'          => 'SWT002',
                'first_name'     => 'Mafuz',
                'last_name'      => 'Ahmed',
                'email'          => 'mafuz@softwindtech.com',
                'phone'          => '+880-1712-000002',
                'gender'         => 'male',
                'company_id'     => 1,
                'department_id'  => 1,
                'designation_id' => 2, // CTO
                'manager_id'     => 1, // Moinur
                'emp_status'     => 'confirmed',
                'status'         => true,
                'joining_date'   => Carbon::parse('2020-03-01'),
            ],
            // PM - Kabir (reports to CTO)
            [
                'id_no'          => 'SWT003',
                'first_name'     => 'Kabir',
                'last_name'      => 'Hossain',
                'email'          => 'kabir@softwindtech.com',
                'phone'          => '+880-1712-000003',
                'gender'         => 'male',
                'company_id'     => 1,
                'department_id'  => 1,
                'designation_id' => 3, // Project Manager
                'manager_id'     => 2, // Mafuz
                'emp_status'     => 'confirmed',
                'status'         => true,
                'joining_date'   => Carbon::parse('2021-01-15'),
            ]
        ];

        $adminRole    = Role::where('slug', 'admin')->first();
        $employeeRole = Role::where('slug', 'employee')->first();

        // CEO & CTO get admin + employee roles (admin panel + employee panel)
        $adminEmployeeIds = ['SWT001', 'SWT002'];

        foreach ($employees as $data) {
            $employee = Employee::create($data);

            $user = User::create([
                'name'        => $employee->full_name,
                'email'       => $employee->email,
                'password'    => Hash::make('pass234'),
                'employee_id' => $employee->id,
                'status'      => true,
            ]);

            if (in_array($employee->id_no, $adminEmployeeIds)) {
                $user->roles()->attach([$adminRole->id, $employeeRole->id]);
            } else {
                $user->roles()->attach($employeeRole);
            }
        }
    }
}
