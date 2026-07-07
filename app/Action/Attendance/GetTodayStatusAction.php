<?php

namespace App\Action\Attendance;

use App\Http\Resources\Api\AttendanceSessionResource;
use App\Http\Resources\Api\AttendanceSummaryResource;
use App\Models\AttendanceSession;
use App\Models\AttendanceSummary;
use App\Models\Employee;

class GetTodayStatusAction
{
    /**
     * Today's sessions plus the daily summary for the mobile home screen.
     * Read-only, so it queries the models directly (no shared service).
     */
    public function execute(Employee $employee): array
    {
        $today = today();

        $sessions = AttendanceSession::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->orderBy('session_number')
            ->get();

        $summary = AttendanceSummary::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        return [
            'date'     => $today->toDateString(),
            'sessions' => AttendanceSessionResource::collection($sessions),
            'summary'  => $summary ? new AttendanceSummaryResource($summary) : null,
        ];
    }
}
