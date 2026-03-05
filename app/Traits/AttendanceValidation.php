<?php

namespace App\Traits;

use App\Enums\BreakStatus;
use App\Enums\SessionStatus;
use App\Models\AttendanceBreak;
use App\Models\AttendanceSession;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Services\WorkScheduleService;
use Carbon\Carbon;

trait AttendanceValidation
{
    protected function getEmployee(): ?Employee
    {
        return $this->user()?->employee;
    }

    protected function findActiveSession(Employee $employee): ?AttendanceSession
    {
        return AttendanceSession::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->where('status', SessionStatus::Active)
            ->first();
    }

    protected function findActiveBreak(Employee $employee): ?AttendanceBreak
    {
        return AttendanceBreak::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->where('status', BreakStatus::Active)
            ->first();
    }

    protected function findLastCompletedSession(Employee $employee): ?AttendanceSession
    {
        return AttendanceSession::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->where('status', SessionStatus::Completed)
            ->latest('check_out_time')
            ->first();
    }

    protected function getTodaySummary(Employee $employee): ?AttendanceSummary
    {
        return AttendanceSummary::where('employee_id', $employee->id)
            ->where('attendance_date', today())
            ->first();
    }

    protected function getTodaySessionCount(Employee $employee): int
    {
        return AttendanceSession::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->count();
    }

    protected function getTodayBreakCount(Employee $employee): int
    {
        return AttendanceBreak::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->count();
    }

    protected function isWithinOfficeHours(Employee $employee): bool
    {
        return app(WorkScheduleService::class)->isWithinOfficeHours($employee);
    }

    protected function getOfficeTimeRange(Employee $employee): array
    {
        $company = $employee->company;

        $start = $company?->office_start_time
            ? Carbon::parse($company->office_start_time)->format('g:i A')
            : config('attendance.default_office_start', '9:00 AM');

        $end = $company?->office_end_time
            ? Carbon::parse($company->office_end_time)->format('g:i A')
            : config('attendance.default_office_end', '6:00 PM');

        return ['start' => $start, 'end' => $end];
    }

    protected function getTotalOfficeMinutes(Employee $employee): int
    {
        $schedule = app(WorkScheduleService::class)->getEmployeeSchedule($employee);
        $start = Carbon::parse($schedule['work_start_time']);
        $end = Carbon::parse($schedule['work_end_time']);

        return (int) abs($start->diffInMinutes($end));
    }

    protected function hasCompletedOfficeHours(Employee $employee): bool
    {
        $summary = $this->getTodaySummary($employee);

        if (!$summary) {
            return false;
        }

        return $summary->total_working_minutes >= $this->getTotalOfficeMinutes($employee);
    }
}
