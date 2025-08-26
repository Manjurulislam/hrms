<?php

namespace Database\Seeders;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::truncate();

        $employees = [
            [
                'id_no'             => 'EMP001',
                'first_name'        => 'Mohammad',
                'last_name'         => 'Rahman',
                'email'             => 'mohammad.rahman@company.com',
                'phone'             => '+880-1712-345678',
                'sec_phone'         => '+880-1856-123456',
                'nid'               => '1234567890123',
                'gender'            => 'male',
                'qualification'     => 'Bachelor of Science in Computer Science',
                'emergency_contact' => '+880-1798-765432',
                'blood_group'       => 'B+',
                'marital_status'    => 'married',
                'bank_account'      => '1234567890',
                'address'           => 'House 15, Road 7, Dhanmondi, Dhaka-1205',
                'department_id'     => 1, // Information Technology
                'status'            => true,
                'date_of_birth'     => Carbon::parse('1990-05-15'),
                'joining_date'      => Carbon::parse('2023-01-15'),
                'probation_end_at'  => Carbon::parse('2023-07-15'),
            ],
            [
                'id_no'             => 'EMP002',
                'first_name'        => 'Fatima',
                'last_name'         => 'Khatun',
                'email'             => 'fatima.khatun@company.com',
                'phone'             => '+880-1934-567890',
                'sec_phone'         => null,
                'nid'               => '2345678901234',
                'gender'            => 'female',
                'qualification'     => 'Master of Business Administration (MBA)',
                'emergency_contact' => '+880-1678-234567',
                'blood_group'       => 'A+',
                'marital_status'    => 'single',
                'bank_account'      => '2345678901',
                'address'           => 'Flat 4B, Gulshan Avenue, Dhaka-1212',
                'department_id'     => 2, // Human Resources
                'status'            => true,
                'date_of_birth'     => Carbon::parse('1988-12-03'),
                'joining_date'      => Carbon::parse('2022-03-01'),
                'probation_end_at'  => Carbon::parse('2022-09-01'),
            ],
            [
                'id_no'             => 'EMP003',
                'first_name'        => 'Ahmed',
                'last_name'         => 'Hassan',
                'email'             => 'ahmed.hassan@company.com',
                'phone'             => '+880-1645-789012',
                'sec_phone'         => '+880-1723-456789',
                'nid'               => '3456789012345',
                'gender'            => 'male',
                'qualification'     => 'Bachelor of Commerce in Accounting',
                'emergency_contact' => '+880-1589-345678',
                'blood_group'       => 'O+',
                'marital_status'    => 'married',
                'bank_account'      => '3456789012',
                'address'           => 'Plot 25, Block C, Bashundhara R/A, Dhaka-1229',
                'department_id'     => 3, // Finance & Accounting
                'status'            => true,
                'date_of_birth'     => Carbon::parse('1985-08-22'),
                'joining_date'      => Carbon::parse('2021-06-10'),
                'probation_end_at'  => Carbon::parse('2021-12-10'),
            ],
            [
                'id_no'             => 'EMP004',
                'first_name'        => 'Rashida',
                'last_name'         => 'Begum',
                'email'             => 'rashida.begum@company.com',
                'phone'             => '+880-1756-890123',
                'sec_phone'         => '+880-1634-567890',
                'nid'               => '4567890123456',
                'gender'            => 'female',
                'qualification'     => 'Bachelor of Arts in Marketing',
                'emergency_contact' => '+880-1490-234567',
                'blood_group'       => 'AB+',
                'marital_status'    => 'divorced',
                'bank_account'      => '4567890123',
                'address'           => 'House 8, Lane 3, Uttara Sector 12, Dhaka-1230',
                'department_id'     => 4, // Marketing & Sales
                'status'            => false,
                'date_of_birth'     => Carbon::parse('1992-11-18'),
                'joining_date'      => Carbon::parse('2024-02-01'),
                'probation_end_at'  => Carbon::parse('2024-08-01'),
            ],
            [
                'id_no'             => 'EMP005',
                'first_name'        => 'Karim',
                'last_name'         => 'Uddin',
                'email'             => 'karim.uddin@company.com',
                'phone'             => '+880-1867-901234',
                'sec_phone'         => null,
                'nid'               => '5678901234567',
                'gender'            => 'male',
                'qualification'     => 'Diploma in Operations Management',
                'emergency_contact' => '+880-1745-345678',
                'blood_group'       => 'O-',
                'marital_status'    => 'single',
                'bank_account'      => '5678901234',
                'address'           => 'Apartment 12A, Banani DOHS, Dhaka-1213',
                'department_id'     => 5, // Operations
                'status'            => true,
                'date_of_birth'     => Carbon::parse('1995-03-10'),
                'joining_date'      => Carbon::parse('2024-05-20'),
                'probation_end_at'  => Carbon::parse('2024-11-20'),
            ],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }
    }
}
