<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyWorkingDay;
use App\Models\Employee;
use App\Models\Holiday;
use App\Traits\CompanySettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkScheduleService
{
    use CompanySettings;

    public function isWorkingDay(Employee $employee, Carbon $date): bool
    {
        $company = $employee->company;

        // An active holiday is never a working day. Employees may still work on it,
        // but that work is treated as overtime (extra work), not a required day.
        if ($this->isHoliday($employee, $date)) {
            return false;
        }

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

    // Whether the given date falls within an active holiday for the employee's company
    public function isHoliday(Employee $employee, Carbon $date): bool
    {
        if (!$employee->company_id) {
            return false;
        }

        return Holiday::where('company_id', $employee->company_id)
            ->where('status', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
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
                'work_start_time' => $this->companySetting(null, 'office_start'),
                'work_end_time'   => $this->companySetting(null, 'office_end'),
                'working_days'    => [1, 2, 3, 4, 5],
                'weekend_days'    => [0, 6],
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
            'work_start_time' => $this->companySetting($company, 'office_start'),
            'work_end_time'   => $this->companySetting($company, 'office_end'),
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
        $current     = $startDate->copy();

        while ($current <= $endDate) {
            if ($this->isWorkingDay($employee, $current)) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    // Check-in window: opens at check_in_open (early morning, before office start)
    // and closes at office_end. Check-in is blocked after office hours and overnight
    // until check_in_open the next morning.
    public function isWithinCheckInWindow(Employee $employee, Carbon $time = null): bool
    {
        $time    = $time ?? now();
        $company = $employee->company;

        $openTime = Carbon::parse($this->companySetting($company, 'check_in_open'));
        $endTime  = Carbon::parse($this->companySetting($company, 'office_end'));

        $openTime->setDate($time->year, $time->month, $time->day);
        $endTime->setDate($time->year, $time->month, $time->day);

        return $time->between($openTime, $endTime);
    }

    public function calculateLateMinutes(Employee $employee, Carbon $checkInTime): int
    {
        $company        = $employee->company;
        $graceMinutes   = $this->companySetting($company, 'late_grace');
        $scheduledStart = Carbon::parse($this->companySetting($company, 'office_start'));

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
        $graceMinutes = $this->companySetting($company, 'early_grace');
        $scheduledEnd = Carbon::parse($this->companySetting($company, 'office_end'));

        $scheduledEnd->setDate($checkOutTime->year, $checkOutTime->month, $checkOutTime->day);
        $scheduledEndWithGrace = $scheduledEnd->copy()->subMinutes($graceMinutes);

        if ($checkOutTime->gte($scheduledEndWithGrace)) {
            return 0;
        }

        return (int) abs($scheduledEnd->diffInMinutes($checkOutTime));
    }
}
