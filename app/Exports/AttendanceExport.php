<?php

namespace App\Exports;

use App\Models\AttendanceSummary;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected int    $employeeId,
        protected string $month
    ) {}

    public function collection()
    {
        [$year, $month] = explode('-', $this->month);

        return AttendanceSummary::query()
            ->where('employee_id', $this->employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->with('employee:id,first_name,last_name,id_no')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Day',
            'Check In',
            'Check Out',
            'Working Hours',
            'Break Hours',
            'Sessions',
            'Status',
        ];
    }

    public function map($record): array
    {
        $date = Carbon::parse($record->attendance_date);

        $workingHours = $record->total_working_minutes
            ? sprintf('%dh %dm', floor($record->total_working_minutes / 60), $record->total_working_minutes % 60)
            : '0h 0m';

        $breakHours = $record->total_break_minutes
            ? sprintf('%dh %dm', floor($record->total_break_minutes / 60), $record->total_break_minutes % 60)
            : '0h 0m';

        return [
            $date->format('Y-m-d'),
            $date->format('D'),
            $record->first_check_in ? Carbon::parse($record->first_check_in)->format('g:i a') : '--:--',
            $record->last_check_out ? Carbon::parse($record->last_check_out)->format('g:i a') : '--:--',
            $workingHours,
            $breakHours,
            $record->total_sessions ?? 0,
            ucwords(str_replace('_', ' ', $record->status?->value ?? $record->status)),
        ];
    }
}
