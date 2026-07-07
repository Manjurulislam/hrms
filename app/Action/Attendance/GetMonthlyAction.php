<?php

namespace App\Action\Attendance;

use App\Http\Resources\Api\AttendanceSummaryResource;
use App\Models\AttendanceSummary;
use App\Models\Employee;

class GetMonthlyAction
{
    /**
     * Per-day summaries for a given month plus simple roll-up totals.
     */
    public function execute(Employee $employee, int $year, int $month): array
    {
        $summaries = AttendanceSummary::forEmployee($employee->id)
            ->forMonth($year, $month)
            ->orderBy('attendance_date')
            ->get();

        return [
            'month'  => sprintf('%04d-%02d', $year, $month),
            'totals' => [
                'present_days'          => $summaries->where('total_sessions', '>', 0)->count(),
                'total_working_minutes' => (int) $summaries->sum('total_working_minutes'),
                'overtime_minutes'      => (int) $summaries->sum('overtime_minutes'),
                'late_minutes'          => (int) $summaries->sum('late_minutes'),
            ],
            'days'   => AttendanceSummaryResource::collection($summaries),
        ];
    }
}
