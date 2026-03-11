# Customizable Leave Approval Workflow — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the hardcoded leave approval chain with a database-driven, per-company, per-leave-type configurable workflow system.

**Architecture:** New `ApprovalWorkflow` and `ApprovalWorkflowStep` models define reusable approval chains. Each leave type can be assigned a workflow. On leave submission, the system evaluates step conditions and resolves approvers dynamically. The existing `leave_requests` and `leave_approvals` tables remain unchanged as the runtime execution layer.

**Tech Stack:** Laravel 11, Vue 3 + Vuetify + Inertia.js, MySQL

---

## Task 1: Create Enums

**Files:**
- Create: `app/Enums/ApproverType.php`
- Create: `app/Enums/StepConditionType.php`

**Step 1: Create ApproverType enum**

```php
<?php

namespace App\Enums;

enum ApproverType: string
{
    case DirectManager   = 'direct_manager';
    case DesignationLevel = 'designation_level';
    case SpecificEmployee = 'specific_employee';
    case DepartmentHead  = 'department_head';
}
```

**Step 2: Create StepConditionType enum**

```php
<?php

namespace App\Enums;

enum StepConditionType: string
{
    case Always          = 'always';
    case DaysGreaterThan = 'days_greater_than';
    case DaysLessThan    = 'days_less_than';
}
```

**Step 3: Commit**

```bash
git add app/Enums/ApproverType.php app/Enums/StepConditionType.php
git commit -m "feat: add ApproverType and StepConditionType enums for workflow system"
```

---

## Task 2: Create Migrations

**Files:**
- Create: `database/migrations/xxxx_create_approval_workflows_table.php`
- Create: `database/migrations/xxxx_create_approval_workflow_steps_table.php`
- Create: `database/migrations/xxxx_add_approval_workflow_id_to_leave_types_table.php`

**Step 1: Create approval_workflows migration**

```bash
php artisan make:migration create_approval_workflows_table
```

Migration content:

```php
Schema::create('approval_workflows', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Step 2: Create approval_workflow_steps migration**

```bash
php artisan make:migration create_approval_workflow_steps_table
```

Migration content:

```php
Schema::create('approval_workflow_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
    $table->unsignedTinyInteger('step_order');
    $table->string('approver_type', 30);
    $table->unsignedBigInteger('approver_value')->nullable();
    $table->boolean('is_mandatory')->default(true);
    $table->string('condition_type', 30)->default('always');
    $table->unsignedInteger('condition_value')->nullable();
    $table->timestamps();

    $table->index(['workflow_id', 'step_order']);
});
```

**Step 3: Create leave_types column migration**

```bash
php artisan make:migration add_approval_workflow_id_to_leave_types_table
```

Migration content:

```php
Schema::table('leave_types', function (Blueprint $table) {
    $table->foreignId('approval_workflow_id')->nullable()->after('company_id')
        ->constrained('approval_workflows')->nullOnDelete();
});
```

**Step 4: Run migrations**

```bash
php artisan migrate
```

Expected: 3 migrations run successfully.

**Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: add approval_workflows, workflow_steps tables and leave_types FK"
```

---

## Task 3: Create Models

**Files:**
- Create: `app/Models/ApprovalWorkflow.php`
- Create: `app/Models/ApprovalWorkflowStep.php`
- Modify: `app/Models/LeaveType.php` — add `approval_workflow_id` to fillable, add relationship
- Modify: `app/Models/Company.php` — add `approvalWorkflows()` relationship

**Step 1: Create ApprovalWorkflow model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'name',
        'company_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class, 'workflow_id')->orderBy('step_order');
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }
}
```

**Step 2: Create ApprovalWorkflowStep model**

```php
<?php

namespace App\Models;

use App\Enums\ApproverType;
use App\Enums\StepConditionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'step_order',
        'approver_type',
        'approver_value',
        'is_mandatory',
        'condition_type',
        'condition_value',
    ];

    protected $casts = [
        'step_order'     => 'integer',
        'approver_type'  => ApproverType::class,
        'is_mandatory'   => 'boolean',
        'condition_type' => StepConditionType::class,
        'condition_value' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }
}
```

**Step 3: Update LeaveType model**

In `app/Models/LeaveType.php`, add `'approval_workflow_id'` to the `$fillable` array and add:

```php
public function approvalWorkflow(): BelongsTo
{
    return $this->belongsTo(ApprovalWorkflow::class);
}
```

**Step 4: Update Company model**

In `app/Models/Company.php`, add:

```php
public function approvalWorkflows(): HasMany
{
    return $this->hasMany(ApprovalWorkflow::class);
}
```

**Step 5: Commit**

```bash
git add app/Models/ApprovalWorkflow.php app/Models/ApprovalWorkflowStep.php app/Models/LeaveType.php app/Models/Company.php
git commit -m "feat: add ApprovalWorkflow and ApprovalWorkflowStep models with relationships"
```

---

## Task 4: Create Workflow Service

**Files:**
- Create: `app/Services/Backend/ApprovalWorkflowService.php`

This service handles CRUD for workflows and their steps.

**Step 1: Create the service**

```php
<?php

namespace App\Services\Backend;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Traits\PaginateQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    use PaginateQuery;

    public function list(Request $request): array
    {
        $query = ApprovalWorkflow::query()
            ->with(['company:id,name', 'steps' => fn($q) => $q->orderBy('step_order')])
            ->orderBy('created_at', 'desc');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $perPage = $request->integer('per_page', 50);
        $paginated = $perPage === -1 ? $query->get() : $query->paginate($perPage);

        if ($perPage === -1) {
            return ['data' => $paginated, 'total' => $paginated->count()];
        }

        return $paginated->toArray();
    }

    public function store(array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($data) {
            $workflow = ApprovalWorkflow::create([
                'name'       => $data['name'],
                'company_id' => $data['company_id'],
                'is_active'  => $data['is_active'] ?? true,
            ]);

            if (!empty($data['steps'])) {
                foreach ($data['steps'] as $index => $step) {
                    $workflow->steps()->create([
                        'step_order'      => $index + 1,
                        'approver_type'   => $step['approver_type'],
                        'approver_value'  => $step['approver_value'] ?? null,
                        'is_mandatory'    => $step['is_mandatory'] ?? true,
                        'condition_type'  => $step['condition_type'] ?? 'always',
                        'condition_value' => $step['condition_value'] ?? null,
                    ]);
                }
            }

            return $workflow->load('steps');
        });
    }

    public function update(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow, $data) {
            $workflow->update([
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Replace all steps
            $workflow->steps()->delete();

            if (!empty($data['steps'])) {
                foreach ($data['steps'] as $index => $step) {
                    $workflow->steps()->create([
                        'step_order'      => $index + 1,
                        'approver_type'   => $step['approver_type'],
                        'approver_value'  => $step['approver_value'] ?? null,
                        'is_mandatory'    => $step['is_mandatory'] ?? true,
                        'condition_type'  => $step['condition_type'] ?? 'always',
                        'condition_value' => $step['condition_value'] ?? null,
                    ]);
                }
            }

            return $workflow->load('steps');
        });
    }

    public function delete(ApprovalWorkflow $workflow): void
    {
        $workflow->delete();
    }

    public function toggle(ApprovalWorkflow $workflow): bool
    {
        $workflow->update(['is_active' => !$workflow->is_active]);

        return $workflow->is_active;
    }
}
```

**Step 2: Commit**

```bash
git add app/Services/Backend/ApprovalWorkflowService.php
git commit -m "feat: add ApprovalWorkflowService for workflow CRUD"
```

---

## Task 5: Refactor LeaveApprovalService — Workflow-Driven Logic

**Files:**
- Modify: `app/Services/Backend/LeaveApprovalService.php` — replace hardcoded level logic
- Modify: `app/Services/Backend/LeaveRequestService.php` — resolve first approver from workflow
- Modify: `app/Models/LeaveApproval.php` — add `workflow_step_id` tracking

**Step 1: Add workflow_step_id to leave_approvals**

Create migration:

```bash
php artisan make:migration add_workflow_step_id_to_leave_approvals_table
```

```php
Schema::table('leave_approvals', function (Blueprint $table) {
    $table->unsignedBigInteger('workflow_step_id')->nullable()->after('level');

    $table->foreign('workflow_step_id')
        ->references('id')->on('approval_workflow_steps')
        ->nullOnDelete();
});
```

Run: `php artisan migrate`

**Step 2: Update LeaveApproval model**

Add `'workflow_step_id'` to `$fillable` in `app/Models/LeaveApproval.php` and add:

```php
public function workflowStep(): BelongsTo
{
    return $this->belongsTo(ApprovalWorkflowStep::class, 'workflow_step_id');
}
```

**Step 3: Rewrite LeaveApprovalService**

Replace `app/Services/Backend/LeaveApprovalService.php` with:

```php
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
```

**Step 4: Update LeaveRequestService::store()**

In `app/Services/Backend/LeaveRequestService.php`, replace the `store()` method. Remove the hardcoded manager approval logic and delegate to `LeaveApprovalService::initializeApproval()`:

```php
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
```

**Step 5: Commit**

```bash
git add database/migrations/ app/Models/LeaveApproval.php app/Services/Backend/LeaveApprovalService.php app/Services/Backend/LeaveRequestService.php
git commit -m "feat: refactor approval service to use workflow-driven logic with fallback"
```

---

## Task 6: Update Controllers — Remove Forward Parameter

**Files:**
- Modify: `app/Http/Controllers/Backend/LeaveRequestController.php`
- Modify: `app/Http/Controllers/Employee/LeaveController.php`
- Modify: `app/Traits/ResolvesApprover.php`

**Step 1: Update LeaveRequestController::approve()**

In `app/Http/Controllers/Backend/LeaveRequestController.php`, update the `approve()` method. Remove `$request->boolean('forward')` parameter since forwarding is now automatic:

```php
public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
{
    try {
        if (!$this->canActOnLeaveRequest($leaveRequest)) {
            return back()->withErrors(['error' => 'You are not authorized to approve this request.']);
        }

        $approver = $this->getApproverEmployee($leaveRequest);

        $result = $this->approvalService->approve(
            $leaveRequest,
            $approver,
            $request->input('remarks')
        );

        return back()->with('success', $result['message']);
    } catch (Exception $e) {
        Log::error(__METHOD__, [$e->getMessage()]);

        return back()->withErrors(['error' => 'Failed to approve leave request.']);
    }
}
```

**Step 2: Update LeaveController::approve()**

In `app/Http/Controllers/Employee/LeaveController.php`, same change — remove `$request->boolean('forward')`:

```php
public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
{
    try {
        if (!$this->canActOnLeaveRequest($leaveRequest)) {
            return back()->withErrors(['error' => 'You are not authorized to approve this request.']);
        }

        $approver = $this->getApproverEmployee($leaveRequest);

        $result = $this->approvalService->approve(
            $leaveRequest,
            $approver,
            $request->input('remarks')
        );

        return to_route('emp-leave.approvals')->with('success', $result['message']);
    } catch (Exception $e) {
        Log::error(__METHOD__, [$e->getMessage()]);

        return back()->withErrors(['error' => 'Failed to approve leave request.']);
    }
}
```

**Step 3: Simplify ResolvesApprover trait**

In `app/Traits/ResolvesApprover.php`, remove `approverLevel` from the return since level-based button logic is being removed. Update `resolveApproverContext()`:

```php
protected function resolveApproverContext(LeaveRequest $leaveRequest): array
{
    $user = auth()->user();
    $employee = $user->employee;

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
```

**Step 4: Update show() methods in both controllers**

Update `LeaveRequestController::show()` and `LeaveController::showApproval()` to pass `isCurrentApprover` as a boolean (no longer an array with approverLevel):

In `LeaveRequestController::show()`:
```php
$isCurrentApprover = $this->resolveApproverContext($leaveRequest);

return Inertia::render('Backend/LeaveRequest/show', [
    'leaveRequest'      => $leaveRequest,
    'isCurrentApprover' => $isCurrentApprover,
]);
```

In `LeaveController::showApproval()`:
```php
$isCurrentApprover = $this->resolveApproverContext($leaveRequest);

return Inertia::render('Employee/Leave/approval-show', [
    'leaveRequest'      => $leaveRequest,
    'isCurrentApprover' => $isCurrentApprover,
]);
```

**Step 5: Commit**

```bash
git add app/Http/Controllers/Backend/LeaveRequestController.php app/Http/Controllers/Employee/LeaveController.php app/Traits/ResolvesApprover.php
git commit -m "feat: simplify controllers and trait — remove hardcoded level logic"
```

---

## Task 7: Update Frontend Approval Pages

**Files:**
- Modify: `resources/js/Pages/Employee/Leave/approval-show.vue`
- Modify: `resources/js/Pages/Backend/LeaveRequest/show.vue`

**Step 1: Simplify Employee approval-show.vue**

Replace the level-based button logic (lines 182-243) with simple Approve/Reject buttons. Remove `approverLevel` prop. Remove `forward` from the form:

Props change:
```js
const props = defineProps({
    leaveRequest: Object,
    isCurrentApprover: Boolean,
});
```

Form change:
```js
const approveForm = useForm({remarks: ''});
```

Approve function:
```js
const approve = () => {
    approveForm.remarks = remarks.value;
    approveForm.post(route('emp-leave.approvals.approve', props.leaveRequest.id));
};
```

Replace the action buttons block (lines 182-243) with:
```vue
<div class="d-flex flex-wrap ga-3">
    <v-btn
        :loading="approveForm.processing"
        color="success"
        prepend-icon="mdi-check"
        variant="flat"
        size="large"
        @click="approve"
    >
        Approve
    </v-btn>

    <v-btn
        color="error"
        prepend-icon="mdi-close"
        variant="outlined"
        size="large"
        @click="showRejectDialog = true"
    >
        Reject
    </v-btn>
</div>
```

**Step 2: Simplify Backend LeaveRequest/show.vue**

Same changes — remove `approverLevel` prop, remove `forward` from form, replace level-based buttons (lines 209-265) with simple Approve/Reject:

Props change:
```js
const props = defineProps({
    leaveRequest: Object,
    isCurrentApprover: Boolean,
});
```

Form/function changes:
```js
const approveForm = useForm({remarks: ''});

const approve = () => {
    approveForm.remarks = remarks.value;
    approveForm.post(route('leave-requests.approve', props.leaveRequest.id));
};
```

Replace action buttons block (lines 209-265) with:
```vue
<div class="d-flex ga-3">
    <v-btn
        :loading="approveForm.processing"
        color="success"
        prepend-icon="mdi-check"
        variant="flat"
        @click="approve"
    >
        Approve
    </v-btn>

    <v-btn
        color="error"
        prepend-icon="mdi-close"
        variant="flat"
        @click="showRejectDialog = true"
    >
        Reject
    </v-btn>
</div>
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Employee/Leave/approval-show.vue resources/js/Pages/Backend/LeaveRequest/show.vue
git commit -m "feat: simplify approval UI — remove hardcoded level buttons"
```

---

## Task 8: Create Workflow Admin Controller & Routes

**Files:**
- Create: `app/Http/Controllers/Backend/ApprovalWorkflowController.php`
- Create: `app/Http/Requests/ApprovalWorkflowRequest.php`
- Modify: `routes/backend/admin-routes.php`

**Step 1: Create the form request**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                       => 'required|string|max:255',
            'company_id'                 => 'required|exists:companies,id',
            'is_active'                  => 'boolean',
            'steps'                      => 'required|array|min:1',
            'steps.*.approver_type'      => 'required|string|in:direct_manager,designation_level,specific_employee,department_head',
            'steps.*.approver_value'     => 'nullable|integer',
            'steps.*.is_mandatory'       => 'boolean',
            'steps.*.condition_type'     => 'required|string|in:always,days_greater_than,days_less_than',
            'steps.*.condition_value'    => 'nullable|integer|min:1',
        ];
    }
}
```

**Step 2: Create the controller**

```php
<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalWorkflowRequest;
use App\Models\ApprovalWorkflow;
use App\Models\Employee;
use App\Enums\ApproverType;
use App\Enums\DesignationLevel;
use App\Enums\StepConditionType;
use App\Services\Backend\ApprovalWorkflowService;
use App\Services\Backend\SharedService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalWorkflowController extends Controller
{
    public function __construct(
        protected readonly ApprovalWorkflowService $service,
        protected readonly SharedService $shared
    ) {}

    public function index(): Response
    {
        $companies = $this->shared->companies();

        return Inertia::render('Backend/ApprovalWorkflow/index', [
            'companies'        => $companies,
            'defaultCompanyId' => $companies->first()?->id,
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/ApprovalWorkflow/create', $this->formData());
    }

    public function store(ApprovalWorkflowRequest $request): RedirectResponse
    {
        try {
            $this->service->store($request->validated());

            return to_route('approval-workflows.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create workflow.']);
        }
    }

    public function edit(ApprovalWorkflow $approvalWorkflow): Response
    {
        $approvalWorkflow->load('steps');

        return Inertia::render('Backend/ApprovalWorkflow/edit', array_merge(
            $this->formData(),
            ['workflow' => $approvalWorkflow]
        ));
    }

    public function update(ApprovalWorkflowRequest $request, ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        try {
            $this->service->update($approvalWorkflow, $request->validated());

            return to_route('approval-workflows.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update workflow.']);
        }
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        try {
            $this->service->delete($approvalWorkflow);

            return to_route('approval-workflows.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete workflow.']);
        }
    }

    public function toggleStatus(ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        try {
            $status = $this->service->toggle($approvalWorkflow);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }

    private function formData(): array
    {
        return [
            'companies'       => $this->shared->companies(),
            'approverTypes'   => collect(ApproverType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => ucwords(str_replace('_', ' ', $t->value)),
            ]),
            'conditionTypes'  => collect(StepConditionType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => ucwords(str_replace('_', ' ', $t->value)),
            ]),
            'designationLevels' => collect(DesignationLevel::cases())->map(fn($l) => [
                'value' => $l->value,
                'label' => $l->name,
            ]),
            'employees' => Employee::where('status', true)->get(['id', 'first_name', 'last_name']),
        ];
    }
}
```

**Step 3: Add routes**

In `routes/backend/admin-routes.php`, add the import and route group:

```php
use App\Http\Controllers\Backend\ApprovalWorkflowController;

Route::controller(ApprovalWorkflowController::class)->name('approval-workflows.')
    ->prefix('approval-workflows')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('get', 'get')->name('get');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('{approvalWorkflow}/edit', 'edit')->name('edit');
        Route::put('update/{approvalWorkflow}', 'update')->name('update');
        Route::post('{approvalWorkflow}/toggle-status', 'toggleStatus')->name('toggle-status');
        Route::delete('delete/{approvalWorkflow}', 'destroy')->name('destroy');
    });
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/Backend/ApprovalWorkflowController.php app/Http/Requests/ApprovalWorkflowRequest.php routes/backend/admin-routes.php
git commit -m "feat: add ApprovalWorkflow controller, request, and routes"
```

---

## Task 9: Create Workflow Admin Vue Pages — Index

**Files:**
- Create: `resources/js/Pages/Backend/ApprovalWorkflow/index.vue`

**Step 1: Create the index page**

Follow the same pattern as `Backend/LeaveType/index.vue`. Shows a data table with columns: SL, Name, Company, Steps Count, Status, Actions. Filter by company. Toggle status. Link to create/edit.

```vue
<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';

const props = defineProps({
    companies: Array,
    defaultCompanyId: Number,
});

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Name', key: 'name'},
        {title: 'Company', key: 'company'},
        {title: 'Steps', key: 'steps_count', align: 'center'},
        {title: 'Status', key: 'is_active', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '8%'},
    ],
    pagination: {itemsPerPage: 50, totalItems: 0},
    filters: {
        search: '',
        company_id: props.defaultCompanyId,
        per_page: 50,
    },
    serverItems: [],
    loading: true,
});

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    axios.get(route('approval-workflows.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data.map(item => ({
            ...item,
            steps_count: item.steps?.length || 0,
        }));
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.post(route('approval-workflows.toggle-status', item.id))
        .then(() => toast('Workflow status updated.'));
};

const handleSearch = () => {
    state.loading = true;
    getData(state.filters);
};
</script>

<template>
    <DefaultLayout>
        <Head title="Approval Workflows"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New', route: 'approval-workflows.create'}"
                        icon="mdi-plus"
                        title="Approval Workflows"
                    />

                    <v-card-text class="pb-0">
                        <v-row>
                            <v-col cols="12" md="4">
                                <v-select
                                    v-model="state.filters.company_id"
                                    :items="props.companies"
                                    clearable
                                    density="compact"
                                    item-title="name"
                                    item-value="id"
                                    label="Company"
                                    variant="outlined"
                                    @update:model-value="handleSearch"
                                />
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-text-field
                                    v-model="state.filters.search"
                                    clearable
                                    density="compact"
                                    label="Search"
                                    prepend-inner-icon="mdi-magnify"
                                    variant="outlined"
                                    @keyup.enter="handleSearch"
                                    @click:clear="handleSearch"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>

                    <v-card-text>
                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            density="compact"
                            item-value="name"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{index}">{{ index + 1 }}</template>
                            <template v-slot:item.company="{item}">
                                <v-chip v-if="item.company" color="primary" size="x-small" variant="tonal">
                                    {{ item.company.name }}
                                </v-chip>
                                <span v-else>-</span>
                            </template>
                            <template v-slot:item.steps_count="{item}">
                                <v-chip color="info" size="x-small" variant="tonal">
                                    {{ item.steps_count }} {{ item.steps_count === 1 ? 'step' : 'steps' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.is_active="{item}">
                                <v-switch
                                    v-model="item.is_active"
                                    color="success"
                                    density="compact"
                                    hide-details
                                    @change="() => toggleStatus(item)"
                                />
                            </template>
                            <template v-slot:item.actions="{item}">
                                <btn-link
                                    :route="route('approval-workflows.edit', item.id)"
                                    color="bg-darkprimary"
                                    icon="mdi-pencil"
                                />
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/Pages/Backend/ApprovalWorkflow/index.vue
git commit -m "feat: add approval workflow index page"
```

---

## Task 10: Create Workflow Admin Vue Pages — Create/Edit with Step Builder

**Files:**
- Create: `resources/js/Pages/Backend/ApprovalWorkflow/create.vue`
- Create: `resources/js/Pages/Backend/ApprovalWorkflow/edit.vue`

**Step 1: Create the create page with step builder**

This is the most complex UI component. It includes:
- Workflow name, company select, status toggle
- Dynamic step builder: add/remove/reorder steps
- Each step has: approver type dropdown, conditional approver_value field, mandatory toggle, condition type dropdown, conditional condition_value field

```vue
<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    companies: Array,
    approverTypes: Array,
    conditionTypes: Array,
    designationLevels: Array,
    employees: Array,
});

const form = useForm({
    name: '',
    company_id: null,
    is_active: true,
    steps: [
        {approver_type: 'direct_manager', approver_value: null, is_mandatory: true, condition_type: 'always', condition_value: null},
    ],
});

const addStep = () => {
    form.steps.push({
        approver_type: 'direct_manager',
        approver_value: null,
        is_mandatory: true,
        condition_type: 'always',
        condition_value: null,
    });
};

const removeStep = (index) => {
    if (form.steps.length > 1) {
        form.steps.splice(index, 1);
    }
};

const moveStep = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= form.steps.length) return;
    const temp = form.steps[index];
    form.steps[index] = form.steps[newIndex];
    form.steps[newIndex] = temp;
};

const needsApproverValue = (type) => ['designation_level', 'specific_employee'].includes(type);
const needsConditionValue = (type) => ['days_greater_than', 'days_less_than'].includes(type);

const submit = () => {
    form.post(route('approval-workflows.store'), {
        onSuccess: () => toast('Workflow created successfully.'),
        onError: () => toast.error('Something went wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Approval Workflow"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'approval-workflows.index', icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Create Approval Workflow"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4">
                            <!-- Basic Info -->
                            <v-row>
                                <v-col cols="12" md="4">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Workflow Name"
                                        placeholder="e.g., Standard Leave Approval"
                                        required
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-select
                                        v-model="form.company_id"
                                        :error-messages="form.errors.company_id"
                                        :items="companies"
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Company"
                                        required
                                        variant="outlined"
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <div class="mt-3">
                                        <v-label class="mb-2 font-weight-medium">Status</v-label>
                                        <div>
                                            <el-switch
                                                v-model="form.is_active"
                                                size="large"
                                                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                                            />
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>

                            <!-- Steps Builder -->
                            <v-divider class="my-4"/>
                            <div class="d-flex align-center justify-space-between mb-4">
                                <div class="text-subtitle-1 font-weight-bold">
                                    <v-icon size="20" class="mr-1">mdi-sitemap</v-icon>
                                    Approval Steps
                                </div>
                                <v-btn
                                    color="primary"
                                    prepend-icon="mdi-plus"
                                    size="small"
                                    variant="tonal"
                                    @click="addStep"
                                >
                                    Add Step
                                </v-btn>
                            </div>

                            <div v-if="form.errors.steps" class="text-error text-body-2 mb-2">
                                {{ form.errors.steps }}
                            </div>

                            <v-card
                                v-for="(step, index) in form.steps"
                                :key="index"
                                variant="outlined"
                                class="mb-3 pa-4"
                            >
                                <div class="d-flex align-center justify-space-between mb-3">
                                    <div class="text-subtitle-2 font-weight-bold">
                                        Step {{ index + 1 }}
                                    </div>
                                    <div class="d-flex ga-1">
                                        <v-btn
                                            :disabled="index === 0"
                                            icon="mdi-arrow-up"
                                            size="x-small"
                                            variant="text"
                                            @click="moveStep(index, -1)"
                                        />
                                        <v-btn
                                            :disabled="index === form.steps.length - 1"
                                            icon="mdi-arrow-down"
                                            size="x-small"
                                            variant="text"
                                            @click="moveStep(index, 1)"
                                        />
                                        <v-btn
                                            :disabled="form.steps.length <= 1"
                                            color="error"
                                            icon="mdi-delete"
                                            size="x-small"
                                            variant="text"
                                            @click="removeStep(index)"
                                        />
                                    </div>
                                </div>

                                <v-row dense>
                                    <!-- Approver Type -->
                                    <v-col cols="12" md="3">
                                        <v-select
                                            v-model="step.approver_type"
                                            :error-messages="form.errors[`steps.${index}.approver_type`]"
                                            :items="approverTypes"
                                            density="compact"
                                            item-title="label"
                                            item-value="value"
                                            label="Approver Type"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <!-- Approver Value (conditional) -->
                                    <v-col v-if="needsApproverValue(step.approver_type)" cols="12" md="3">
                                        <v-select
                                            v-if="step.approver_type === 'designation_level'"
                                            v-model="step.approver_value"
                                            :error-messages="form.errors[`steps.${index}.approver_value`]"
                                            :items="designationLevels"
                                            density="compact"
                                            item-title="label"
                                            item-value="value"
                                            label="Designation Level"
                                            variant="outlined"
                                        />
                                        <v-select
                                            v-else-if="step.approver_type === 'specific_employee'"
                                            v-model="step.approver_value"
                                            :error-messages="form.errors[`steps.${index}.approver_value`]"
                                            :items="employees"
                                            density="compact"
                                            :item-title="e => `${e.first_name} ${e.last_name}`"
                                            item-value="id"
                                            label="Select Employee"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <!-- Condition Type -->
                                    <v-col cols="12" md="2">
                                        <v-select
                                            v-model="step.condition_type"
                                            :error-messages="form.errors[`steps.${index}.condition_type`]"
                                            :items="conditionTypes"
                                            density="compact"
                                            item-title="label"
                                            item-value="value"
                                            label="Condition"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <!-- Condition Value (conditional) -->
                                    <v-col v-if="needsConditionValue(step.condition_type)" cols="12" md="2">
                                        <v-text-field
                                            v-model="step.condition_value"
                                            :error-messages="form.errors[`steps.${index}.condition_value`]"
                                            density="compact"
                                            label="Days"
                                            min="1"
                                            type="number"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <!-- Mandatory Toggle -->
                                    <v-col cols="12" md="2">
                                        <div class="mt-1">
                                            <v-label class="mb-1 text-caption">Mandatory</v-label>
                                            <div>
                                                <el-switch
                                                    v-model="step.is_mandatory"
                                                    size="small"
                                                    style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                                                />
                                            </div>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-card>
                        </v-card-text>

                        <v-divider/>
                        <v-card-actions>
                            <v-btn
                                :loading="form.processing"
                                class="text-none mb-4 mx-auto"
                                color="primary"
                                type="submit"
                                variant="flat"
                            >
                                Submit
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
```

**Step 2: Create the edit page**

Same as create but pre-populated with `workflow` prop data:

```vue
<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    workflow: Object,
    companies: Array,
    approverTypes: Array,
    conditionTypes: Array,
    designationLevels: Array,
    employees: Array,
});

const form = useForm({
    name: props.workflow.name,
    company_id: props.workflow.company_id,
    is_active: props.workflow.is_active,
    steps: props.workflow.steps.map(s => ({
        approver_type: s.approver_type,
        approver_value: s.approver_value,
        is_mandatory: s.is_mandatory,
        condition_type: s.condition_type,
        condition_value: s.condition_value,
    })),
});

const addStep = () => {
    form.steps.push({
        approver_type: 'direct_manager',
        approver_value: null,
        is_mandatory: true,
        condition_type: 'always',
        condition_value: null,
    });
};

const removeStep = (index) => {
    if (form.steps.length > 1) {
        form.steps.splice(index, 1);
    }
};

const moveStep = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= form.steps.length) return;
    const temp = form.steps[index];
    form.steps[index] = form.steps[newIndex];
    form.steps[newIndex] = temp;
};

const needsApproverValue = (type) => ['designation_level', 'specific_employee'].includes(type);
const needsConditionValue = (type) => ['days_greater_than', 'days_less_than'].includes(type);

const submit = () => {
    form.put(route('approval-workflows.update', props.workflow.id), {
        onSuccess: () => toast('Workflow updated successfully.'),
        onError: () => toast.error('Something went wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Approval Workflow"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'approval-workflows.index', icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit Approval Workflow"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4">
                            <v-row>
                                <v-col cols="12" md="4">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Workflow Name"
                                        placeholder="e.g., Standard Leave Approval"
                                        required
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-select
                                        v-model="form.company_id"
                                        :items="companies"
                                        density="compact"
                                        disabled
                                        item-title="name"
                                        item-value="id"
                                        label="Company"
                                        variant="outlined"
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <div class="mt-3">
                                        <v-label class="mb-2 font-weight-medium">Status</v-label>
                                        <div>
                                            <el-switch
                                                v-model="form.is_active"
                                                size="large"
                                                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                                            />
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>

                            <v-divider class="my-4"/>
                            <div class="d-flex align-center justify-space-between mb-4">
                                <div class="text-subtitle-1 font-weight-bold">
                                    <v-icon size="20" class="mr-1">mdi-sitemap</v-icon>
                                    Approval Steps
                                </div>
                                <v-btn color="primary" prepend-icon="mdi-plus" size="small" variant="tonal" @click="addStep">
                                    Add Step
                                </v-btn>
                            </div>

                            <div v-if="form.errors.steps" class="text-error text-body-2 mb-2">{{ form.errors.steps }}</div>

                            <v-card v-for="(step, index) in form.steps" :key="index" variant="outlined" class="mb-3 pa-4">
                                <div class="d-flex align-center justify-space-between mb-3">
                                    <div class="text-subtitle-2 font-weight-bold">Step {{ index + 1 }}</div>
                                    <div class="d-flex ga-1">
                                        <v-btn :disabled="index === 0" icon="mdi-arrow-up" size="x-small" variant="text" @click="moveStep(index, -1)"/>
                                        <v-btn :disabled="index === form.steps.length - 1" icon="mdi-arrow-down" size="x-small" variant="text" @click="moveStep(index, 1)"/>
                                        <v-btn :disabled="form.steps.length <= 1" color="error" icon="mdi-delete" size="x-small" variant="text" @click="removeStep(index)"/>
                                    </div>
                                </div>
                                <v-row dense>
                                    <v-col cols="12" md="3">
                                        <v-select v-model="step.approver_type" :items="approverTypes" density="compact" item-title="label" item-value="value" label="Approver Type" variant="outlined"/>
                                    </v-col>
                                    <v-col v-if="needsApproverValue(step.approver_type)" cols="12" md="3">
                                        <v-select v-if="step.approver_type === 'designation_level'" v-model="step.approver_value" :items="designationLevels" density="compact" item-title="label" item-value="value" label="Designation Level" variant="outlined"/>
                                        <v-select v-else-if="step.approver_type === 'specific_employee'" v-model="step.approver_value" :items="employees" density="compact" :item-title="e => `${e.first_name} ${e.last_name}`" item-value="id" label="Select Employee" variant="outlined"/>
                                    </v-col>
                                    <v-col cols="12" md="2">
                                        <v-select v-model="step.condition_type" :items="conditionTypes" density="compact" item-title="label" item-value="value" label="Condition" variant="outlined"/>
                                    </v-col>
                                    <v-col v-if="needsConditionValue(step.condition_type)" cols="12" md="2">
                                        <v-text-field v-model="step.condition_value" density="compact" label="Days" min="1" type="number" variant="outlined"/>
                                    </v-col>
                                    <v-col cols="12" md="2">
                                        <div class="mt-1">
                                            <v-label class="mb-1 text-caption">Mandatory</v-label>
                                            <div>
                                                <el-switch v-model="step.is_mandatory" size="small" style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"/>
                                            </div>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-card>
                        </v-card-text>

                        <v-divider/>
                        <v-card-actions>
                            <v-btn :loading="form.processing" class="text-none mb-4 mx-auto" color="primary" type="submit" variant="flat">
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Backend/ApprovalWorkflow/
git commit -m "feat: add approval workflow create and edit pages with step builder UI"
```

---

## Task 11: Update LeaveType Form — Add Workflow Selector

**Files:**
- Modify: `resources/js/Pages/Backend/LeaveType/create.vue`
- Modify: `resources/js/Pages/Backend/LeaveType/edit.vue`
- Modify: `app/Http/Controllers/Backend/LeaveTypeController.php` (pass workflows to form)
- Modify: `app/Http/Requests/LeaveTypeRequest.php` (if exists, add validation)

**Step 1: Update LeaveTypeController to pass workflows**

Check the controller and its `formData` / `create` / `edit` methods. Add `ApprovalWorkflow` data. In the `create()` and `edit()` methods, include:

```php
'workflows' => \App\Models\ApprovalWorkflow::where('is_active', true)->get(['id', 'name', 'company_id']),
```

**Step 2: Add workflow_id to LeaveType create.vue form**

Add to the form:
```js
approval_workflow_id: null,
```

Add to the template after company select:
```vue
<v-col cols="12" md="6">
    <v-select
        v-model="form.approval_workflow_id"
        :error-messages="form.errors.approval_workflow_id"
        :items="workflows.filter(w => !form.company_id || w.company_id === form.company_id)"
        clearable
        density="compact"
        item-title="name"
        item-value="id"
        label="Approval Workflow"
        variant="outlined"
    />
</v-col>
```

**Step 3: Same for edit.vue** — pre-populate `approval_workflow_id` from the leave type.

**Step 4: Update validation** — add `'approval_workflow_id' => 'nullable|exists:approval_workflows,id'` to the form request rules.

**Step 5: Commit**

```bash
git add resources/js/Pages/Backend/LeaveType/ app/Http/Controllers/Backend/LeaveTypeController.php app/Http/Requests/
git commit -m "feat: add workflow selector to leave type create/edit forms"
```

---

## Task 12: Build and Verify

**Step 1: Build frontend**

```bash
npm run build
```

Expected: No build errors.

**Step 2: Run migrations fresh (if on dev)**

```bash
php artisan migrate:fresh --seed
```

Or just `php artisan migrate` if keeping data.

**Step 3: Manual verification checklist**

- [ ] Create a workflow under Company Settings with 3 steps
- [ ] Assign workflow to a leave type
- [ ] Submit a leave request as an employee
- [ ] Verify first approver is resolved correctly
- [ ] Approve at each step, verify forwarding works
- [ ] Verify final approval deducts leave balance
- [ ] Test rejection stops the chain
- [ ] Test fallback (leave type with no workflow uses direct manager)
- [ ] Test conditional steps (days > 3 triggers extra step)
- [ ] Test non-mandatory step skip

**Step 4: Commit any fixes**

```bash
git add -A
git commit -m "fix: address issues found during verification"
```
