<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'session_number'     => $this->session_number,
            'attendance_date'    => optional($this->attendance_date)->toDateString(),
            'session_type'       => $this->session_type?->value,
            'status'             => $this->status?->value,
            'check_in_time'      => optional($this->check_in_time)->toIso8601String(),
            'check_in_location'  => $this->check_in_location,
            'check_in_lat'       => $this->check_in_lat,
            'check_in_long'      => $this->check_in_long,
            'check_in_note'      => $this->check_in_note,
            'check_out_time'     => optional($this->check_out_time)->toIso8601String(),
            'check_out_location' => $this->check_out_location,
            'check_out_lat'      => $this->check_out_lat,
            'check_out_long'     => $this->check_out_long,
            'check_out_note'     => $this->check_out_note,
            'duration_minutes'   => $this->duration_minutes,
            'is_late'            => $this->is_late,
            'is_early_departure' => $this->is_early_departure,
            'is_overtime'        => $this->is_overtime,
        ];
    }
}
