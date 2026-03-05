<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            CompanySeeder::class,
            CompanyWorkingDaySeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            HolidaySeeder::class,
            LeaveTypeSeeder::class,
            RoleSeeder::class,
            EmployeeSeeder::class,
            UserSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
