<?php

namespace App\Services\Backend;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    use PaginateQuery, QueryParams;

    public function list(Request $request): array
    {
        $query = LeaveRequest::query()
            ->with([
                'employee:id,first_name,last_name,id_no',
                'employee.media',
                'leaveType:id,name',
                'currentApprover:id,first_name,last_name',
            ])
            ->orderBy('created_at', 'desc');

        $query = $this->leaveRequestQuery($query, $request);

        return $this->transformLeaveRequests($query, $request->integer('per_page', 50));
    }

    public function store(Employee $employee, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $data) {
            $startDate = Carbon::parse($data['started_at']);
            $endDate = Carbon::parse($data['ended_at']);
            $totalDays = $startDate->diffInDays($endDate) + 1;

            $leaveRequest = LeaveRequest::create([
                'title'               => $data['title'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'total_days'          => $totalDays,
                'company_id'          => $employee->company_id,
                'employee_id'         => $employee->id,
                'leave_type_id'       => $data['leave_type_id'],
                'current_approver_id' => null,
                'status'              => LeaveRequestStatus::Pending,
                'started_at'          => $data['started_at'],
                'ended_at'            => $data['ended_at'],
            ]);

            // Delegate first approver resolution to the approval service
            app(LeaveApprovalService::class)->initializeApproval($leaveRequest, $employee);

            return $leaveRequest;
        });
    }

    public function cancel(LeaveRequest $leaveRequest): bool
    {
        if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
            return false;
        }

        $leaveRequest->update(['status' => LeaveRequestStatus::Cancelled]);

        return true;
    }

    public function getBalances(Employee $employee, int $year): array
    {
        $leaveTypes = LeaveType::where('company_id', $employee->company_id)
            ->where('status', true)
            ->get();

        return $leaveTypes->map(function ($type) use ($employee, $year) {
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $employee->id,
                    'leave_type_id' => $type->id,
                    'year'          => $year,
                ],
                [
                    'total' => $type->max_per_year,
                    'used'  => 0,
                ]
            );

            return [
                'id'          => $type->id,
                'name'        => $type->name,
                'total'       => $balance->total,
                'used'        => $balance->used,
                'remaining'   => $balance->remaining,
            ];
        })->toArray();
    }
}
