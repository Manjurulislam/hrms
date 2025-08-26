<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LeaveType::truncate();

        $leaveTypes = [
            [
                'name'       => 'Annual Leave',
                'days'       => 21,
                'company_id' => 1, // Tech Solutions Ltd.
                'status'     => true,
            ],
            [
                'name'       => 'Sick Leave',
                'days'       => 14,
                'company_id' => 2, // Digital Marketing Pro
                'status'     => true,
            ],
            [
                'name'       => 'Maternity Leave',
                'days'       => 120,
                'company_id' => 4, // Healthcare Innovations
                'status'     => true,
            ],
            [
                'name'       => 'Casual Leave',
                'days'       => 10,
                'company_id' => 5, // Financial Services Group
                'status'     => true,
            ],
            [
                'name'       => 'Emergency Leave',
                'days'       => 7,
                'company_id' => 3, // Green Energy Systems
                'status'     => false,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::create($leaveType);
        }
    }
}
