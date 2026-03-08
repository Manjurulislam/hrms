<?php

namespace App\Services\Backend;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceSession;
use App\Models\AttendanceSummary;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceManagementService
{
    use PaginateQuery, QueryParams;

    public function list(Request $request): array
    {
        $query = AttendanceSummary::query()
            ->with(['employee:id,first_name,last_name,id_no', 'department:id,name'])
            ->orderBy('attendance_date', 'desc');

        $query = $this->attendanceQuery($query, $request);

        return $this->transformAttendance($query, $request->integer('per_page', 50));
    }

    public function employeeAttendance(Request $request): array
    {
        $query = AttendanceSummary::query()
            ->where('employee_id', $request->input('employee_id'))
            ->when($request->filled('company_id'), fn($q) => $q->where('company_id', $request->input('company_id')))
            ->orderBy('attendance_date', 'desc');

        // Filter by month (default current)
        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->input('month'));
            $query->whereYear('attendance_date', $year)->whereMonth('attendance_date', $month);
        } else {
            $query->whereYear('attendance_date', now()->year)->whereMonth('attendance_date', now()->month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $records = $query->get();

        // Compute summary stats
        $stats = [
            'present'   => $records->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome])->count(),
            'absent'    => $records->where('status', AttendanceStatus::Absent)->count(),
            'late'      => $records->where('status', AttendanceStatus::Late)->count(),
            'half_day'  => $records->where('status', AttendanceStatus::HalfDay)->count(),
            'wfh'       => $records->where('status', AttendanceStatus::WorkFromHome)->count(),
            'avg_hours' => $records->count() > 0
                ? round($records->avg('total_working_minutes') / 60, 1)
                : 0,
        ];

        // Transform for display
        $data = $this->transformAttendance($query, $request->integer('per_page', 50));

        return array_merge($data, ['stats' => $stats]);
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

        $collection = collect($data);
        $sessions   = $this->loadSessionsWithBreaks($collection);

        $data = $collection->map(function ($item) use ($sessions) {
            $date = Carbon::parse($item->attendance_date);
            $key  = $item->employee_id . '_' . $date->toDateString();

            return array_merge($item->toArray(), [
                'attendance_date_display' => $date->format('d M Y'),
                'day'                     => $date->format('D'),
                'first_check_in_display'  => $item->first_check_in
                    ? Carbon::parse($item->first_check_in)->format('g:i a')
                    : '--:--',
                'last_check_out_display'  => $item->last_check_out
                    ? Carbon::parse($item->last_check_out)->format('g:i a')
                    : '--:--',
                'working_hours'  => $this->formatMinutesToHours($item->total_working_minutes),
                'break_hours'    => $this->formatMinutesToHours($item->total_break_minutes),
                'total_sessions' => $item->total_sessions ?? 0,
                'status_label'   => $this->getStatusLabel($item->status),
                'sessions'       => $sessions[$key] ?? [],
            ]);
        });

        return [
            'total' => $total,
            'data'  => $data,
        ];
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
                'duration'       => $this->formatMinutesToHours($session->duration_minutes),
                'status'         => $session->status instanceof \BackedEnum ? $session->status->value : $session->status,
                'breaks'         => $session->breaks->map(fn($b) => [
                    'break_type'  => $b->break_type instanceof \BackedEnum ? $b->break_type->value : $b->break_type,
                    'break_start' => $b->break_start?->format('g:i a') ?? '--:--',
                    'break_end'   => $b->break_end?->format('g:i a') ?? '--:--',
                    'duration'    => $this->formatMinutesToHours($b->duration_minutes),
                    'reason'      => $b->reason,
                    'status'      => $b->status instanceof \BackedEnum ? $b->status->value : $b->status,
                ])->toArray(),
            ];
        }

        return $grouped;
    }
}
