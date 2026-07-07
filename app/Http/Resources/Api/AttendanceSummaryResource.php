<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'attendance_date'       => optional($this->attendance_date)->toDateString(),
            'is_working_day'        => $this->is_working_day,
            'first_check_in'        => $this->first_check_in,
            'last_check_out'        => $this->last_check_out,
            'total_sessions'        => $this->total_sessions,
            'total_working_minutes' => $this->total_working_minutes,
            'total_break_minutes'   => $this->total_break_minutes,
            'overtime_minutes'      => $this->overtime_minutes,
            'late_minutes'          => $this->late_minutes,
            'early_leave_minutes'   => $this->early_leave_minutes,
        ];
    }
}
