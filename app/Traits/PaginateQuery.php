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

            $start = data_get($item, 'schedule.work_start_time');
            $end   = data_get($item, 'schedule.work_end_time');


            return array_merge($item->toArray(), [
                'name'        => $item->name,
                'description' => $item->description,
                'company'     => $item->company->name,
                'start'       => $start ? Carbon::parse($start)->format('H:i A') : '',
                'ended'       => $end ? Carbon::parse($end)->format('H:i A') : '',
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
}
