<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use App\Traits\QueryParams;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveRequestExport implements FromCollection, WithHeadings, WithMapping
{
    use QueryParams;

    public function __construct(
        protected Request $request
    ) {}

    public function collection()
    {
        $query = LeaveRequest::query()
            ->with([
                'employee:id,first_name,last_name,id_no,phone',
                'leaveType:id,name',
            ])
            ->orderBy('created_at', 'desc');

        $query = $this->leaveRequestQuery($query, $this->request);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Phone',
            'Leave',
            'Start',
            'End',
            'Total Days',
            'Notes',
            'Applied',
            'Status',
        ];
    }

    public function map($record): array
    {
        return [
            $record->employee?->id_no ?? '-',
            $record->employee ? trim($record->employee->first_name . ' ' . $record->employee->last_name) : '-',
            $record->employee?->phone ?? '-',
            $record->leaveType?->name ?? '-',
            $record->started_at ? Carbon::parse($record->started_at)->format('d M Y') : '-',
            $record->ended_at ? Carbon::parse($record->ended_at)->format('d M Y') : '-',
            $record->total_days ?? 0,
            $record->notes ?? '-',
            $record->created_at ? Carbon::parse($record->created_at)->format('d M Y') : '-',
            ucwords(str_replace('_', ' ', $record->status?->value ?? $record->status)),
        ];
    }
}
