<?php

namespace App\Services\Backend;

use App\Models\AttendanceSummary;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class CompanyAttendanceService
{
    use PaginateQuery, QueryParams;

    public function list(Request $request): array
    {
        $query = AttendanceSummary::query()
            ->with(['employee:id,first_name,last_name,id_no', 'department:id,name'])
            ->orderBy('attendance_date', 'desc');

        $query = $this->attendanceQuery($query, $request);

        return $this->transformAttendance($query, $request->integer('per_page', 50));
    }

    public function employeeAttendance(Request $request): array
    {
        $query = AttendanceSummary::query()
            ->where('employee_id', $request->input('employee_id'))
            ->where('company_id', $request->input('company_id'))
            ->orderBy('attendance_date', 'desc');

        // Filter by month (default current)
        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->input('month'));
            $query->whereYear('attendance_date', $year)->whereMonth('attendance_date', $month);
        } else {
            $query->whereYear('attendance_date', now()->year)->whereMonth('attendance_date', now()->month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $records = $query->get();

        // Compute summary stats
        $stats = [
            'present'   => $records->whereIn('status', ['present', 'late', 'work_from_home'])->count(),
            'absent'    => $records->where('status', 'absent')->count(),
            'late'      => $records->where('status', 'late')->count(),
            'half_day'  => $records->where('status', 'half_day')->count(),
            'wfh'       => $records->where('status', 'work_from_home')->count(),
            'avg_hours' => $records->count() > 0
                ? round($records->avg('total_working_minutes') / 60, 1)
                : 0,
        ];

        // Transform for display
        $data = $this->transformAttendance($query, $request->integer('per_page', 50));

        return array_merge($data, ['stats' => $stats]);
    }
}
