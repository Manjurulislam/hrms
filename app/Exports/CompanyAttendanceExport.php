<?php

namespace App\Exports;

use App\Models\AttendanceSummary;
use App\Traits\QueryParams;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CompanyAttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    use QueryParams;

    public function __construct(
        protected Request $request
    ) {}

    public function collection()
    {
        $query = AttendanceSummary::query()
            ->with(['employee:id,first_name,last_name,id_no', 'department:id,name'])
            ->orderBy('attendance_date');

        $query = $this->attendanceQuery($query, $this->request);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Emp ID',
            'Department',
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
            $record->employee ? trim($record->employee->first_name . ' ' . $record->employee->last_name) : '-',
            $record->employee->id_no ?? '-',
            $record->department->name ?? '-',
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
