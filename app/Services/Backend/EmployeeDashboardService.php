<?php

namespace App\Services\Backend;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;

class EmployeeDashboardService
{
    public function getData(Employee $employee): array
    {
        return [
            'stats'             => $this->getStats($employee),
            'monthlyAttendance' => $this->getMonthlyAttendance($employee),
            'leaveBalances'     => $this->getLeaveBalances($employee),
            'recentLeaves'      => $this->getRecentLeaves($employee),
            'weeklyHours'       => $this->getWeeklyHours($employee),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Stats
    // ═══════════════════════════════════════════════════════════════

    private function getStats(Employee $employee): array
    {
        $monthAttendance = $this->getCurrentMonthAttendance($employee);

        $presentCount = $monthAttendance->whereIn('status', self::presentStatuses())->count();
        $totalWorkingDays = $monthAttendance->where('is_working_day', true)->count();

        return [
            'present'         => $presentCount,
            'absent'          => $monthAttendance->where('status', AttendanceStatus::Absent)->count(),
            'late'            => $monthAttendance->where('status', AttendanceStatus::Late)->count(),
            'attendance_rate' => $this->calculateRate($presentCount, $totalWorkingDays),
            'avg_hours'       => $this->calculateAvgHours($monthAttendance),
            'pending_leaves'  => $this->countPendingLeaves($employee),
        ];
    }

    private function calculateRate(int $numerator, int $denominator): int
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100) : 0;
    }

    private function calculateAvgHours($records): float
    {
        return $records->count() > 0
            ? round($records->avg('total_working_minutes') / 60, 1)
            : 0;
    }

    private function countPendingLeaves(Employee $employee): int
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::InReview])
            ->count();
    }

    // ═══════════════════════════════════════════════════════════════
    // Monthly Attendance
    // ═══════════════════════════════════════════════════════════════

    private function getMonthlyAttendance(Employee $employee): array
    {
        return $this->getCurrentMonthAttendance($employee)
            ->map(fn($item) => [
                'date'   => Carbon::parse($item->attendance_date)->format('d'),
                'hours'  => round($item->total_working_minutes / 60, 1),
                'status' => AttendanceStatus::labelFor($item->status),
            ])
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════════
    // Leave Balances
    // ═══════════════════════════════════════════════════════════════

    private function getLeaveBalances(Employee $employee): array
    {
        $year = now()->year;
        $leaveTypes = $this->getActiveLeaveTypes($employee);

        return $leaveTypes->map(function ($type) use ($employee, $year) {
            $balance = $this->getOrCreateBalance($employee, $type, $year);

            return [
                'name'      => $type->name,
                'total'     => $balance->total,
                'used'      => $balance->used,
                'remaining' => $balance->remaining,
            ];
        })->toArray();
    }

    private function getActiveLeaveTypes(Employee $employee)
    {
        return LeaveType::where('company_id', $employee->company_id)
            ->where('status', true)
            ->get();
    }

    private function getOrCreateBalance(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            [
                'employee_id'   => $employee->id,
                'leave_type_id' => $type->id,
                'year'          => $year,
            ],
            [
                'total' => $type->max_per_year,
                'used'  => 0,
            ]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // Recent Leaves
    // ═══════════════════════════════════════════════════════════════

    private function getRecentLeaves(Employee $employee): array
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->with('leaveType:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id'         => $item->id,
                'leave_type' => data_get($item, 'leaveType.name', '-'),
                'started_at' => $item->started_at->format('d M Y'),
                'ended_at'   => $item->ended_at->format('d M Y'),
                'total_days' => $item->total_days,
                'status'     => $item->status->value,
            ])
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════════
    // Weekly Hours
    // ═══════════════════════════════════════════════════════════════

    private function getWeeklyHours(Employee $employee): array
    {
        return AttendanceSummary::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderBy('attendance_date', 'asc')
            ->get()
            ->map(fn($item) => [
                'day'   => Carbon::parse($item->attendance_date)->format('D'),
                'hours' => round($item->total_working_minutes / 60, 1),
            ])
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════════
    // Query Helpers
    // ═══════════════════════════════════════════════════════════════

    private function getCurrentMonthAttendance(Employee $employee)
    {
        $now = now();

        return AttendanceSummary::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $now->year)
            ->whereMonth('attendance_date', $now->month)
            ->orderBy('attendance_date', 'asc')
            ->get();
    }

    private static function presentStatuses(): array
    {
        return [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome];
    }
}
