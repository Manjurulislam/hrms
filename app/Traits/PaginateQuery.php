<?php

namespace App\Traits;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceSession;
use Carbon\Carbon;

trait PaginateQuery
{
    public function paginateOrFetchAll($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            return ['total' => $items->count(), 'data' => $items];
        }

        $pagination = $query->paginate($rows);

        return ['total' => $pagination->total(), 'data' => $pagination->items()];
    }

    public function paginateAndTransform($query, $rows, ?callable $transformer = null): array
    {
        $result = $this->paginateOrFetchAll($query, $rows);

        if ($transformer) {
            $result['data'] = collect($result['data'])->map($transformer);
        }

        return $result;
    }

    public function transformRole($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, fn($item) => array_merge($item->toArray(), [
            'name'         => $item->name,
            'display_name' => $item->display_name,
            'description'  => $item->description,
            'role_type'    => $item->role_type->label(),
            'status'       => $item->status,
        ]));
    }

    public function transformUsers($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, fn($item) => array_merge($item->toArray(), [
            'name'   => $item->name,
            'email'  => $item->email,
            'status' => $item->status,
            'roles'  => $item->roles->pluck('name'),
        ]));
    }

    public function transformCustomers($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, fn($item) => array_merge($item->toArray(), [
            'name'   => $item->full_name,
            'email'  => $item->email,
            'phone'  => $item->phone,
            'status' => $item->status,
        ]));
    }

    public function transformDepartment($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, fn($item) => array_merge($item->toArray(), [
            'name'        => $item->name,
            'description' => $item->description,
            'company'     => $item->company->name,
            'status'      => $item->status,
        ]));
    }

    public function transformEvents($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, fn($item) => array_merge($item->toArray(), [
            'title'      => $item->title,
            'capacity'   => $item->max_capacity,
            'booked'     => $item->current_bookings,
            'registered' => $item->current_reg,
            'status'     => $item->status,
            'roles'      => $item->roles->pluck('name')->implode(', '),
        ]));
    }

    public function transformLeaveRequests($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, function ($item) {
            $arr = $item->toArray();
            if ($item->employee) {
                $arr['employee']['avatar_url'] = $item->employee->getFirstMediaUrl('avatar') ?: null;
            }
            return $arr;
        });
    }

    public function transformEmployees($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, fn($item) => array_merge($item->toArray(), [
            'avatar_url' => $item->getFirstMediaUrl('avatar') ?: null,
        ]));
    }

    public function transformAttendance($query, $rows): array
    {
        return $this->paginateAndTransform($query, $rows, function ($item) {
            $date = Carbon::parse($item->attendance_date);

            return array_merge($item->toArray(), [
                'attendance_date_display' => $date->format('d M Y'),
                'day'                     => $date->format('D'),
                'first_check_in_display'  => $item->first_check_in
                    ? Carbon::parse($item->first_check_in)->format('g:i a')
                    : '--:--',
                'last_check_out_display'  => $item->last_check_out
                    ? Carbon::parse($item->last_check_out)->format('g:i a')
                    : '--:--',
                'working_hours'  => self::formatMinutesToHours($item->total_working_minutes),
                'break_hours'    => self::formatMinutesToHours($item->total_break_minutes),
                'total_sessions' => $item->total_sessions ?? 0,
                'status_label'   => AttendanceStatus::labelFor($item->status),
            ]);
        });
    }

    public function transformAttendanceWithSessions($query, $rows): array
    {
        $result     = $this->paginateOrFetchAll($query, $rows);
        $collection = collect($result['data']);
        $sessions   = $this->loadSessionsWithBreaks($collection);

        $result['data'] = $collection->map(function ($item) use ($sessions) {
            $date = Carbon::parse($item->attendance_date);
            $key  = $item->employee_id . '_' . $date->toDateString();

            return array_merge($item->toArray(), [
                'avatar_url'              => $item->employee?->getFirstMediaUrl('avatar') ?: null,
                'attendance_date_display' => $date->format('d M Y'),
                'day'                     => $date->format('D'),
                'first_check_in_display'  => $item->first_check_in
                    ? Carbon::parse($item->first_check_in)->format('g:i a')
                    : '--:--',
                'last_check_out_display'  => $item->last_check_out
                    ? Carbon::parse($item->last_check_out)->format('g:i a')
                    : '--:--',
                'working_hours'  => self::formatMinutesToHours($item->total_working_minutes),
                'break_hours'    => self::formatMinutesToHours($item->total_break_minutes),
                'total_sessions' => $item->total_sessions ?? 0,
                'status_label'   => AttendanceStatus::labelFor($item->status),
                'sessions'       => $sessions[$key] ?? [],
            ]);
        });

        return $result;
    }

    private function loadSessionsWithBreaks($summaries): array
    {
        if ($summaries->isEmpty()) {
            return [];
        }

        $employeeIds = $summaries->pluck('employee_id')->unique();
        $dates       = $summaries->pluck('attendance_date')->map(fn($d) => Carbon::parse($d)->toDateString())->unique();

        $sessions = AttendanceSession::with('breaks')
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('attendance_date', $dates)
            ->orderBy('session_number')
            ->get();

        $grouped = [];

        foreach ($sessions as $session) {
            $key = $session->employee_id . '_' . Carbon::parse($session->attendance_date)->toDateString();

            $grouped[$key][] = [
                'session_number' => $session->session_number,
                'check_in_time'  => $session->check_in_time?->format('g:i a') ?? '--:--',
                'check_out_time' => $session->check_out_time?->format('g:i a') ?? '--:--',
                'duration'       => self::formatMinutesToHours($session->duration_minutes),
                'status'         => $session->status instanceof \BackedEnum ? $session->status->value : $session->status,
                'breaks'         => $session->breaks->map(fn($b) => [
                    'break_type'  => $b->break_type instanceof \BackedEnum ? $b->break_type->value : $b->break_type,
                    'break_start' => $b->break_start?->format('g:i a') ?? '--:--',
                    'break_end'   => $b->break_end?->format('g:i a') ?? '--:--',
                    'duration'    => self::formatMinutesToHours($b->duration_minutes),
                    'reason'      => $b->reason,
                    'status'      => $b->status instanceof \BackedEnum ? $b->status->value : $b->status,
                ])->toArray(),
            ];
        }

        return $grouped;
    }

    public static function formatMinutesToHours($minutes): string
    {
        if (!$minutes) return '0h 0m';

        $hours = floor($minutes / 60);
        $mins  = $minutes % 60;

        return sprintf('%dh %dm', $hours, $mins);
    }
}
