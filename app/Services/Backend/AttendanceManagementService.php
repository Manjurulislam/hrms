<?php

namespace App\Services\Backend;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceSummary;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class AttendanceManagementService
{
    use PaginateQuery, QueryParams;

    public function list(Request $request): array
    {
        $query = AttendanceSummary::query()
            ->with(['employee:id,first_name,last_name,id_no', 'employee.media', 'department:id,name'])
            ->orderBy('attendance_date', 'desc');

        $query = $this->attendanceQuery($query, $request);

        return $this->transformAttendanceWithSessions($query, $request->integer('per_page', 50));
    }

    public function employeeAttendance(Request $request): array
    {
        $query = AttendanceSummary::query()
            ->where('employee_id', $request->input('employee_id'))
            ->when($request->filled('company_id'), fn($q) => $q->where('company_id', $request->input('company_id')))
            ->orderBy('attendance_date', 'desc');

        $this->applyMonthFilter($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $data  = $this->transformAttendanceWithSessions($query, $request->integer('per_page', 50));
        $stats = $this->computeStats(collect($data['data']));

        return array_merge($data, ['stats' => $stats]);
    }

    private function applyMonthFilter($query, Request $request): void
    {
        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->input('month'));
            $query->whereYear('attendance_date', $year)->whereMonth('attendance_date', $month);
        } else {
            $query->whereYear('attendance_date', now()->year)->whereMonth('attendance_date', now()->month);
        }
    }

    private function computeStats($records): array
    {
        $statusCounts = collect($records)->countBy(fn($r) => data_get($r, 'status') instanceof AttendanceStatus
            ? data_get($r, 'status')->value
            : (string) data_get($r, 'status'));

        $presentStatuses = [AttendanceStatus::Present->value, AttendanceStatus::Late->value, AttendanceStatus::WorkFromHome->value];
        $presentCount = collect($presentStatuses)->sum(fn($s) => $statusCounts->get($s, 0));

        $totalMinutes = collect($records)->sum(fn($r) => data_get($r, 'total_working_minutes', 0));
        $count = collect($records)->count();

        return [
            'present'   => $presentCount,
            'absent'    => $statusCounts->get(AttendanceStatus::Absent->value, 0),
            'late'      => $statusCounts->get(AttendanceStatus::Late->value, 0),
            'half_day'  => $statusCounts->get(AttendanceStatus::HalfDay->value, 0),
            'wfh'       => $statusCounts->get(AttendanceStatus::WorkFromHome->value, 0),
            'avg_hours' => $count > 0 ? round($totalMinutes / $count / 60, 1) : 0,
        ];
    }
}
