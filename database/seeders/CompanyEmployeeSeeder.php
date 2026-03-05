<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('company_employee')->truncate();

        // Moinur (employee_id: 1) manages Aliede as CEO
        DB::table('company_employee')->insert([
            ['company_id' => 2, 'employee_id' => 1, 'role' => 'ceo', 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
            // Mafuz (employee_id: 2) manages Aliede as CTO
            ['company_id' => 2, 'employee_id' => 2, 'role' => 'cto', 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
            // Kabir (employee_id: 3) manages Aliede as PM
            ['company_id' => 2, 'employee_id' => 3, 'role' => 'pm',  'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
