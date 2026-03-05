<?php

namespace Database\Seeders;

use App\Models\AttendanceSession;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::with('department')->get();
        $startDate = Carbon::now()->subDays(30);
        $endDate   = Carbon::today();

        foreach ($employees as $employee) {
            $date = $startDate->copy();

            while ($date->lte($endDate)) {
                // Skip weekends
                if ($date->isWeekend()) {
                    AttendanceSummary::create([
                        'employee_id'           => $employee->id,
                        'company_id'            => $employee->company_id,
                        'department_id'         => $employee->department_id,
                        'attendance_date'       => $date->toDateString(),
                        'status'                => 'weekend',
                        'is_working_day'        => false,
                        'total_working_minutes' => 0,
                        'total_break_minutes'   => 0,
                        'overtime_minutes'      => 0,
                        'late_minutes'          => 0,
                        'early_leave_minutes'   => 0,
                        'total_sessions'        => 0,
                    ]);
                    $date->addDay();
                    continue;
                }

                // Random attendance scenario
                $scenario = $this->randomScenario();

                if ($scenario === 'absent') {
                    AttendanceSummary::create([
                        'employee_id'           => $employee->id,
                        'company_id'            => $employee->company_id,
                        'department_id'         => $employee->department_id,
                        'attendance_date'       => $date->toDateString(),
                        'status'                => 'absent',
                        'is_working_day'        => true,
                        'total_working_minutes' => 0,
                        'total_break_minutes'   => 0,
                        'overtime_minutes'      => 0,
                        'late_minutes'          => 0,
                        'early_leave_minutes'   => 0,
                        'total_sessions'        => 0,
                    ]);
                    $date->addDay();
                    continue;
                }

                // Generate check-in/out times based on scenario
                $times = $this->generateTimes($scenario, $date);

                $workingMinutes = $times['check_out']->diffInMinutes($times['check_in']);
                $breakMinutes   = rand(30, 60);
                $workingMinutes -= $breakMinutes;
                $lateMinutes    = $scenario === 'late' ? rand(10, 45) : 0;
                $overtimeMinutes = $workingMinutes > 480 ? $workingMinutes - 480 : 0;

                $summary = AttendanceSummary::create([
                    'employee_id'           => $employee->id,
                    'company_id'            => $employee->company_id,
                    'department_id'         => $employee->department_id,
                    'attendance_date'       => $date->toDateString(),
                    'scheduled_start_time'  => '09:00:00',
                    'scheduled_end_time'    => '18:00:00',
                    'first_check_in'        => $times['check_in']->format('H:i:s'),
                    'last_check_out'        => $times['check_out']->format('H:i:s'),
                    'total_working_minutes' => $workingMinutes,
                    'total_break_minutes'   => $breakMinutes,
                    'overtime_minutes'      => $overtimeMinutes,
                    'late_minutes'          => $lateMinutes,
                    'early_leave_minutes'   => $scenario === 'half_day' ? rand(120, 240) : 0,
                    'total_sessions'        => 1,
                    'status'                => $scenario,
                    'is_working_day'        => true,
                    'ip_addresses'          => ['192.168.1.' . rand(10, 200)],
                    'locations'             => ['office'],
                ]);

                // Create attendance session
                AttendanceSession::create([
                    'employee_id'       => $employee->id,
                    'company_id'        => $employee->company_id,
                    'department_id'     => $employee->department_id,
                    'session_number'    => 1,
                    'check_in_time'     => $times['check_in'],
                    'check_out_time'    => $times['check_out'],
                    'check_in_ip'       => '192.168.1.' . rand(10, 200),
                    'check_in_location' => 'office',
                    'check_out_ip'      => '192.168.1.' . rand(10, 200),
                    'check_out_location' => 'office',
                    'duration_minutes'  => $times['check_out']->diffInMinutes($times['check_in']),
                    'attendance_date'   => $date->toDateString(),
                    'status'            => 'completed',
                ]);

                $date->addDay();
            }
        }
    }

    private function randomScenario(): string
    {
        $rand = rand(1, 100);

        if ($rand <= 65) return 'present';
        if ($rand <= 80) return 'late';
        if ($rand <= 90) return 'half_day';
        if ($rand <= 95) return 'work_from_home';
        return 'absent';
    }

    private function generateTimes(string $scenario, Carbon $date): array
    {
        switch ($scenario) {
            case 'late':
                $checkIn = $date->copy()->setTime(9, rand(15, 55));
                $checkOut = $date->copy()->setTime(18, rand(0, 30));
                break;

            case 'half_day':
                $checkIn = $date->copy()->setTime(9, rand(0, 15));
                $checkOut = $date->copy()->setTime(13, rand(0, 30));
                break;

            case 'work_from_home':
                $checkIn = $date->copy()->setTime(8, rand(30, 59));
                $checkOut = $date->copy()->setTime(17, rand(30, 59));
                break;

            default: // present
                $checkIn = $date->copy()->setTime(8, rand(30, 59));
                $checkOut = $date->copy()->setTime(18, rand(0, 45));
                break;
        }

        return ['check_in' => $checkIn, 'check_out' => $checkOut];
    }
}
