<?php

namespace App\Services\Backend;

use App\Enums\ApproverType;
use App\Enums\LeaveApprovalStatus;
use App\Enums\LeaveMessage;
use App\Enums\LeaveRequestStatus;
use App\Enums\StepConditionType;
use App\Models\ApprovalWorkflowStep;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveApprovalService
{
    /**
     * Approve a leave request — workflow-driven.
     */
    public function approve(LeaveRequest $leaveRequest, Employee $approver, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $remarks) {
            $currentApproval = $this->resolveOrCreateApproval($leaveRequest, $approver, LeaveApprovalStatus::Approved, $remarks);

            $leaveType = $leaveRequest->leaveType;

            if (!$leaveType) {
                return $this->finalApprove($leaveRequest);
            }

            $workflow = $leaveType->approvalWorkflow;

            if (!$workflow) {
                return $this->finalApprove($leaveRequest);
            }

            $nextStep = $this->findNextApplicableStep($workflow, $currentApproval->workflow_step_id, $leaveRequest->total_days);

            if (!$nextStep) {
                return $this->finalApprove($leaveRequest);
            }

            return $this->processNextStep($leaveRequest, $workflow, $nextStep);
        });
    }

    /**
     * Reject a leave request — stops the chain.
     */
    public function reject(LeaveRequest $leaveRequest, Employee $approver, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $remarks) {
            $this->resolveOrCreateApproval($leaveRequest, $approver, LeaveApprovalStatus::Rejected, $remarks);
            $this->updateLeaveRequestStatus($leaveRequest, LeaveRequestStatus::Rejected);

            // Notify the employee and top executives that the leave request has been rejected
            app(NotificationService::class)->leaveRequestRejected($leaveRequest, $remarks);

            return $this->success(LeaveMessage::Rejected->value);
        });
    }

    /**
     * Initialize the first approval step for a leave request.
     */
    public function initializeApproval(LeaveRequest $leaveRequest, Employee $employee): void
    {
        $workflow = $leaveRequest->leaveType->approvalWorkflow;

        if (!$workflow || !$workflow->is_active) {
            $this->fallbackToDirectManager($leaveRequest, $employee);
            return;
        }

        $firstStep = $this->findNextApplicableStep($workflow, null, $leaveRequest->total_days);

        if (!$firstStep) {
            $this->finalApprove($leaveRequest);
            return;
        }

        $approver = $this->resolveApprover($firstStep, $employee);

        if (!$approver) {
            if (!$firstStep->is_mandatory) {
                $this->skipToNextOrFinalize($workflow, $firstStep, $leaveRequest);
                return;
            }

            $approver = $this->resolveDirectManager($employee);

            if (!$approver) {
                return;
            }
        }

        $this->createPendingApproval($leaveRequest, $approver, $firstStep);
        $leaveRequest->update(['current_approver_id' => $approver->id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Approval Resolution
    // ═══════════════════════════════════════════════════════════════

    private function resolveOrCreateApproval(
        LeaveRequest $leaveRequest,
        Employee $approver,
        LeaveApprovalStatus $status,
        ?string $remarks
    ): LeaveApproval {
        $existing = $this->findPendingApproval($leaveRequest, $approver);

        if ($existing) {
            $existing->update([
                'status'   => $status,
                'remarks'  => $remarks,
                'acted_at' => now(),
            ]);

            return $existing;
        }

        return LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id'      => $approver->id,
            'level'            => $this->getApproverLevel($approver),
            'status'           => $status,
            'remarks'          => $remarks,
            'acted_at'         => now(),
        ]);
    }

    private function findPendingApproval(LeaveRequest $leaveRequest, Employee $approver): ?LeaveApproval
    {
        return LeaveApproval::where('leave_request_id', $leaveRequest->id)
            ->where('approver_id', $approver->id)
            ->where('status', LeaveApprovalStatus::Pending)
            ->first();
    }

    private function createPendingApproval(LeaveRequest $leaveRequest, Employee $approver, ApprovalWorkflowStep $step): LeaveApproval
    {
        return LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id'      => $approver->id,
            'level'            => $this->getApproverLevel($approver),
            'workflow_step_id' => $step->id,
            'status'           => LeaveApprovalStatus::Pending,
        ]);
    }

    private function getApproverLevel(Employee $approver): int
    {
        return $approver->designation?->level?->value ?? 99;
    }

    // ═══════════════════════════════════════════════════════════════
    // Workflow Step Navigation
    // ═══════════════════════════════════════════════════════════════

    private function findNextApplicableStep($workflow, ?int $currentStepId, int $totalDays): ?ApprovalWorkflowStep
    {
        $steps = $workflow->steps()->orderBy('step_order')->get();

        $foundCurrent = $currentStepId === null;

        foreach ($steps as $step) {
            if (!$foundCurrent) {
                if ($step->id === $currentStepId) {
                    $foundCurrent = true;
                }
                continue;
            }

            if ($this->stepConditionMet($step, $totalDays)) {
                return $step;
            }
        }

        return null;
    }

    private function stepConditionMet(ApprovalWorkflowStep $step, int $totalDays): bool
    {
        return match ($step->condition_type) {
            StepConditionType::Always          => true,
            StepConditionType::DaysGreaterThan => $totalDays > ($step->condition_value ?? 0),
            StepConditionType::DaysLessThan    => $totalDays < ($step->condition_value ?? 0),
            default                            => true,
        };
    }

    private function processNextStep(LeaveRequest $leaveRequest, $workflow, ApprovalWorkflowStep $nextStep): array
    {
        $employee = $leaveRequest->employee;
        $nextApprover = $this->resolveApprover($nextStep, $employee);

        if (!$nextApprover) {
            if (!$nextStep->is_mandatory) {
                return $this->skipToNextOrFinalize($workflow, $nextStep, $leaveRequest);
            }
            return $this->finalApprove($leaveRequest);
        }

        return $this->forwardToStep($leaveRequest, $nextApprover, $nextStep);
    }

    private function skipToNextOrFinalize($workflow, ApprovalWorkflowStep $skippedStep, LeaveRequest $leaveRequest, int $depth = 0): array
    {
        if ($depth > 10) {
            Log::error('LeaveApprovalService::skipToNextOrFinalize exceeded max recursion depth', [
                'leave_request_id' => $leaveRequest->id,
                'skipped_step_id'  => $skippedStep->id,
            ]);
            return $this->finalApprove($leaveRequest);
        }

        $nextStep = $this->findNextApplicableStep($workflow, $skippedStep->id, $leaveRequest->total_days);

        if (!$nextStep) {
            return $this->finalApprove($leaveRequest);
        }

        $nextApprover = $this->resolveApprover($nextStep, $leaveRequest->employee);

        if (!$nextApprover) {
            if (!$nextStep->is_mandatory) {
                return $this->skipToNextOrFinalize($workflow, $nextStep, $leaveRequest, $depth + 1);
            }
            return $this->finalApprove($leaveRequest);
        }

        return $this->forwardToStep($leaveRequest, $nextApprover, $nextStep);
    }

    private function forwardToStep(LeaveRequest $leaveRequest, Employee $nextApprover, ApprovalWorkflowStep $step): array
    {
        $this->createPendingApproval($leaveRequest, $nextApprover, $step);

        $this->updateLeaveRequestStatus($leaveRequest, LeaveRequestStatus::InReview, $nextApprover->id);

        // Notify the next approver via email that a leave request is awaiting their action
        app(NotificationService::class)->leaveRequestForwarded($leaveRequest, $nextApprover);

        return $this->success(LeaveMessage::ForwardedTo->with(['name' => $nextApprover->full_name]));
    }

    // ═══════════════════════════════════════════════════════════════
    // Approver Resolution
    // ═══════════════════════════════════════════════════════════════

    private function resolveApprover(ApprovalWorkflowStep $step, Employee $employee): ?Employee
    {
        return match ($step->approver_type) {
            ApproverType::DirectManager    => $this->resolveDirectManager($employee),
            ApproverType::DesignationLevel => $this->resolveByDesignation($employee, (int) $step->approver_value),
            ApproverType::SpecificEmployee => $this->resolveSpecificEmployee($step->approver_value),
            ApproverType::DepartmentHead   => $this->resolveDepartmentHead($employee),
            default                        => null,
        };
    }

    private function resolveDirectManager(Employee $employee): ?Employee
    {
        if (!$employee->manager_id) {
            return null;
        }

        return Employee::with('designation')->find($employee->manager_id);
    }

    private function resolveSpecificEmployee($employeeId): ?Employee
    {
        return Employee::with('designation')->find($employeeId);
    }

    private function resolveByDesignation(Employee $employee, int $designationId): ?Employee
    {
        $designation = Designation::find($designationId);

        if (!$designation || !$designation->level) {
            return null;
        }

        return $this->resolveByDesignationLevel($employee, $designation->level->value);
    }

    // Resolve the approver at EXACTLY the target designation level within the employee's company.
    // The workflow step's level is the level that must act — we do not walk the manager chain and
    // we do not escalate to a higher rank when the exact level is missing, because that would
    // skip entire designation levels (e.g. routing a PM-level step to the CTO).
    //
    // If multiple employees share the target level, the match with the lowest id is chosen
    // deterministically. If no active employee at that level exists (excluding the submitter),
    // NULL is returned — the caller decides whether to skip, finalize, or fall back.
    private function resolveByDesignationLevel(Employee $employee, int $targetLevel): ?Employee
    {
        return Employee::with('designation')
            ->where('company_id', $employee->company_id)
            ->where('id', '!=', $employee->id)
            ->where('status', true)
            ->whereHas('designation', fn($q) => $q->where('level', $targetLevel))
            ->orderBy('id')
            ->first();
    }

    private function resolveDepartmentHead(Employee $employee): ?Employee
    {
        if (!$employee->department_id) {
            return null;
        }

        return Employee::where('department_id', $employee->department_id)
            ->where('id', '!=', $employee->id)
            ->whereHas('designation', fn($q) => $q->whereNotNull('level'))
            ->with('designation')
            ->join('designations', 'employees.designation_id', '=', 'designations.id')
            ->orderBy('designations.level', 'asc')
            ->select('employees.*')
            ->first();
    }

    // ═══════════════════════════════════════════════════════════════
    // Status & Balance Updates
    // ═══════════════════════════════════════════════════════════════

    private function finalApprove(LeaveRequest $leaveRequest): array
    {
        $this->updateLeaveRequestStatus($leaveRequest, LeaveRequestStatus::Approved);
        $this->deductLeaveBalance($leaveRequest);

        // Notify the employee and top executives that the leave request has been fully approved
        app(NotificationService::class)->leaveRequestApproved($leaveRequest);

        return $this->success(LeaveMessage::Approved->value);
    }

    private function updateLeaveRequestStatus(LeaveRequest $leaveRequest, LeaveRequestStatus $status, ?int $approverId = null): void
    {
        $leaveRequest->update([
            'status'              => $status,
            'current_approver_id' => $approverId,
        ]);
    }

    private function deductLeaveBalance(LeaveRequest $leaveRequest): void
    {
        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $leaveRequest->started_at->year)
            ->first();

        $balance?->increment('used', $leaveRequest->total_days);
    }

    private function fallbackToDirectManager(LeaveRequest $leaveRequest, Employee $employee): void
    {
        if (!$employee->manager_id) {
            return;
        }

        $manager = $this->resolveDirectManager($employee);

        if (!$manager) {
            return;
        }

        LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id'      => $employee->manager_id,
            'level'            => $this->getApproverLevel($manager),
            'status'           => LeaveApprovalStatus::Pending,
        ]);

        $leaveRequest->update(['current_approver_id' => $employee->manager_id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Response Helpers
    // ═══════════════════════════════════════════════════════════════

    private function success(string $message): array
    {
        return ['success' => true, 'message' => $message];
    }
}
