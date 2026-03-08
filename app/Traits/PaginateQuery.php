<?php

namespace App\Traits;


use Carbon\Carbon;

trait PaginateQuery
{
    public function paginateOrFetchAll($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }
        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    public function transformRole($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            return array_merge($item->toArray(), [
                'name'         => $item->name,
                'display_name' => $item->display_name,
                'description'  => $item->description,
                'role_type'    => $item->role_type->label(),
                'status'       => $item->status,
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }


    public function transformUsers($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            return array_merge($item->toArray(), [
                'name'   => $item->name,
                'email'  => $item->email,
                'status' => $item->status,
                'roles'  => $item->roles->pluck('name'),
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    public function transformCustomers($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            return array_merge($item->toArray(), [
                'name'   => $item->full_name,
                'email'  => $item->email,
                'phone'  => $item->phone,
                'status' => $item->status,
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    public function transformDepartment($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            return array_merge($item->toArray(), [
                'name'        => $item->name,
                'description' => $item->description,
                'company'     => $item->company->name,
                'status'      => $item->status,
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    public function transformEvents($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            return array_merge($item->toArray(), [
                'title'      => $item->title,
                'capacity'   => $item->max_capacity,
                'booked'     => $item->current_bookings,
                'registered' => $item->current_reg,
                'status'     => $item->status,
                'roles'      => $item->roles->pluck('name')->implode(', '),
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    public function transformLeaveRequests($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            $arr = $item->toArray();
            if ($item->employee) {
                $arr['employee']['avatar_url'] = $item->employee->getFirstMediaUrl('avatar') ?: null;
            }
            return $arr;
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    public function transformEmployees($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            return array_merge($item->toArray(), [
                'avatar_url' => $item->getFirstMediaUrl('avatar') ?: null,
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    public function transformAttendance($query, $rows): array
    {
        if ($rows == -1) {
            $items = $query->get();
            $total = $items->count();
            $data  = $items;
        } else {
            $pagination = $query->paginate($rows);
            $total      = $pagination->total();
            $data       = $pagination->items();
        }

        $data = collect($data)->map(function ($item) {
            $date = Carbon::parse($item->attendance_date);

            return array_merge($item->toArray(), [
                'attendance_date_display' => $date->format('d M Y'),
                'day' => $date->format('D'),
                'first_check_in_display' => $item->first_check_in
                    ? Carbon::parse($item->first_check_in)->format('g:i a')
                    : '--:--',
                'last_check_out_display' => $item->last_check_out
                    ? Carbon::parse($item->last_check_out)->format('g:i a')
                    : '--:--',
                'working_hours' => $this->formatMinutesToHours($item->total_working_minutes),
                'break_hours' => $this->formatMinutesToHours($item->total_break_minutes),
                'total_sessions' => $item->total_sessions ?? 0,
                'status_label' => $this->getStatusLabel($item->status),
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    /**
     * Format minutes to hours
     */
    private function formatMinutesToHours($minutes)
    {
        if (!$minutes) return '0h 0m';
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%dh %dm', $hours, $mins);
    }

    /**
     * Get status label
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'half_day' => 'Half Day',
            'leave' => 'Leave',
            'holiday' => 'Holiday',
            'weekend' => 'Weekend',
            'work_from_home' => 'WFH'
        ];
        $key = $status instanceof \BackedEnum ? $status->value : $status;
        return $labels[$key] ?? $key;
    }
}
