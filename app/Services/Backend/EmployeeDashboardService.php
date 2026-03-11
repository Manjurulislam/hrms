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
            'stats'            => $this->getStats($employee),
            'monthlyAttendance' => $this->getMonthlyAttendance($employee),
            'leaveBalances'    => $this->getLeaveBalances($employee),
            'recentLeaves'     => $this->getRecentLeaves($employee),
            'weeklyHours'      => $this->getWeeklyHours($employee),
        ];
    }

    private function getStats(Employee $employee): array
    {
        $now = now();
        $monthAttendance = AttendanceSummary::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $now->year)
            ->whereMonth('attendance_date', $now->month)
            ->get();

        $presentCount = $monthAttendance->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome])->count();
        $absentCount = $monthAttendance->where('status', AttendanceStatus::Absent)->count();
        $lateCount = $monthAttendance->where('status', AttendanceStatus::Late)->count();
        $totalWorkingDays = $monthAttendance->where('is_working_day', true)->count();
        $attendanceRate = $totalWorkingDays > 0
            ? round(($presentCount / $totalWorkingDays) * 100)
            : 0;
        $avgHours = $monthAttendance->count() > 0
            ? round($monthAttendance->avg('total_working_minutes') / 60, 1)
            : 0;

        $pendingLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::InReview])
            ->count();

        return [
            'present'         => $presentCount,
            'absent'          => $absentCount,
            'late'            => $lateCount,
            'attendance_rate' => $attendanceRate,
            'avg_hours'       => $avgHours,
            'pending_leaves'  => $pendingLeaves,
        ];
    }

    private function getMonthlyAttendance(Employee $employee): array
    {
        $now = now();

        return AttendanceSummary::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $now->year)
            ->whereMonth('attendance_date', $now->month)
            ->orderBy('attendance_date', 'asc')
            ->get()
            ->map(fn($item) => [
                'date'   => Carbon::parse($item->attendance_date)->format('d'),
                'hours'  => round($item->total_working_minutes / 60, 1),
                'status' => AttendanceStatus::labelFor($item->status),
            ])
            ->toArray();
    }

    private function getLeaveBalances(Employee $employee): array
    {
        $year = now()->year;
        $leaveTypes = LeaveType::where('company_id', $employee->company_id)
            ->where('status', true)
            ->get();

        return $leaveTypes->map(function ($type) use ($employee, $year) {
            $balance = LeaveBalance::firstOrCreate(
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

            return [
                'name'      => $type->name,
                'total'     => $balance->total,
                'used'      => $balance->used,
                'remaining' => $balance->remaining,
            ];
        })->toArray();
    }

    private function getRecentLeaves(Employee $employee): array
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->with('leaveType:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id'         => $item->id,
                'leave_type' => $item->leaveType?->name ?? '-',
                'started_at' => $item->started_at->format('d M Y'),
                'ended_at'   => $item->ended_at->format('d M Y'),
                'total_days' => $item->total_days,
                'status'     => $item->status->value,
            ])
            ->toArray();
    }

    private function getWeeklyHours(Employee $employee): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return AttendanceSummary::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startOfWeek, $endOfWeek])
            ->orderBy('attendance_date', 'asc')
            ->get()
            ->map(fn($item) => [
                'day'   => Carbon::parse($item->attendance_date)->format('D'),
                'hours' => round($item->total_working_minutes / 60, 1),
            ])
            ->toArray();
    }

}
