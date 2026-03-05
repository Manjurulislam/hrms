<?php

namespace Database\Seeders;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            // === Softwind Tech (company_id: 1) ===

            // CEO - Moinur (no manager)
            [
                'id_no'             => 'SWT001',
                'first_name'        => 'Moinur',
                'last_name'         => 'Rahman',
                'email'             => 'moinur@softwindtech.com',
                'phone'             => '+880-1712-000001',
                'gender'            => 'male',
                'company_id'        => 1,
                'department_id'     => 1, // Backend (primary)
                'designation_id'    => 1, // CEO
                'manager_id'        => null,
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2020-01-01'),
            ],
            // CTO - Mafuz (reports to CEO)
            [
                'id_no'             => 'SWT002',
                'first_name'        => 'Mafuz',
                'last_name'         => 'Ahmed',
                'email'             => 'mafuz@softwindtech.com',
                'phone'             => '+880-1712-000002',
                'gender'            => 'male',
                'company_id'        => 1,
                'department_id'     => 1,
                'designation_id'    => 2, // CTO
                'manager_id'        => 1, // Moinur
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2020-03-01'),
            ],
            // PM - Kabir (reports to CTO)
            [
                'id_no'             => 'SWT003',
                'first_name'        => 'Kabir',
                'last_name'         => 'Hossain',
                'email'             => 'kabir@softwindtech.com',
                'phone'             => '+880-1712-000003',
                'gender'            => 'male',
                'company_id'        => 1,
                'department_id'     => 1,
                'designation_id'    => 3, // Project Manager
                'manager_id'        => 2, // Mafuz
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2021-01-15'),
            ],
            // TL Backend - Manjurul (reports to PM)
            [
                'id_no'             => 'SWT004',
                'first_name'        => 'Manjurul',
                'last_name'         => 'Islam',
                'email'             => 'manjurul@softwindtech.com',
                'phone'             => '+880-1712-000004',
                'gender'            => 'male',
                'company_id'        => 1,
                'department_id'     => 1, // Backend
                'designation_id'    => 4, // Team Lead
                'manager_id'        => 3, // Kabir
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2021-06-01'),
            ],
            // TL Frontend - Hasib (reports to PM)
            [
                'id_no'             => 'SWT005',
                'first_name'        => 'Hasib',
                'last_name'         => 'Khan',
                'email'             => 'hasib@softwindtech.com',
                'phone'             => '+880-1712-000005',
                'gender'            => 'male',
                'company_id'        => 1,
                'department_id'     => 2, // Frontend
                'designation_id'    => 4, // Team Lead
                'manager_id'        => 3, // Kabir
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2021-06-15'),
            ],
            // Backend Developer 1 (reports to TL Manjurul)
            [
                'id_no'             => 'SWT006',
                'first_name'        => 'Rahim',
                'last_name'         => 'Uddin',
                'email'             => 'rahim@softwindtech.com',
                'phone'             => '+880-1712-000006',
                'gender'            => 'male',
                'company_id'        => 1,
                'department_id'     => 1, // Backend
                'designation_id'    => 6, // Developer
                'manager_id'        => 4, // Manjurul
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2022-03-01'),
            ],
            // Frontend Developer 1 (reports to TL Hasib)
            [
                'id_no'             => 'SWT007',
                'first_name'        => 'Nusrat',
                'last_name'         => 'Jahan',
                'email'             => 'nusrat@softwindtech.com',
                'phone'             => '+880-1712-000007',
                'gender'            => 'female',
                'company_id'        => 1,
                'department_id'     => 2, // Frontend
                'designation_id'    => 6, // Developer
                'manager_id'        => 5, // Hasib
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2022-05-01'),
            ],

            // === Aliede (company_id: 2) ===

            // TL Backend - Aliede (reports to Kabir via company_employee)
            [
                'id_no'             => 'ALD001',
                'first_name'        => 'Farhan',
                'last_name'         => 'Alam',
                'email'             => 'farhan@aliede.com',
                'phone'             => '+880-1798-000001',
                'gender'            => 'male',
                'company_id'        => 2,
                'department_id'     => 4, // Aliede Backend
                'designation_id'    => 10, // Aliede Team Lead
                'manager_id'        => null, // will be set via company_employee for cross-company
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2022-01-10'),
            ],
            // TL Frontend - Aliede
            [
                'id_no'             => 'ALD002',
                'first_name'        => 'Tasnim',
                'last_name'         => 'Akter',
                'email'             => 'tasnim@aliede.com',
                'phone'             => '+880-1798-000002',
                'gender'            => 'female',
                'company_id'        => 2,
                'department_id'     => 5, // Aliede Frontend
                'designation_id'    => 10, // Aliede Team Lead
                'manager_id'        => null,
                'emp_status' => 'confirmed',
                'status'            => true,
                'joining_date'      => Carbon::parse('2022-02-15'),
            ],
            // Developer - Aliede Backend
            [
                'id_no'             => 'ALD003',
                'first_name'        => 'Sakib',
                'last_name'         => 'Hasan',
                'email'             => 'sakib@aliede.com',
                'phone'             => '+880-1798-000003',
                'gender'            => 'male',
                'company_id'        => 2,
                'department_id'     => 4,
                'designation_id'    => 12, // Aliede Developer
                'manager_id'        => 8,  // Farhan (TL)
                'emp_status' => 'probation',
                'status'            => true,
                'joining_date'      => Carbon::parse('2024-01-10'),
            ],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }

    }
}
