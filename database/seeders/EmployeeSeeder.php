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
            ],
            // TL Backend - Manjurul (reports to PM)
            [
                'id_no'          => 'SWT004',
                'first_name'     => 'Manjurul',
                'last_name'      => 'Islam',
                'email'          => 'manjurul@softwindtech.com',
                'phone'          => '+880-1712-000004',
                'gender'         => 'male',
                'company_id'     => 1,
                'department_id'  => 1, // Backend
                'designation_id' => 4, // Team Lead
                'manager_id'     => 3, // Kabir
                'emp_status'     => 'confirmed',
                'status'         => true,
                'joining_date'   => Carbon::parse('2021-06-01'),
            ],
            // TL Frontend - Hasib (reports to PM)
            [
                'id_no'          => 'SWT005',
                'first_name'     => 'Hasib',
                'last_name'      => 'Khan',
                'email'          => 'hasib@softwindtech.com',
                'phone'          => '+880-1712-000005',
                'gender'         => 'male',
                'company_id'     => 1,
                'department_id'  => 2, // Frontend
                'designation_id' => 4, // Team Lead
                'manager_id'     => 3, // Kabir
                'emp_status'     => 'confirmed',
                'status'         => true,
                'joining_date'   => Carbon::parse('2021-06-15'),
            ],
            // Backend Developer - Rahim (reports to TL Manjurul)
            [
                'id_no'          => 'SWT006',
                'first_name'     => 'Rahim',
                'last_name'      => 'Uddin',
                'email'          => 'rahim@softwindtech.com',
                'phone'          => '+880-1712-000006',
                'gender'         => 'male',
                'company_id'     => 1,
                'department_id'  => 1, // Backend
                'designation_id' => 6, // Developer
                'manager_id'     => 4, // Manjurul
                'emp_status'     => 'confirmed',
                'status'         => true,
                'joining_date'   => Carbon::parse('2022-03-01'),
            ],
            // Frontend Developer - Nusrat (reports to TL Hasib)
            [
                'id_no'          => 'SWT007',
                'first_name'     => 'Nusrat',
                'last_name'      => 'Jahan',
                'email'          => 'nusrat@softwindtech.com',
                'phone'          => '+880-1712-000007',
                'gender'         => 'female',
                'company_id'     => 1,
                'department_id'  => 2, // Frontend
                'designation_id' => 6, // Developer
                'manager_id'     => 5, // Hasib
                'emp_status'     => 'confirmed',
                'status'         => true,
                'joining_date'   => Carbon::parse('2022-05-01'),
            ],
        ];

        $employeeRole = Role::where('slug', 'employee')->first();
        $managerRole  = Role::where('slug', 'manager')->first();

        foreach ($employees as $data) {
            $employee = Employee::create($data);

            $user = User::create([
                'name'        => $employee->full_name,
                'email'       => $employee->email,
                'password'    => Hash::make('pass234'),
                'employee_id' => $employee->id,
                'status'      => true,
            ]);

            // Level 1-3 get Manager role, others get Employee role
            if ($employee->designation && $employee->designation->level?->value <= 3) {
                $user->roles()->attach($managerRole);
            } else {
                $user->roles()->attach($employeeRole);
            }
        }
    }
}
