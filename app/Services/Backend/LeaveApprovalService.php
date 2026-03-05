<?php

namespace App\Services\Backend;

use App\Enums\LeaveApprovalStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    /**
     * Approve a leave request.
     *
     * For level > 2 (Team Lead, PM): auto-forward to their manager
     * For level 2 (CTO): can final approve or forward to CEO
     * For level 1 (CEO): always final approve
     */
    public function approve(LeaveRequest $leaveRequest, Employee $approver, ?string $remarks = null, bool $forward = false): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $remarks, $forward) {
            $approverLevel = $approver->designation?->level ?? 99;

            // Update existing approval row
            $approval = LeaveApproval::where('leave_request_id', $leaveRequest->id)
                ->where('approver_id', $approver->id)
                ->where('status', LeaveApprovalStatus::Pending)
                ->first();

            if ($approval) {
                $approval->update([
                    'status'   => LeaveApprovalStatus::Approved,
                    'remarks'  => $remarks,
                    'acted_at' => now(),
                ]);
            } else {
                LeaveApproval::create([
                    'leave_request_id' => $leaveRequest->id,
                    'approver_id'      => $approver->id,
                    'level'            => $approverLevel,
                    'status'           => LeaveApprovalStatus::Approved,
                    'remarks'          => $remarks,
                    'acted_at'         => now(),
                ]);
            }

            // CEO (level 1) — always final approve
            if ($approverLevel === 1) {
                return $this->finalApprove($leaveRequest);
            }

            // CTO (level 2) — can final approve or forward
            if ($approverLevel <= 2) {
                if ($forward && $approver->manager_id) {
                    return $this->forwardToNext($leaveRequest, $approver);
                }
                return $this->finalApprove($leaveRequest);
            }

            // Level > 2 (Team Lead, PM) — auto-forward to their manager
            if ($approver->manager_id) {
                return $this->forwardToNext($leaveRequest, $approver);
            }

            // No manager above — final approve
            return $this->finalApprove($leaveRequest);
        });
    }

    /**
     * Reject a leave request — stops the chain.
     */
    public function reject(LeaveRequest $leaveRequest, Employee $approver, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $remarks) {
            $approverLevel = $approver->designation?->level ?? 99;

            // Update existing approval row
            $approval = LeaveApproval::where('leave_request_id', $leaveRequest->id)
                ->where('approver_id', $approver->id)
                ->where('status', LeaveApprovalStatus::Pending)
                ->first();

            if ($approval) {
                $approval->update([
                    'status'   => LeaveApprovalStatus::Rejected,
                    'remarks'  => $remarks,
                    'acted_at' => now(),
                ]);
            } else {
                LeaveApproval::create([
                    'leave_request_id' => $leaveRequest->id,
                    'approver_id'      => $approver->id,
                    'level'            => $approverLevel,
                    'status'           => LeaveApprovalStatus::Rejected,
                    'remarks'          => $remarks,
                    'acted_at'         => now(),
                ]);
            }

            $leaveRequest->update([
                'status'              => LeaveRequestStatus::Rejected,
                'current_approver_id' => null,
            ]);

            return ['success' => true, 'message' => 'Leave request rejected.'];
        });
    }

    private function forwardToNext(LeaveRequest $leaveRequest, Employee $currentApprover): array
    {
        $nextApprover = Employee::with('designation')->find($currentApprover->manager_id);

        if (!$nextApprover) {
            return $this->finalApprove($leaveRequest);
        }

        // Create pending approval for next approver
        LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id'      => $nextApprover->id,
            'level'            => $nextApprover->designation?->level ?? 99,
            'status'           => LeaveApprovalStatus::Pending,
        ]);

        $leaveRequest->update([
            'current_approver_id' => $nextApprover->id,
            'status'              => LeaveRequestStatus::InReview,
        ]);

        return ['success' => true, 'message' => 'Approved and forwarded to ' . $nextApprover->full_name . '.'];
    }

    private function finalApprove(LeaveRequest $leaveRequest): array
    {
        $leaveRequest->update([
            'status'              => LeaveRequestStatus::Approved,
            'current_approver_id' => null,
        ]);

        // Deduct from leave balance
        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $leaveRequest->started_at->year)
            ->first();

        if ($balance) {
            $balance->increment('used', $leaveRequest->total_days);
        }

        return ['success' => true, 'message' => 'Leave request approved.'];
    }
}
