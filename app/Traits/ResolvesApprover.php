<?php

namespace App\Traits;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;

trait ResolvesApprover
{
    /**
     * Resolve approver context for the current authenticated user.
     */
    protected function resolveApproverContext(LeaveRequest $leaveRequest): bool
    {
        $user = auth()->user();
        $employee = $user->employee;

        // Never allow approving your own leave
        if ($employee && $leaveRequest->employee_id === $employee->id) {
            return false;
        }

        $isCurrentApprover = false;

        if ($employee) {
            $isCurrentApprover = $leaveRequest->current_approver_id === $employee->id;
        }

        $actionableStatuses = collect([LeaveRequestStatus::Pending, LeaveRequestStatus::InReview]);

        if ($user->hasRole('super_admin') && $actionableStatuses->contains($leaveRequest->status)) {
            $isCurrentApprover = true;
        }

        return $isCurrentApprover;
    }

    /**
     * Get the approver Employee record for processing approval/rejection.
     */
    protected function getApproverEmployee(LeaveRequest $leaveRequest): ?Employee
    {
        $user = auth()->user();

        if ($user->employee) {
            return $user->employee;
        }

        // Super admin without employee record — use current approver
        if ($user->hasRole('super_admin') && $leaveRequest->current_approver_id) {
            return Employee::with('designation')->find($leaveRequest->current_approver_id);
        }

        return null;
    }

    /**
     * Check if the current user is authorized to act on this leave request.
     */
    protected function canActOnLeaveRequest(LeaveRequest $leaveRequest): bool
    {
        $user = auth()->user();
        $actionableStatuses = collect([LeaveRequestStatus::Pending, LeaveRequestStatus::InReview]);

        // Never allow employees to approve their own leave
        if ($user->employee && $leaveRequest->employee_id === $user->employee->id) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return $actionableStatuses->contains($leaveRequest->status);
        }

        return $user->employee && $leaveRequest->current_approver_id === $user->employee->id;
    }
}
