<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Designation::truncate();

        $designations = [
            [
                'title'         => 'CEO',
                'description'   => 'Senior executive responsible for overseeing the technological development and strategy of the organization.',
                'parent_id'     => null,
                'company_id'    => 1, // Tech Solutions Ltd.
                'department_id' => 1, // Information Technology
                'status'        => true,
            ],
            [
                'title'         => 'CTO',
                'description'   => 'Manages human resources operations, employee relations, and organizational development initiatives.',
                'parent_id'     => 1,
                'company_id'    => 1, // Digital Marketing Pro
                'department_id' => 1, // Human Resources
                'status'        => true,
            ],
            [
                'title'         => 'Project Manager',
                'description'   => 'Develops and maintains software applications, leads technical projects, and mentors junior developers.',
                'parent_id'     => 1, // Reports to CTO
                'company_id'    => 1, // Tech Solutions Ltd.
                'department_id' => 1, // Information Technology
                'status'        => true,
            ],
            [
                'title'         => 'Team Leader',
                'description'   => 'Analyzes financial data, prepares reports, and provides insights for strategic financial decision-making.',
                'parent_id'     => null,
                'company_id'    => 1, // Financial Services Group
                'department_id' => 1, // Finance & Accounting
                'status'        => true,
            ],
            [
                'title'         => 'Senior Software Engineer',
                'description'   => 'Develops and executes marketing campaigns, manages social media presence, and analyzes market trends.',
                'parent_id'     => null,
                'company_id'    => 1, // Digital Marketing Pro
                'department_id' => 1, // Marketing & Sales
                'status'        => false,
            ],
        ];

        foreach ($designations as $designation) {
            Designation::create($designation);
        }
    }
}
