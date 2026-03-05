<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            // Softwind Tech designations
            ['title' => 'CEO',              'parent_id' => null, 'company_id' => 1, 'level' => 1, 'status' => true],
            ['title' => 'CTO',              'parent_id' => 1,    'company_id' => 1, 'level' => 2, 'status' => true],
            ['title' => 'Project Manager',  'parent_id' => 2,    'company_id' => 1, 'level' => 3, 'status' => true],
            ['title' => 'Team Lead',        'parent_id' => 3,    'company_id' => 1, 'level' => 4, 'status' => true],
            ['title' => 'Senior Developer', 'parent_id' => 4,    'company_id' => 1, 'level' => 5, 'status' => true],
            ['title' => 'Developer',        'parent_id' => 4,    'company_id' => 1, 'level' => 5, 'status' => true],

            // Aliede designations
            ['title' => 'CEO',              'parent_id' => null, 'company_id' => 2, 'level' => 1, 'status' => true],
            ['title' => 'CTO',              'parent_id' => 7,    'company_id' => 2, 'level' => 2, 'status' => true],
            ['title' => 'Project Manager',  'parent_id' => 8,    'company_id' => 2, 'level' => 3, 'status' => true],
            ['title' => 'Team Lead',        'parent_id' => 9,    'company_id' => 2, 'level' => 4, 'status' => true],
            ['title' => 'Senior Developer', 'parent_id' => 10,   'company_id' => 2, 'level' => 5, 'status' => true],
            ['title' => 'Developer',        'parent_id' => 10,   'company_id' => 2, 'level' => 5, 'status' => true],
        ];

        foreach ($designations as $designation) {
            Designation::create($designation);
        }

    }
}
