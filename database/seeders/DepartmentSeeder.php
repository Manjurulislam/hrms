<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            // Softwind Tech (company_id: 1)
            ['name' => 'Backend',  'description' => 'Backend development team',  'company_id' => 1, 'status' => true],
            ['name' => 'Frontend', 'description' => 'Frontend development team', 'company_id' => 1, 'status' => true],
            ['name' => 'Support',  'description' => 'Customer support team',     'company_id' => 1, 'status' => true],

            // Aliede (company_id: 2)
            ['name' => 'Backend',  'description' => 'Backend development team',  'company_id' => 2, 'status' => true],
            ['name' => 'Frontend', 'description' => 'Frontend development team', 'company_id' => 2, 'status' => true],
            ['name' => 'Support',  'description' => 'Customer support team',     'company_id' => 2, 'status' => true],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
