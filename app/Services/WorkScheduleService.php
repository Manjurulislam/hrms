<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyWorkingDay;
use App\Models\Employee;
use App\Traits\LoadsSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkScheduleService
{
    use LoadsSettings;

    public function __construct()
    {
        $this->loadSettings();
    }
    public function isWorkingDay(Employee $employee, Carbon $date): bool
    {
        $company = $employee->company;

        if (!$company) {
            return !$date->isWeekend();
        }

        $dayOfWeek = $date->dayOfWeek; // 0=Sunday, 6=Saturday

        $workingDay = CompanyWorkingDay::where('company_id', $company->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingDay) {
            return !$date->isWeekend();
        }

        return $workingDay->is_working;
    }

    public function getWorkingDays(Company $company): Collection
    {
        return CompanyWorkingDay::where('company_id', $company->id)
            ->where('is_working', true)
            ->pluck('day_of_week');
    }

    public function getWeekendDays(Company $company): Collection
    {
        return CompanyWorkingDay::where('company_id', $company->id)
            ->where('is_working', false)
            ->pluck('day_of_week');
    }

    public function getEmployeeSchedule(Employee $employee): array
    {
        $company = $employee->company;

        if (!$company) {
            return [
                'has_schedule'    => false,
                'work_start_time' => config('attendance.default_office_start', '09:00'),
                'work_end_time'   => config('attendance.default_office_end', '18:00'),
                'working_days'    => [1, 2, 3, 4, 5], // Mon-Fri
                'weekend_days'    => [0, 6], // Sun, Sat
                'office_ip'       => null,
            ];
        }

        $workingDays = $this->getWorkingDays($company)->toArray();
        $weekendDays = $this->getWeekendDays($company)->toArray();

        if (empty($workingDays) && empty($weekendDays)) {
            $workingDays = [1, 2, 3, 4, 5];
            $weekendDays = [0, 6];
        }

        return [
            'has_schedule'    => true,
            'work_start_time' => $company->office_start_time ?? '09:00',
            'work_end_time'   => $company->office_end_time ?? '18:00',
            'working_days'    => $workingDays,
            'weekend_days'    => $weekendDays,
            'office_ip'       => $company->office_ip,
        ];
    }

    public function shouldMarkAbsent(Employee $employee, Carbon $date): bool
    {
        return $this->isWorkingDay($employee, $date);
    }

    public function getMonthlyWorkingDays(Employee $employee, int $year, int $month): int
    {
        $startDate   = Carbon::create($year, $month, 1);
        $endDate     = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        while ($startDate <= $endDate) {
            if ($this->isWorkingDay($employee, $startDate)) {
                $workingDays++;
            }
            $startDate->addDay();
        }

        return $workingDays;
    }

    public function isWithinOfficeHours(Employee $employee, Carbon $time = null): bool
    {
        $time    = $time ?? now();
        $company = $employee->company;

        if (!$company || !$company->office_start_time) {
            $startTime = Carbon::parse(config('attendance.default_office_start', '09:00'));
            $endTime   = Carbon::parse(config('attendance.default_office_end', '18:00'));
        } else {
            $startTime = Carbon::parse($company->office_start_time);
            $endTime   = Carbon::parse($company->office_end_time);
        }

        $startTime->setDate($time->year, $time->month, $time->day);
        $endTime->setDate($time->year, $time->month, $time->day);

        return $time->between($startTime, $endTime);
    }

    public function calculateLateMinutes(Employee $employee, Carbon $checkInTime): int
    {
        $company      = $employee->company;
        $graceMinutes = (int) config('attendance.late_grace_period', 15);

        if (!$company || !$company->office_start_time) {
            $scheduledStart = Carbon::parse(config('attendance.default_office_start', '09:00'));
        } else {
            $scheduledStart = Carbon::parse($company->office_start_time);
        }

        $scheduledStart->setDate($checkInTime->year, $checkInTime->month, $checkInTime->day);
        $scheduledStartWithGrace = $scheduledStart->copy()->addMinutes($graceMinutes);

        if ($checkInTime->lte($scheduledStartWithGrace)) {
            return 0;
        }

        return (int) abs($checkInTime->diffInMinutes($scheduledStart));
    }

    public function calculateEarlyLeaveMinutes(Employee $employee, Carbon $checkOutTime): int
    {
        $company      = $employee->company;
        $graceMinutes = (int) config('attendance.early_leave_grace_period', 15);

        if (!$company || !$company->office_end_time) {
            $scheduledEnd = Carbon::parse(config('attendance.default_office_end', '18:00'));
        } else {
            $scheduledEnd = Carbon::parse($company->office_end_time);
        }

        $scheduledEnd->setDate($checkOutTime->year, $checkOutTime->month, $checkOutTime->day);
        $scheduledEndWithGrace = $scheduledEnd->copy()->subMinutes($graceMinutes);

        if ($checkOutTime->gte($scheduledEndWithGrace)) {
            return 0;
        }

        return (int) abs($scheduledEnd->diffInMinutes($checkOutTime));
    }
}
