<?php

namespace App\Services\Backend;

use App\Enums\LeaveApprovalStatus;
use App\Enums\LeaveMessage;
use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\NotificationService;
use App\Services\WorkScheduleService;
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
        $totalDays = $this->calculateWorkingDays($employee, $startDate, $endDate);
        $leaveTypeId = data_get($data, 'leave_type_id');

        if ($totalDays < 1) {
            return LeaveMessage::NoWorkingDays->value;
        }

        if ($error = $this->validateLeaveBalance($employee, $leaveTypeId, $startDate->year, $totalDays)) {
            return $error;
        }

        if ($this->hasOverlappingLeave($employee, data_get($data, 'started_at'), data_get($data, 'ended_at'))) {
            return LeaveMessage::OverlappingDates->value;
        }

        return $this->createLeaveRequest($employee, $data, $totalDays);
    }

    // Count only company working days, excluding weekends (per CompanyWorkingDay) and active holidays.
    private function calculateWorkingDays(Employee $employee, Carbon $start, Carbon $end): int
    {
        $schedule = app(WorkScheduleService::class);
        $holidayDates = $this->holidayDatesBetween($employee, $start, $end);

        $days = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if ($schedule->isWorkingDay($employee, $cursor) && !$holidayDates->contains($cursor->toDateString())) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function holidayDatesBetween(Employee $employee, Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $holidays = Holiday::where('company_id', $employee->company_id)
            ->where('status', true)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get(['start_date', 'end_date']);

        $dates = collect();
        foreach ($holidays as $h) {
            $from = $h->start_date->gt($start) ? $h->start_date : $start;
            $to   = $h->end_date->lt($end) ? $h->end_date : $end;
            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $dates->push($cursor->toDateString());
                $cursor->addDay();
            }
        }

        return $dates->unique();
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
