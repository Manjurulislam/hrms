<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            CompanySeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            DepartmentScheduleSeeder::class,
            HolidaySeeder::class,
            LeaveTypeSeeder::class,
            RoleSeeder::class,
            EmployeeSeeder::class,  // Employees must be created first
            UserSeeder::class,       // Then create users (including employee users)
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
