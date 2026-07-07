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
     */
    public function execute(Employee $employee): array
    {
        $sessions = AttendanceSession::forEmployee($employee->id)
            ->today()
            ->orderBy('session_number')
            ->get();

        $summary = AttendanceSummary::forEmployee($employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        return [
            'date'     => today()->toDateString(),
            'sessions' => AttendanceSessionResource::collection($sessions),
            'summary'  => $summary ? new AttendanceSummaryResource($summary) : null,
        ];
    }
}
