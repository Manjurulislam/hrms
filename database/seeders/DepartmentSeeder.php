<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::truncate();

        $departments = [
            [
                'name'        => 'Web',
                'description' => 'Responsible for managing and maintaining all IT infrastructure, software development, and technical support services.',
                'company_id'  => 1, // Tech Solutions Ltd.
                'status'      => true,
            ],
            [
                'name'        => 'Game',
                'description' => 'Handles employee recruitment, training, benefits administration, and workplace policies implementation.',
                'company_id'  => 2, // Digital Marketing Pro
                'status'      => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
