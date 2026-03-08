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

    private function getStats(?int $companyId, $today): array
    {
        $totalEmployees = Employee::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', true)
            ->count();

        $todayAttendance = AttendanceSummary::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereDate('attendance_date', $today)
            ->get();

        $presentCount = $todayAttendance->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome])->count();
        $absentCount = $todayAttendance->where('status', AttendanceStatus::Absent)->count();
        $onLeaveCount = $todayAttendance->where('status', AttendanceStatus::Leave)->count();

        $avgWorkingHours = $todayAttendance->count() > 0
            ? round($todayAttendance->avg('total_working_minutes') / 60, 1)
            : 0;

        $pendingLeaves = LeaveRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::InReview])
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'present'         => $presentCount,
            'absent'          => $absentCount,
            'on_leave'        => $onLeaveCount,
            'pending_leaves'  => $pendingLeaves,
            'avg_hours'       => $avgWorkingHours,
        ];
    }

    private function getMonthlyAttendance(?int $companyId, $today): array
    {
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        $records = AttendanceSummary::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->select('attendance_date', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get();

        $grouped = $records->groupBy(fn($item) => Carbon::parse($item->attendance_date)->format('Y-m-d'));

        $result = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $dayRecords = $grouped->get($key, collect());

            $result[] = [
                'date'    => $date->format('d M'),
                'present' => $dayRecords->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome])->sum('count'),
                'absent'  => $dayRecords->where('status', AttendanceStatus::Absent)->sum('count'),
                'late'    => $dayRecords->where('status', AttendanceStatus::Late)->sum('count'),
                'leave'   => $dayRecords->where('status', AttendanceStatus::Leave)->sum('count'),
            ];
        }

        return $result;
    }

    private function getDepartmentAttendance(?int $companyId, $today): array
    {
        $departments = Department::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', true)
            ->get(['id', 'name']);

        $attendance = AttendanceSummary::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereDate('attendance_date', $today)
            ->select('department_id', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id', 'status')
            ->get();

        $result = [];
        foreach ($departments as $dept) {
            $deptAttendance = $attendance->where('department_id', $dept->id);
            $result[] = [
                'department' => $dept->name,
                'present'    => $deptAttendance->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome])->sum('count'),
                'absent'     => $deptAttendance->where('status', AttendanceStatus::Absent)->sum('count'),
                'late'       => $deptAttendance->where('status', AttendanceStatus::Late)->sum('count'),
                'leave'      => $deptAttendance->where('status', AttendanceStatus::Leave)->sum('count'),
            ];
        }

        return $result;
    }

    private function getRecentAttendance(?int $companyId, $today): array
    {
        return AttendanceSummary::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereDate('attendance_date', $today)
            ->with(['employee:id,first_name,last_name,id_no', 'employee.media'])
            ->orderBy('first_check_in', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'employee_name' => $item->employee
                    ? $item->employee->first_name . ' ' . $item->employee->last_name
                    : '-',
                'avatar_url'    => $item->employee?->getFirstMediaUrl('avatar') ?: null,
                'emp_id'        => $item->employee?->id_no ?? '-',
                'check_in'      => $item->first_check_in
                    ? Carbon::parse($item->first_check_in)->format('g:i a')
                    : '--:--',
                'check_out'     => $item->last_check_out
                    ? Carbon::parse($item->last_check_out)->format('g:i a')
                    : '--:--',
                'working_hours' => $item->total_working_minutes
                    ? sprintf('%dh %dm', floor($item->total_working_minutes / 60), $item->total_working_minutes % 60)
                    : '0h 0m',
                'status'        => $this->getStatusLabel($item->status),
            ])
            ->toArray();
    }

    private function getPendingLeaves(?int $companyId): array
    {
        return LeaveRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::InReview])
            ->with([
                'employee:id,first_name,last_name',
                'employee.media',
                'leaveType:id,name',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id'            => $item->id,
                'avatar_url'    => $item->employee?->getFirstMediaUrl('avatar') ?: null,
                'employee_name' => $item->employee
                    ? $item->employee->first_name . ' ' . $item->employee->last_name
                    : '-',
                'leave_type'    => $item->leaveType?->name ?? '-',
                'total_days'    => $item->total_days,
                'started_at'    => $item->started_at->format('d M Y'),
                'status'        => $item->status->value,
            ])
            ->toArray();
    }

    private function getStatusLabel($status): string
    {
        $labels = [
            'present'        => 'Present',
            'absent'         => 'Absent',
            'late'           => 'Late',
            'half_day'       => 'Half Day',
            'leave'          => 'Leave',
            'holiday'        => 'Holiday',
            'weekend'        => 'Weekend',
            'work_from_home' => 'WFH',
        ];

        $key = $status instanceof \BackedEnum ? $status->value : $status;

        return $labels[$key] ?? $key;
    }
}
