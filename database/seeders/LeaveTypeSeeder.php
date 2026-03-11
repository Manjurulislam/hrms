<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Bangladesh Labour Act 2006 standard leave entitlements
        $bdLeaveTypes = [
            ['name' => 'Annual Leave', 'max_per_year' => 18],
            ['name' => 'Casual Leave', 'max_per_year' => 10],
            ['name' => 'Sick Leave', 'max_per_year' => 14],
            ['name' => 'Maternity Leave', 'max_per_year' => 7],
            ['name' => 'Paternity Leave', 'max_per_year' => 7],
            ['name' => 'Compensatory Leave', 'max_per_year' => 7],
            ['name' => 'Unpaid Leave', 'max_per_year' => 30],
        ];

        foreach ($bdLeaveTypes as $leaveType) {
            LeaveType::create(array_merge($leaveType, [
                'company_id' => 1,
                'status'     => true,
            ]));
        }
    }
}
