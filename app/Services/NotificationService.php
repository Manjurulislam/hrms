<?php

namespace App\Services;

use App\Enums\DesignationLevel;
use App\Mail\LeaveRequestApprovedMail;
use App\Mail\LeaveRequestApproverMail;
use App\Mail\LeaveRequestCancelledMail;
use App\Mail\LeaveRequestRejectedMail;
use App\Mail\LeaveRequestSubmittedMail;
use App\Mail\ResetPasswordMail;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    // ═══════════════════════════════════════════════════════════════
    // Leave Request Notifications
    // ═══════════════════════════════════════════════════════════════

    public function leaveRequestCreated(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->load(['employee', 'leaveType', 'currentApprover']);

        // Confirm the employee that their leave request has been submitted successfully
        Mail::to($leaveRequest->employee->email)
            ->send(new LeaveRequestSubmittedMail($leaveRequest));

        // Notify the assigned approver so they can review and take action on the request
        if ($leaveRequest->currentApprover && $leaveRequest->currentApprover->email) {
            Mail::to($leaveRequest->currentApprover->email)
                ->send(new LeaveRequestApproverMail($leaveRequest, $leaveRequest->currentApprover->full_name));
        }

        // Keep top executives informed about all new leave requests for visibility
        $this->notifyCeo($leaveRequest, new LeaveRequestSubmittedMail($leaveRequest));
    }

    public function leaveRequestApproved(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->load(['employee', 'leaveType']);

        // Inform the employee that their leave has been fully approved and balance has been deducted
        Mail::to($leaveRequest->employee->email)
            ->send(new LeaveRequestApprovedMail($leaveRequest, $leaveRequest->employee->full_name));

        // Keep top executives informed about approved leaves for workforce planning
        $this->notifyCeo($leaveRequest, fn(Employee $ceo) => new LeaveRequestApprovedMail($leaveRequest, $ceo->full_name));
    }

    public function leaveRequestRejected(LeaveRequest $leaveRequest, ?string $remarks = null): void
    {
        $leaveRequest->load(['employee', 'leaveType']);

        // Inform the employee that their leave was rejected, including the rejection remarks
        Mail::to($leaveRequest->employee->email)
            ->send(new LeaveRequestRejectedMail($leaveRequest, $leaveRequest->employee->full_name, $remarks));

        // Keep top executives informed about rejected leaves for oversight
        $this->notifyCeo($leaveRequest, fn(Employee $ceo) => new LeaveRequestRejectedMail($leaveRequest, $ceo->full_name, $remarks));
    }

    public function leaveRequestForwarded(LeaveRequest $leaveRequest, Employee $nextApprover): void
    {
        $leaveRequest->load(['employee', 'leaveType']);

        // Notify the next approver in the workflow chain that a leave request is awaiting their action
        if ($nextApprover->email) {
            Mail::to($nextApprover->email)
                ->send(new LeaveRequestApproverMail($leaveRequest, $nextApprover->full_name));
        }
    }

    public function leaveRequestCancelled(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->load(['employee', 'leaveType', 'currentApprover']);

        // Inform the current approver that the request they were reviewing has been cancelled by the employee
        if ($leaveRequest->currentApprover && $leaveRequest->currentApprover->email) {
            Mail::to($leaveRequest->currentApprover->email)
                ->send(new LeaveRequestCancelledMail($leaveRequest, $leaveRequest->currentApprover->full_name));
        }

        // Keep top executives informed about cancelled leaves for awareness
        $this->notifyCeo($leaveRequest, fn(Employee $ceo) => new LeaveRequestCancelledMail($leaveRequest, $ceo->full_name));
    }

    // ═══════════════════════════════════════════════════════════════
    // Password Reset
    // ═══════════════════════════════════════════════════════════════

    public function sendPasswordReset(string $email, string $name, string $url): void
    {
        Mail::to($email)->send(new ResetPasswordMail($url, $name));
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function getCeoEmployees(int $companyId): Collection
    {
        return Employee::whereHas('designation', fn($q) => $q->where('level', DesignationLevel::TopExecutive))
            ->where('company_id', $companyId)
            ->where('status', true)
            ->whereNotNull('email')
            ->get();
    }

    private function notifyCeo(LeaveRequest $leaveRequest, $mailable): void
    {
        $ceos = $this->getCeoEmployees($leaveRequest->company_id);

        foreach ($ceos as $ceo) {
            $mail = is_callable($mailable) ? $mailable($ceo) : clone $mailable;
            Mail::to($ceo->email)->send($mail);
        }
    }
}
