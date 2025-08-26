<?php

namespace Database\Seeders;

use App\Models\DepartmentSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DepartmentScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DepartmentSchedule::truncate();

        $schedules = [
            [
                'department_id'   => 1, // Information Technology
                'work_days'       => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'work_start_time' => Carbon::createFromTime(9, 0, 0), // 09:00 AM
                'work_end_time'   => Carbon::createFromTime(18, 00, 0), // 06:00 PM
            ],
        ];

        foreach ($schedules as $schedule) {
            DepartmentSchedule::create($schedule);
        }
    }
}
