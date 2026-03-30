<?php

namespace App\Services;

use App\Enums\DesignationLevel;
use App\Mail\LeaveRequestApprovedMail;
use App\Mail\LeaveRequestApproverMail;
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

        // Notify employee
        Mail::to($leaveRequest->employee->email)
            ->send(new LeaveRequestSubmittedMail($leaveRequest));

        // Notify approver
        if ($leaveRequest->currentApprover && $leaveRequest->currentApprover->email) {
            Mail::to($leaveRequest->currentApprover->email)
                ->send(new LeaveRequestApproverMail($leaveRequest, $leaveRequest->currentApprover->full_name));
        }

        // Notify CEO
        $this->notifyCeo($leaveRequest, new LeaveRequestSubmittedMail($leaveRequest));
    }

    public function leaveRequestApproved(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->load(['employee', 'leaveType']);

        // Notify CEO
        $this->notifyCeo($leaveRequest, fn(Employee $ceo) => new LeaveRequestApprovedMail($leaveRequest, $ceo->full_name));
    }

    public function leaveRequestRejected(LeaveRequest $leaveRequest, ?string $remarks = null): void
    {
        $leaveRequest->load(['employee', 'leaveType']);

        // Notify employee
        Mail::to($leaveRequest->employee->email)
            ->send(new LeaveRequestRejectedMail($leaveRequest, $leaveRequest->employee->full_name, $remarks));
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
