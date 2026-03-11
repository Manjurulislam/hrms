<?php

namespace App\Services\Backend;

use App\Models\AttendanceSummary;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceRecordService
{
    use PaginateQuery, QueryParams;

    public function list(Request $request): array
    {
        $employeeId = $this->resolveEmployeeId($request);

        if (!$employeeId) {
            return ['total' => 0, 'data' => []];
        }

        $query = AttendanceSummary::query()
            ->where('employee_id', $employeeId)
            ->with(['employee:id,first_name,last_name,id_no'])
            ->orderBy('attendance_date', 'desc');

        $this->attendanceRecordQuery($query, $request);

        return $this->transformAttendanceWithSessions($query, $request->integer('per_page', 50));
    }

    public function resolveEmployeeId(Request $request): ?int
    {
        if ($request->filled('employee_id')) {
            return $request->integer('employee_id');
        }

        return Auth::user()?->employee?->id;
    }
}
