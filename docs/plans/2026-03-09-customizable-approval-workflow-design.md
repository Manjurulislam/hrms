# Customizable Leave Approval Workflow — Design

## Problem

The current leave approval chain is hardcoded:
- Level 1 (CEO) always final approves
- Level 2 (CTO) can final approve or forward
- Level 3+ auto-forwards to manager

No admin UI exists to configure workflows. Approval rules can't vary per company or leave type.

## Solution

Database-driven workflow templates with configurable steps, assigned per company + leave type.

## Database Design

### New Tables

#### `approval_workflows`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | PK |
| name | string | e.g., "Standard Leave Approval" |
| company_id | FK | belongs to company |
| is_active | boolean | enable/disable workflow |
| timestamps | | |

#### `approval_workflow_steps`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | PK |
| workflow_id | FK | belongs to workflow |
| step_order | tinyint | 1, 2, 3... |
| approver_type | enum(ApproverType) | direct_manager, designation_level, specific_employee, department_head |
| approver_value | nullable int | designation level value or employee_id (null for direct_manager/department_head) |
| is_mandatory | boolean | required or skippable |
| condition_type | enum(StepConditionType) | always, days_greater_than, days_less_than |
| condition_value | nullable int | e.g., 3 for "if days > 3" |
| timestamps | | |

### Modified Tables

#### `leave_types`
- Add `approval_workflow_id` (nullable FK to `approval_workflows`)

### Unchanged Tables
- `leave_requests` — continues tracking runtime chain (current_approver_id, status)
- `leave_approvals` — continues as audit trail per approval step

## Enums

### `ApproverType`
- `direct_manager` → "Direct Manager"
- `designation_level` → "Designation Level"
- `specific_employee` → "Specific Employee"
- `department_head` → "Department Head"

### `StepConditionType`
- `always` → "Always"
- `days_greater_than` → "Days Greater Than"
- `days_less_than` → "Days Less Than"

## Service Logic

### On Leave Request Creation
1. Look up `leave_type.approval_workflow`
2. Evaluate each step's condition against `total_days`
3. Filter to applicable steps
4. Create first pending approval by resolving step 1's approver

### On Approval
1. Find the current step in the workflow
2. Move to the next applicable step
3. Resolve the next approver based on step's `approver_type`
4. If no more steps → final approve (deduct balance)

### Approver Resolution per Type
- `direct_manager` → employee's `manager_id`
- `designation_level` → walk up `manager_id` chain until matching level found
- `specific_employee` → use `approver_value` as employee_id
- `department_head` → employee's department head

### Fallback
If no workflow assigned to a leave type, use single-step direct manager approval (backward compatible).

## Admin UI

### Location
Under Company Settings

### Workflow Management
- CRUD for workflows within a company
- Step builder: add/remove/reorder steps
- Each step configures: approver type, value, mandatory toggle, condition type, condition value

### Leave Type Form
- Dropdown to select workflow from company's workflows

## Frontend Approval Page Changes

### approval-show.vue
- Remove hardcoded level-based buttons (Level 1/2/3+ logic)
- Show "Approve" and "Reject" for current approver regardless of level
- Remove "Forward to CEO" button — forwarding is automatic per workflow
- Timeline shows all workflow steps with status

## Key Files to Modify

### New Files
- `app/Enums/ApproverType.php`
- `app/Enums/StepConditionType.php`
- `app/Models/ApprovalWorkflow.php`
- `app/Models/ApprovalWorkflowStep.php`
- Migrations for new tables and leave_types column
- Controller + Vue pages for workflow admin UI

### Modified Files
- `app/Services/Backend/LeaveApprovalService.php` — replace hardcoded level logic with workflow-driven logic
- `app/Services/Backend/LeaveRequestService.php` — resolve first approver from workflow
- `app/Traits/ResolvesApprover.php` — simplify (no more level-based checks)
- `app/Models/LeaveType.php` — add workflow relationship
- `resources/js/Pages/Employee/Leave/approval-show.vue` — remove level-based buttons
- `resources/js/Pages/Backend/LeaveRequest/show.vue` — same button changes
