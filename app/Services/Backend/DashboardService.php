<?php

namespace App\Services\Backend;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceSummary;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getData(?int $companyId = null): array
    {
        $today = today();

        return [
            'stats'                => $this->getStats($companyId, $today),
            'monthlyAttendance'    => $this->getMonthlyAttendance($companyId, $today),
            'departmentAttendance' => $this->getDepartmentAttendance($companyId, $today),
            'recentAttendance'     => $this->getRecentAttendance($companyId, $today),
            'pendingLeaves'        => $this->getPendingLeaves($companyId),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Stats
    // ═══════════════════════════════════════════════════════════════

    private function getStats(?int $companyId, $today): array
    {
        return [
            'total_employees' => $this->countActiveEmployees($companyId),
            'present'         => $this->countTodayByStatuses($companyId, $today, self::presentStatuses()),
            'absent'          => $this->countTodayByStatus($companyId, $today, AttendanceStatus::Absent),
            'on_leave'        => $this->countTodayByStatus($companyId, $today, AttendanceStatus::Leave),
            'pending_leaves'  => $this->countPendingLeaves($companyId),
            'avg_hours'       => $this->averageWorkingHours($companyId, $today),
        ];
    }

    private function countActiveEmployees(?int $companyId): int
    {
        return Employee::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', true)
            ->count();
    }

    private function countTodayByStatuses(?int $companyId, $today, array $statuses): int
    {
        return $this->todayAttendanceQuery($companyId, $today)
            ->whereIn('status', $statuses)
            ->count();
    }

    private function countTodayByStatus(?int $companyId, $today, AttendanceStatus $status): int
    {
        return $this->todayAttendanceQuery($companyId, $today)
            ->where('status', $status)
            ->count();
    }

    private function countPendingLeaves(?int $companyId): int
    {
        return LeaveRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', self::pendingLeaveStatuses())
            ->count();
    }

    private function averageWorkingHours(?int $companyId, $today): float
    {
        $avg = $this->todayAttendanceQuery($companyId, $today)->avg('total_working_minutes');

        return $avg ? round($avg / 60, 1) : 0;
    }

    // ═══════════════════════════════════════════════════════════════
    // Monthly Attendance
    // ═══════════════════════════════════════════════════════════════

    private function getMonthlyAttendance(?int $companyId, $today): array
    {
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        $grouped = $this->getGroupedMonthlyRecords($companyId, $startOfMonth, $endOfMonth);

        return $this->buildMonthlyChart($startOfMonth, $endOfMonth, $grouped);
    }

    private function getGroupedMonthlyRecords(?int $companyId, $startOfMonth, $endOfMonth)
    {
        return AttendanceSummary::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->select('attendance_date', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->attendance_date)->format('Y-m-d'));
    }

    private function buildMonthlyChart($startOfMonth, $endOfMonth, $grouped): array
    {
        $result = [];
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            $dayRecords = $grouped->get($current->format('Y-m-d'), collect());

            $result[] = [
                'date'    => $current->format('d M'),
                'present' => $dayRecords->whereIn('status', self::presentStatuses())->sum('count'),
                'absent'  => $dayRecords->where('status', AttendanceStatus::Absent)->sum('count'),
                'late'    => $dayRecords->where('status', AttendanceStatus::Late)->sum('count'),
                'leave'   => $dayRecords->where('status', AttendanceStatus::Leave)->sum('count'),
            ];

            $current->addDay();
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════════
    // Department Attendance
    // ═══════════════════════════════════════════════════════════════

    private function getDepartmentAttendance(?int $companyId, $today): array
    {
        $departments = $this->getActiveDepartments($companyId);
        $attendance = $this->getDepartmentAttendanceRecords($companyId, $today);

        return $departments->map(fn($dept) => $this->formatDepartmentAttendance($dept, $attendance))
            ->toArray();
    }

    private function getActiveDepartments(?int $companyId)
    {
        return Department::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', true)
            ->get(['id', 'name']);
    }

    private function getDepartmentAttendanceRecords(?int $companyId, $today)
    {
        return AttendanceSummary::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereDate('attendance_date', $today)
            ->select('department_id', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id', 'status')
            ->get();
    }

    private function formatDepartmentAttendance($dept, $attendance): array
    {
        $deptAttendance = $attendance->where('department_id', $dept->id);

        return [
            'department' => $dept->name,
            'present'    => $deptAttendance->whereIn('status', self::presentStatuses())->sum('count'),
            'absent'     => $deptAttendance->where('status', AttendanceStatus::Absent)->sum('count'),
            'late'       => $deptAttendance->where('status', AttendanceStatus::Late)->sum('count'),
            'leave'      => $deptAttendance->where('status', AttendanceStatus::Leave)->sum('count'),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Recent Attendance
    // ═══════════════════════════════════════════════════════════════

    private function getRecentAttendance(?int $companyId, $today): array
    {
        return $this->todayAttendanceQuery($companyId, $today)
            ->with(['employee:id,first_name,last_name,id_no', 'employee.media'])
            ->orderBy('first_check_in', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => $this->formatRecentAttendanceItem($item))
            ->toArray();
    }

    private function formatRecentAttendanceItem($item): array
    {
        return [
            'employee_name' => $item->employee
                ? $item->employee->first_name . ' ' . $item->employee->last_name
                : '-',
            'avatar_url'    => $item->employee?->getFirstMediaUrl('avatar') ?: null,
            'emp_id'        => data_get($item, 'employee.id_no', '-'),
            'check_in'      => $item->first_check_in
                ? Carbon::parse($item->first_check_in)->format('g:i a')
                : '--:--',
            'check_out'     => $item->last_check_out
                ? Carbon::parse($item->last_check_out)->format('g:i a')
                : '--:--',
            'working_hours' => $item->total_working_minutes
                ? sprintf('%dh %dm', floor($item->total_working_minutes / 60), $item->total_working_minutes % 60)
                : '0h 0m',
            'status'        => AttendanceStatus::labelFor($item->status),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Pending Leaves
    // ═══════════════════════════════════════════════════════════════

    private function getPendingLeaves(?int $companyId): array
    {
        return LeaveRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', self::pendingLeaveStatuses())
            ->with([
                'employee:id,first_name,last_name',
                'employee.media',
                'leaveType:id,name',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => $this->formatPendingLeaveItem($item))
            ->toArray();
    }

    private function formatPendingLeaveItem($item): array
    {
        return [
            'id'            => $item->id,
            'avatar_url'    => $item->employee?->getFirstMediaUrl('avatar') ?: null,
            'employee_name' => $item->employee
                ? $item->employee->first_name . ' ' . $item->employee->last_name
                : '-',
            'leave_type'    => data_get($item, 'leaveType.name', '-'),
            'total_days'    => $item->total_days,
            'started_at'    => $item->started_at->format('d M Y'),
            'status'        => $item->status->value,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Query Helpers
    // ═══════════════════════════════════════════════════════════════

    private function todayAttendanceQuery(?int $companyId, $today)
    {
        return AttendanceSummary::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereDate('attendance_date', $today);
    }

    private static function presentStatuses(): array
    {
        return [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome];
    }

    private static function pendingLeaveStatuses(): array
    {
        return [LeaveRequestStatus::Pending, LeaveRequestStatus::InReview];
    }
}
