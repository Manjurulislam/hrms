<?php

namespace App\Services\Backend;

use App\Enums\LeaveApprovalStatus;
use App\Enums\LeaveMessage;
use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\NotificationService;
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

    public function store(Employee $employee, array $data): LeaveRequest|string
    {
        $startDate = Carbon::parse(data_get($data, 'started_at'));
        $endDate = Carbon::parse(data_get($data, 'ended_at'));
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $leaveTypeId = data_get($data, 'leave_type_id');

        if ($error = $this->validateLeaveBalance($employee, $leaveTypeId, $startDate->year, $totalDays)) {
            return $error;
        }

        if ($this->hasOverlappingLeave($employee, data_get($data, 'started_at'), data_get($data, 'ended_at'))) {
            return LeaveMessage::OverlappingDates->value;
        }

        return $this->createLeaveRequest($employee, $data, $totalDays);
    }

    public function cancel(LeaveRequest $leaveRequest): bool|string
    {
        if (!$this->isCancellable($leaveRequest)) {
            return LeaveMessage::CannotCancel->value;
        }

        if ($this->hasApprovalAction($leaveRequest)) {
            return LeaveMessage::AlreadyReviewed->value;
        }

        $leaveRequest->update(['status' => LeaveRequestStatus::Cancelled]);

        // Notify the current approver and top executives that the employee has cancelled this leave request
        app(NotificationService::class)->leaveRequestCancelled($leaveRequest);

        return true;
    }

    public function getBalances(Employee $employee, int $year): array
    {
        $leaveTypes = $this->getActiveLeaveTypes($employee);

        return $leaveTypes->map(function ($type) use ($employee, $year) {
            $balance = $this->getOrCreateBalance($employee, $type, $year);

            return [
                'id'        => $type->id,
                'name'      => $type->name,
                'total'     => $balance->total,
                'used'      => $balance->used,
                'remaining' => $balance->remaining,
            ];
        })->toArray();
    }

    // ═══════════════════════════════════════════════════════════════
    // Validation Methods
    // ═══════════════════════════════════════════════════════════════

    private function validateLeaveBalance(Employee $employee, int $leaveTypeId, int $year, int $totalDays): ?string
    {
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if ($balance && $balance->remaining < $totalDays) {
            return LeaveMessage::InsufficientBalance->with(['remaining' => $balance->remaining]);
        }

        return null;
    }

    private function hasOverlappingLeave(Employee $employee, string $startDate, string $endDate): bool
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::InReview, LeaveRequestStatus::Approved])
            ->where('started_at', '<=', $endDate)
            ->where('ended_at', '>=', $startDate)
            ->exists();
    }

    private function isCancellable(LeaveRequest $leaveRequest): bool
    {
        return collect([LeaveRequestStatus::Pending, LeaveRequestStatus::InReview])
            ->contains($leaveRequest->status);
    }

    private function hasApprovalAction(LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->approvals()
            ->whereIn('status', [LeaveApprovalStatus::Approved, LeaveApprovalStatus::Rejected])
            ->exists();
    }

    // ═══════════════════════════════════════════════════════════════
    // Query Methods
    // ═══════════════════════════════════════════════════════════════

    private function getActiveLeaveTypes(Employee $employee)
    {
        return LeaveType::where('company_id', $employee->company_id)
            ->where('status', true)
            ->get();
    }

    private function getOrCreateBalance(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
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
    }

    // ═══════════════════════════════════════════════════════════════
    // Create Methods
    // ═══════════════════════════════════════════════════════════════

    private function createLeaveRequest(Employee $employee, array $data, int $totalDays): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $data, $totalDays) {
            $leaveRequest = LeaveRequest::create([
                'title'               => data_get($data, 'title'),
                'notes'               => data_get($data, 'notes'),
                'total_days'          => $totalDays,
                'company_id'          => $employee->company_id,
                'employee_id'         => $employee->id,
                'leave_type_id'       => data_get($data, 'leave_type_id'),
                'current_approver_id' => null,
                'status'              => LeaveRequestStatus::Pending,
                'started_at'          => data_get($data, 'started_at'),
                'ended_at'            => data_get($data, 'ended_at'),
            ]);

            app(LeaveApprovalService::class)->initializeApproval($leaveRequest, $employee);

            // Notify the employee (confirmation), first approver (action needed), and top executives (awareness)
            app(NotificationService::class)->leaveRequestCreated($leaveRequest);

            return $leaveRequest;
        });
    }
}
