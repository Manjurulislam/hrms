<?php

namespace App\Services\Backend;

use App\Enums\ApproverType;
use App\Enums\LeaveApprovalStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\StepConditionType;
use App\Models\ApprovalWorkflowStep;
use App\Models\Employee;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    /**
     * Approve a leave request — workflow-driven.
     */
    public function approve(LeaveRequest $leaveRequest, Employee $approver, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $remarks) {
            // Mark current approval as approved
            $currentApproval = LeaveApproval::where('leave_request_id', $leaveRequest->id)
                ->where('approver_id', $approver->id)
                ->where('status', LeaveApprovalStatus::Pending)
                ->first();

            if ($currentApproval) {
                $currentApproval->update([
                    'status'   => LeaveApprovalStatus::Approved,
                    'remarks'  => $remarks,
                    'acted_at' => now(),
                ]);
            } else {
                $currentApproval = LeaveApproval::create([
                    'leave_request_id' => $leaveRequest->id,
                    'approver_id'      => $approver->id,
                    'level'            => $approver->designation?->level?->value ?? 99,
                    'status'           => LeaveApprovalStatus::Approved,
                    'remarks'          => $remarks,
                    'acted_at'         => now(),
                ]);
            }

            // Find the next step in the workflow
            $workflow = $leaveRequest->leaveType->approvalWorkflow;

            if (!$workflow) {
                // No workflow — fallback: single-step direct manager approval
                return $this->finalApprove($leaveRequest);
            }

            $currentStepId = $currentApproval->workflow_step_id;
            $nextStep = $this->findNextApplicableStep($workflow, $currentStepId, $leaveRequest->total_days);

            if (!$nextStep) {
                return $this->finalApprove($leaveRequest);
            }

            // Resolve the next approver
            $employee = $leaveRequest->employee;
            $nextApprover = $this->resolveApprover($nextStep, $employee);

            if (!$nextApprover) {
                // Can't resolve approver — skip non-mandatory, or final approve
                if (!$nextStep->is_mandatory) {
                    return $this->skipToNextOrFinalize($workflow, $nextStep, $leaveRequest);
                }
                return $this->finalApprove($leaveRequest);
            }

            return $this->forwardToStep($leaveRequest, $nextApprover, $nextStep);
        });
    }

    /**
     * Reject a leave request — stops the chain.
     */
    public function reject(LeaveRequest $leaveRequest, Employee $approver, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $remarks) {
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
                    'level'            => $approver->designation?->level?->value ?? 99,
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

    /**
     * Initialize the first approval step for a leave request.
     * Called from LeaveRequestService::store().
     */
    public function initializeApproval(LeaveRequest $leaveRequest, Employee $employee): void
    {
        $workflow = $leaveRequest->leaveType->approvalWorkflow;

        if (!$workflow || !$workflow->is_active) {
            // Fallback: direct manager
            if ($employee->manager_id) {
                $manager = Employee::with('designation')->find($employee->manager_id);
                LeaveApproval::create([
                    'leave_request_id' => $leaveRequest->id,
                    'approver_id'      => $employee->manager_id,
                    'level'            => $manager?->designation?->level?->value ?? 99,
                    'status'           => LeaveApprovalStatus::Pending,
                ]);
                $leaveRequest->update(['current_approver_id' => $employee->manager_id]);
            }
            return;
        }

        // Find first applicable step
        $firstStep = $this->findNextApplicableStep($workflow, null, $leaveRequest->total_days);

        if (!$firstStep) {
            // No applicable steps — auto-approve
            $this->finalApprove($leaveRequest);
            return;
        }

        $approver = $this->resolveApprover($firstStep, $employee);

        if (!$approver) {
            if (!$firstStep->is_mandatory) {
                $this->skipToNextOrFinalize($workflow, $firstStep, $leaveRequest);
                return;
            }
            // Fallback to direct manager
            if ($employee->manager_id) {
                $approver = Employee::with('designation')->find($employee->manager_id);
            }
            if (!$approver) {
                return;
            }
        }

        LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id'      => $approver->id,
            'level'            => $approver->designation?->level?->value ?? 99,
            'workflow_step_id' => $firstStep->id,
            'status'           => LeaveApprovalStatus::Pending,
        ]);

        $leaveRequest->update(['current_approver_id' => $approver->id]);
    }

    /**
     * Find the next applicable workflow step after the current one.
     */
    private function findNextApplicableStep($workflow, ?int $currentStepId, int $totalDays): ?ApprovalWorkflowStep
    {
        $steps = $workflow->steps()->orderBy('step_order')->get();

        $foundCurrent = $currentStepId === null; // if null, start from beginning

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

    /**
     * Check if a step's condition is met.
     */
    private function stepConditionMet(ApprovalWorkflowStep $step, int $totalDays): bool
    {
        return match ($step->condition_type) {
            StepConditionType::Always          => true,
            StepConditionType::DaysGreaterThan => $totalDays > ($step->condition_value ?? 0),
            StepConditionType::DaysLessThan    => $totalDays < ($step->condition_value ?? 0),
            default                            => true,
        };
    }

    /**
     * Resolve the actual Employee approver for a workflow step.
     */
    private function resolveApprover(ApprovalWorkflowStep $step, Employee $employee): ?Employee
    {
        return match ($step->approver_type) {
            ApproverType::DirectManager   => $this->resolveDirectManager($employee),
            ApproverType::DesignationLevel => $this->resolveByDesignationLevel($employee, (int) $step->approver_value),
            ApproverType::SpecificEmployee => Employee::with('designation')->find($step->approver_value),
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

    private function resolveByDesignationLevel(Employee $employee, int $targetLevel): ?Employee
    {
        // Walk up the manager chain until we find someone at the target level
        $current = $employee;
        $visited = [];

        while ($current->manager_id && !in_array($current->manager_id, $visited)) {
            $visited[] = $current->manager_id;
            $manager = Employee::with('designation')->find($current->manager_id);

            if (!$manager) {
                break;
            }

            $managerLevel = $manager->designation?->level?->value;

            if ($managerLevel !== null && $managerLevel <= $targetLevel) {
                return $manager;
            }

            $current = $manager;
        }

        return null;
    }

    private function resolveDepartmentHead(Employee $employee): ?Employee
    {
        if (!$employee->department_id) {
            return null;
        }

        // Find the employee with the highest designation level in the same department
        // (lowest level number = highest authority)
        return Employee::where('department_id', $employee->department_id)
            ->where('id', '!=', $employee->id)
            ->whereHas('designation', fn($q) => $q->whereNotNull('level'))
            ->with('designation')
            ->get()
            ->sortBy(fn($e) => $e->designation?->level?->value ?? 99)
            ->first();
    }

    private function forwardToStep(LeaveRequest $leaveRequest, Employee $nextApprover, ApprovalWorkflowStep $step): array
    {
        LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id'      => $nextApprover->id,
            'level'            => $nextApprover->designation?->level?->value ?? 99,
            'workflow_step_id' => $step->id,
            'status'           => LeaveApprovalStatus::Pending,
        ]);

        $leaveRequest->update([
            'current_approver_id' => $nextApprover->id,
            'status'              => LeaveRequestStatus::InReview,
        ]);

        return ['success' => true, 'message' => 'Approved and forwarded to ' . $nextApprover->full_name . '.'];
    }

    private function skipToNextOrFinalize($workflow, ApprovalWorkflowStep $skippedStep, LeaveRequest $leaveRequest): array
    {
        $nextStep = $this->findNextApplicableStep($workflow, $skippedStep->id, $leaveRequest->total_days);

        if (!$nextStep) {
            return $this->finalApprove($leaveRequest);
        }

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

    private function finalApprove(LeaveRequest $leaveRequest): array
    {
        $leaveRequest->update([
            'status'              => LeaveRequestStatus::Approved,
            'current_approver_id' => null,
        ]);

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
