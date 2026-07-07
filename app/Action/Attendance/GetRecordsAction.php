<?php

namespace App\Action\Attendance;

use App\Http\Resources\Api\AttendanceSummaryResource;
use App\Models\AttendanceSummary;
use App\Models\Employee;

class GetRecordsAction
{
    /**
     * Daily summaries for the last N months (most recent first).
     */
    public function execute(Employee $employee, int $months): array
    {
        $months = max(1, min($months, 12));
        $from   = today()->startOfMonth()->subMonths($months - 1);

        $summaries = AttendanceSummary::forEmployee($employee->id)
            ->since($from)
            ->orderByDesc('attendance_date')
            ->get();

        return [
            'from'    => $from->toDateString(),
            'to'      => today()->toDateString(),
            'records' => AttendanceSummaryResource::collection($summaries),
        ];
    }
}
