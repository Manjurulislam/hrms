<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Backend',  'description' => 'Backend development team',  'company_id' => 1, 'status' => true],
            ['name' => 'Frontend', 'description' => 'Frontend development team', 'company_id' => 1, 'status' => true],
            ['name' => 'Support',  'description' => 'Customer support team',     'company_id' => 1, 'status' => true],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
