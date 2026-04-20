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
        $user = Auth::user();

        if ($request->filled('employee_id') && $this->canQueryOthers($user)) {
            return $request->integer('employee_id');
        }

        return $user?->employee?->id;
    }

    protected function canQueryOthers(?\App\Models\User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach (['super_admin', 'admin', 'hr'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
