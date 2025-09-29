<?php

namespace App\Services;

use App\Models\DepartmentSchedule;
use App\Models\DepartmentWorkingDay;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkScheduleService
{
    /**
     * Check if a given date is a working day for an employee
     */
    public function isWorkingDay(Employee $employee, Carbon $date): bool
    {
        $schedule = $employee->department->schedule;

        if (!$schedule) {
            // Default: Monday to Friday are working days
            return !$date->isWeekend();
        }

        // Get the day name (e.g., 'Monday', 'Tuesday', etc.)
        $dayName = $date->format('l');

        // Check if this day is a working day in the schedule
        $workingDay = DepartmentWorkingDay::where('department_schedule_id', $schedule->id)
            ->where('day', $dayName)
            ->first();

        // If no record exists, default to weekday = working day
        if (!$workingDay) {
            return !$date->isWeekend();
        }

        return $workingDay->status;
    }

    /**
     * Get all working days for a department schedule
     */
    public function getWorkingDays(DepartmentSchedule $schedule): Collection
    {
        return DepartmentWorkingDay::where('department_schedule_id', $schedule->id)
            ->where('status', true)
            ->pluck('day');
    }

    /**
     * Get weekend days for a department schedule
     */
    public function getWeekendDays(DepartmentSchedule $schedule): Collection
    {
        return DepartmentWorkingDay::where('department_schedule_id', $schedule->id)
            ->where('status', false)
            ->pluck('day');
    }

    /**
     * Setup default working days for a new department schedule
     */
    public function setupDefaultWorkingDays(DepartmentSchedule $schedule, array $weekendDays = ['Saturday', 'Sunday']): void
    {
        $allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($allDays as $day) {
            DepartmentWorkingDay::create([
                'department_schedule_id' => $schedule->id,
                'day' => $day,
                'status' => !in_array($day, $weekendDays) // true for working days, false for weekends
            ]);
        }
    }

    /**
     * Update weekend configuration for a department
     */
    public function updateWeekends(DepartmentSchedule $schedule, array $weekendDays): void
    {
        $allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($allDays as $day) {
            DepartmentWorkingDay::updateOrCreate(
                [
                    'department_schedule_id' => $schedule->id,
                    'day' => $day
                ],
                [
                    'status' => !in_array($day, $weekendDays)
                ]
            );
        }
    }

    /**
     * Get schedule details for an employee including working days
     */
    public function getEmployeeSchedule(Employee $employee): array
    {
        $schedule = $employee->department->schedule;

        if (!$schedule) {
            return [
                'has_schedule' => false,
                'work_start_time' => config('attendance.default_office_start', '09:00'),
                'work_end_time' => config('attendance.default_office_end', '18:00'),
                'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'weekend_days' => ['Saturday', 'Sunday'],
                'grace_period' => config('attendance.late_grace_period', 15)
            ];
        }

        $workingDays = $this->getWorkingDays($schedule)->toArray();
        $weekendDays = $this->getWeekendDays($schedule)->toArray();

        // If no working days configured, use defaults
        if (empty($workingDays) && empty($weekendDays)) {
            $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $weekendDays = ['Saturday', 'Sunday'];
        }

        return [
            'has_schedule' => true,
            'work_start_time' => $schedule->work_start_time,
            'work_end_time' => $schedule->work_end_time,
            'working_days' => $workingDays,
            'weekend_days' => $weekendDays,
            'grace_period' => $schedule->delay ?? 15,
            'office_ip' => $schedule->office_ip
        ];
    }

    /**
     * Check if employee should be marked absent for a date
     */
    public function shouldMarkAbsent(Employee $employee, Carbon $date): bool
    {
        // Don't mark absent on weekends
        if (!$this->isWorkingDay($employee, $date)) {
            return false;
        }

        // Don't mark absent on holidays (you can implement holiday check here)
        // if ($this->isHoliday($employee, $date)) {
        //     return false;
        // }

        // Don't mark absent if on leave
        // if ($this->isOnLeave($employee, $date)) {
        //     return false;
        // }

        return true;
    }

    /**
     * Get working days count for a month
     */
    public function getMonthlyWorkingDays(Employee $employee, int $year, int $month): int
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        while ($startDate <= $endDate) {
            if ($this->isWorkingDay($employee, $startDate)) {
                $workingDays++;
            }
            $startDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Check if the current time is within office hours
     */
    public function isWithinOfficeHours(Employee $employee, Carbon $time = null): bool
    {
        $time = $time ?? now();
        $schedule = $employee->department->schedule;

        if (!$schedule) {
            $startTime = Carbon::parse(config('attendance.default_office_start', '09:00'));
            $endTime = Carbon::parse(config('attendance.default_office_end', '18:00'));
        } else {
            $startTime = Carbon::parse($schedule->work_start_time);
            $endTime = Carbon::parse($schedule->work_end_time);
        }

        // Set the date to today for comparison
        $startTime->setDate($time->year, $time->month, $time->day);
        $endTime->setDate($time->year, $time->month, $time->day);

        return $time->between($startTime, $endTime);
    }

    /**
     * Calculate late minutes based on schedule
     */
    public function calculateLateMinutes(Employee $employee, Carbon $checkInTime): int
    {
        $schedule = $employee->department->schedule;

        if (!$schedule) {
            $scheduledStart = Carbon::parse(config('attendance.default_office_start', '09:00'));
            $graceMinutes = config('attendance.late_grace_period', 15);
        } else {
            $scheduledStart = Carbon::parse($schedule->work_start_time);
            $graceMinutes = $schedule->delay ?? 15;
        }

        // Set to same date as check-in
        $scheduledStart->setDate($checkInTime->year, $checkInTime->month, $checkInTime->day);

        // Add grace period
        $scheduledStartWithGrace = $scheduledStart->copy()->addMinutes($graceMinutes);

        if ($checkInTime->lte($scheduledStartWithGrace)) {
            return 0; // Not late
        }

        return $checkInTime->diffInMinutes($scheduledStart);
    }

    /**
     * Calculate early leave minutes
     */
    public function calculateEarlyLeaveMinutes(Employee $employee, Carbon $checkOutTime): int
    {
        $schedule = $employee->department->schedule;

        if (!$schedule) {
            $scheduledEnd = Carbon::parse(config('attendance.default_office_end', '18:00'));
            $graceMinutes = config('attendance.early_leave_grace_period', 15);
        } else {
            $scheduledEnd = Carbon::parse($schedule->work_end_time);
            $graceMinutes = $schedule->delay ?? 15;
        }

        // Set to same date as check-out
        $scheduledEnd->setDate($checkOutTime->year, $checkOutTime->month, $checkOutTime->day);

        // Subtract grace period for early leave
        $scheduledEndWithGrace = $scheduledEnd->copy()->subMinutes($graceMinutes);

        if ($checkOutTime->gte($scheduledEndWithGrace)) {
            return 0; // Not early
        }

        return $scheduledEnd->diffInMinutes($checkOutTime);
    }
}