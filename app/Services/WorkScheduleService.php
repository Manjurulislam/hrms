<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyWorkingDay;
use App\Models\Employee;
use App\Traits\CompanySettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkScheduleService
{
    use CompanySettings;

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

        $startTime = Carbon::parse($this->companySetting($company, 'office_start'));
        $endTime   = Carbon::parse($this->companySetting($company, 'office_end'));

        $startTime->setDate($time->year, $time->month, $time->day);
        $endTime->setDate($time->year, $time->month, $time->day);

        return $time->between($startTime, $endTime);
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
