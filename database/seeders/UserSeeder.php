<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();

        $users = [
            [
                'name'     => 'Admin',
                'email'    => 'admin@mail.com',
                'password' => Hash::make('pass234'),
                'status'   => true,
            ],
            [
                'name'        => 'Fatima Khatun',
                'email'       => 'fatima.khatun@company.com',
                'password'    => Hash::make('password'),
                'employee_id' => 2, // Fatima Khatun from EmployeeSeeder
                'status'      => true,
            ],
            [
                'name'        => 'Ahmed Hassan',
                'email'       => 'ahmed.hassan@company.com',
                'password'    => Hash::make('password'),
                'employee_id' => 3, // Ahmed Hassan from EmployeeSeeder
                'status'      => true,
            ],
            [
                'name'        => 'Rashida Begum',
                'email'       => 'rashida.begum@company.com',
                'password'    => Hash::make('password'),
                'employee_id' => 4, // Rashida Begum from EmployeeSeeder
                'status'      => false,
            ],
            [
                'name'        => 'Karim Uddin',
                'email'       => 'karim.uddin@company.com',
                'password'    => Hash::make('password'),
                'employee_id' => 5, // Karim Uddin from EmployeeSeeder
                'status'      => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
