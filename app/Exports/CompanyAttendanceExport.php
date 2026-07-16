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
            ->with(['employee:id,first_name,last_name,id_no,phone,designation_id', 'employee.designation:id,title', 'department:id,name'])
            ->orderBy('attendance_date');

        $query = $this->attendanceQuery($query, $this->request);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Emp ID',
            'Name',
            'Phone',
            'Department',
            'Designation',
            'Day',
            'Check In',
            'Check Out',
            'Date',
            'Status',
        ];
    }

    public function map($record): array
    {
        $date = Carbon::parse($record->attendance_date);

        return [
            $record->employee?->id_no ?? '-',
            $record->employee ? trim($record->employee->first_name . ' ' . $record->employee->last_name) : '-',
            $record->employee?->phone ?? '-',
            $record->department?->name ?? '-',
            $record->employee?->designation?->title ?? '-',
            $date->format('l'),
            $record->first_check_in ? Carbon::parse($record->first_check_in)->format('g:i a') : '--:--',
            $record->last_check_out ? Carbon::parse($record->last_check_out)->format('g:i a') : '--:--',
            $date->format('Y-m-d'),
            ucwords(str_replace('_', ' ', $record->status?->value ?? $record->status)),
        ];
    }
}
