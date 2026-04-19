# Leave Request & Approval Flow

## Overview

The HRMS leave management system allows employees to apply for leave, which then flows through a configurable multi-step approval workflow. Each leave type can have its own approval workflow with conditional steps, multiple approver types, and mandatory/optional logic. Any approver in the chain can reject the request (which stops the flow), while approvals move through the chain until all steps are complete.

Super admins can act on any pending/in-review request regardless of the approval chain.

---

## Table of Contents

- [Designation Hierarchy](#designation-hierarchy)
- [Approval Workflow Configuration](#approval-workflow-configuration)
- [Complete Leave Request Flow](#complete-leave-request-flow)
- [Phase 1: Employee Submits Leave Request](#phase-1-employee-submits-leave-request)
- [Phase 2: Approval Workflow Initialization](#phase-2-approval-workflow-initialization)
- [Phase 3: Approver Takes Action](#phase-3-approver-takes-action)
- [Phase 4: Employee Cancellation](#phase-4-employee-cancellation)
- [Email Notifications](#email-notifications)
- [Authorization & Security](#authorization--security)
- [Status Transitions](#status-transitions)
- [Leave Balance Management](#leave-balance-management)
- [Database Schema](#database-schema)
- [API Routes](#api-routes)
- [Backend Services](#backend-services)
- [File Structure](#file-structure)
- [Example Walkthrough](#example-walkthrough)

---

## Designation Hierarchy

| Level | Role             | Enum Value                          |
|-------|------------------|-------------------------------------|
| 1     | Top Executive    | `DesignationLevel::TopExecutive`    |
| 2     | Senior Management| `DesignationLevel::SeniorManagement`|
| 3     | Middle Management| `DesignationLevel::MiddleManagement`|
| 4     | Team Lead        | `DesignationLevel::TeamLead`        |
| 5     | Senior Staff     | `DesignationLevel::SeniorStaff`     |
| 6     | Mid-Level Staff  | `DesignationLevel::MidLevelStaff`   |
| 7     | Junior Staff     | `DesignationLevel::JuniorStaff`     |
| 8     | Entry Level      | `DesignationLevel::EntryLevel`      |

Lower level number = higher rank. CEO (Level 1) is the highest.

---

## Approval Workflow Configuration

Each leave type can be linked to an `ApprovalWorkflow`, which contains ordered steps.

### Approver Types

| Type               | Enum Value         | How It Resolves                                                  |
|--------------------|--------------------|------------------------------------------------------------------|
| Direct Manager     | `direct_manager`   | Uses `employee.manager_id`                                      |
| Designation Level  | `designation_level`| Walks up the manager chain to find someone at/above target level |
| Specific Employee  | `specific_employee`| Hardcoded employee ID in `approver_value`                        |
| Department Head    | `department_head`  | Highest-ranked employee in the same department                   |

### Step Conditions

| Condition Type   | Enum Value          | Behavior                                      |
|------------------|---------------------|-----------------------------------------------|
| Always           | `always`            | Step always executes                           |
| Days Greater Than| `days_greater_than` | Only if `total_days > condition_value`         |
| Days Less Than   | `days_less_than`    | Only if `total_days < condition_value`         |

### Mandatory vs Optional Steps

- **Mandatory (`is_mandatory = true`)**: If the approver cannot be resolved, the system falls back to the direct manager. If no fallback is found, the request is auto-approved.
- **Optional (`is_mandatory = false`)**: If the approver cannot be resolved, the step is skipped and the system moves to the next applicable step.

---

## Complete Leave Request Flow

```
EMPLOYEE SUBMITS LEAVE REQUEST
        |
        v
+---------------------------------------+
| Validation                            |
| - Leave type exists                   |
| - Start date >= today                 |
| - End date >= start date              |
| - Sufficient leave balance            |
| - No overlapping leave requests       |
+---------------------------------------+
        |
        v
+---------------------------------------+
| Create LeaveRequest (status: Pending) |
+---------------------------------------+
        |
        v
+---------------------------------------+
| Initialize Approval Workflow          |
| - Find workflow for this leave type   |
| - Resolve first applicable step       |
| - Assign first approver              |
+---------------------------------------+
        |
        v
+---------------------------------------+
| Send Email Notifications              |
| - Employee: submission confirmation   |
| - First Approver: action needed       |
| - All CEOs: awareness                 |
+---------------------------------------+
        |
        v
+---------------------------------------+
| AWAITING APPROVER ACTION              |
+---+---------------+------------------+
    |               |                  |
    v               v                  v
 APPROVE         REJECT            EMPLOYEE
    |               |              CANCELS
    v               v                  |
 More steps?    Status: Rejected       v
    |               |              Status: Cancelled
  +-+-+          Email:             Email:
  |   |          - Employee          - Current Approver
  v   v          - All CEOs          - All CEOs
 YES  NO
  |    |
  v    v
Forward   Final Approve
to next   - Status: Approved
approver  - Deduct balance
  |       - Email: Employee + CEOs
  v
Email: Next Approver
Status: In Review
(repeat cycle)
```

---

## Phase 1: Employee Submits Leave Request

**Route:** `POST /leave/store`
**Controller:** `Employee/LeaveController::store()`
**Service:** `LeaveRequestService::store()`

### Step 1: Form Validation (`LeaveRequestFormRequest`)

| Field          | Rules                                      |
|----------------|--------------------------------------------|
| `leave_type_id`| Required, must exist in `leave_types` table|
| `started_at`   | Required, date, must be today or later     |
| `ended_at`     | Required, date, must be >= `started_at`    |
| `title`        | Optional, max 255 characters               |
| `notes`        | Optional, string                           |

### Step 2: Business Validation

1. **Calculate total days**: `end_date - start_date + 1`
2. **Check leave balance**: If `remaining < totalDays` -> error: *"Insufficient leave balance. You have X day(s) remaining."*
3. **Check overlapping dates**: No overlap with existing pending/in_review/approved leaves -> error: *"You already have a leave request that overlaps with the selected dates."*

### Step 3: Create Leave Request

Creates a `LeaveRequest` record with:
- `status = Pending`
- `current_approver_id = null` (set during workflow initialization)
- All data wrapped in a `DB::transaction`

---

## Phase 2: Approval Workflow Initialization

**Service:** `LeaveApprovalService::initializeApproval()`

```
LeaveType has active workflow?
|
+-- NO --> Fallback to employee's direct manager (employee.manager_id)
|
+-- YES --> Find first applicable step (based on total_days conditions)
            |
            +-- No applicable step found --> Auto-approve (finalApprove)
            |
            +-- Step found --> Resolve approver for this step
                              |
                              +-- Approver found --> Create LeaveApproval (Pending)
                              |                      Set current_approver_id
                              |
                              +-- Approver NOT found
                                  |
                                  +-- Step is optional --> Skip to next step
                                  |
                                  +-- Step is mandatory --> Fallback to direct manager
```

### Manager Chain Walking (Designation Level Resolution)

When the approver type is `designation_level`, the system walks up the manager hierarchy:

1. Collect all manager IDs in the chain (employee -> manager -> manager's manager -> ...)
2. Load all managers with their designations
3. Walk the chain from bottom to top
4. Return the first manager whose designation level is at or above the target level
5. Circular reference protection via visited set

---

## Phase 3: Approver Takes Action

### Path A: Approve

**Service:** `LeaveApprovalService::approve()`

1. Record approval in `leave_approvals` table (status: `Approved`, remarks, acted_at)
2. Check if leave type has a workflow
   - No workflow -> `finalApprove()`
3. Find next applicable workflow step after the current one
   - No next step -> `finalApprove()`
4. Resolve approver for next step
   - Approver found -> Forward to next step
   - Approver NOT found + step optional -> Skip (recursive, max depth 10)
   - Approver NOT found + step mandatory -> `finalApprove()`

### Final Approve

When all workflow steps are complete:

1. Update request status to `Approved`
2. Set `current_approver_id` to `null`
3. Deduct `total_days` from `LeaveBalance.used`
4. Send email to employee (approval confirmation) and all CEOs (awareness)

### Forward to Next Step

When forwarding to the next approver in the chain:

1. Create new `LeaveApproval` record (status: `Pending`) for next approver
2. Update request status to `InReview`
3. Set `current_approver_id` to next approver
4. Send email to the next approver (action needed)

### Path B: Reject

**Service:** `LeaveApprovalService::reject()`

1. Record rejection in `leave_approvals` table (status: `Rejected`, remarks, acted_at)
2. Update request status to `Rejected`
3. Set `current_approver_id` to `null`
4. Send email to employee (rejection with remarks) and all CEOs (awareness)

**Rejection at ANY level stops the entire workflow immediately.**

---

## Phase 4: Employee Cancellation

**Service:** `LeaveRequestService::cancel()`

### Conditions

- Request status must be `Pending` or `InReview`
- No approver must have already acted (approved or rejected)
  - If someone already acted -> error: *"This leave request has already been reviewed by an approver and cannot be cancelled."*

### On Success

1. Update request status to `Cancelled`
2. Send email to current approver (the request they were reviewing has been cancelled) and all CEOs (awareness)

**Note:** Leave balance is NOT affected because it was never deducted for pending/in-review requests.

---

## Email Notifications

### Notification Matrix

| Event                         | Employee (Submitter) | Current/Next Approver | All CEOs |
|-------------------------------|:--------------------:|:---------------------:|:--------:|
| Leave Request Submitted       | Confirmation         | Action needed          | Awareness|
| Forwarded to Next Approver    | -                    | Action needed          | -        |
| Final Approval                | Approved             | -                      | Awareness|
| Rejection (any level)         | Rejected + remarks   | -                      | Awareness|
| Employee Cancellation         | -                    | Cancelled notice       | Awareness|

### Mail Classes

| Class                        | Subject                                  | Template                        |
|------------------------------|------------------------------------------|---------------------------------|
| `LeaveRequestSubmittedMail`  | Leave Request Submitted                  | `emails.leave.submitted`        |
| `LeaveRequestApproverMail`   | New Leave Request Pending Your Approval  | `emails.leave.approver-notify`  |
| `LeaveRequestApprovedMail`   | Leave Request Approved                   | `emails.leave.approved`         |
| `LeaveRequestRejectedMail`   | Leave Request Rejected                   | `emails.leave.rejected`         |
| `LeaveRequestCancelledMail`  | Leave Request Cancelled                  | `emails.leave.cancelled`        |

### CEO Identification

CEOs are identified as employees with `DesignationLevel::TopExecutive` (level = 1) in the same company, who are active and have an email address.

### Notification Service Methods

| Method                       | Called From                              | Purpose                                                                     |
|------------------------------|------------------------------------------|-----------------------------------------------------------------------------|
| `leaveRequestCreated()`      | `LeaveRequestService::createLeaveRequest()` | Confirm employee, notify first approver, inform CEOs about new request   |
| `leaveRequestForwarded()`    | `LeaveApprovalService::forwardToStep()`  | Notify the next approver that a request is awaiting their action            |
| `leaveRequestApproved()`     | `LeaveApprovalService::finalApprove()`   | Inform employee of approval and balance deduction, inform CEOs              |
| `leaveRequestRejected()`     | `LeaveApprovalService::reject()`         | Inform employee of rejection with remarks, inform CEOs                      |
| `leaveRequestCancelled()`    | `LeaveRequestService::cancel()`          | Inform current approver and CEOs that employee cancelled the request        |

---

## Authorization & Security

### ResolvesApprover Trait

Used by both `Employee/LeaveController` and `Backend/LeaveRequestController`.

| Rule                          | Implementation                                                        |
|-------------------------------|-----------------------------------------------------------------------|
| Self-approval blocked         | `employee_id !== approver employee id` check                         |
| Only current approver can act | `current_approver_id === authenticated employee id`                   |
| Super admin override          | Can approve/reject any `Pending` or `InReview` request                |
| Route protection              | `menu.permission` middleware + `canActOnLeaveRequest()` method        |

### Key Methods

| Method                      | Description                                                           |
|-----------------------------|-----------------------------------------------------------------------|
| `resolveApproverContext()`  | Determines if the authenticated user is the current approver          |
| `getApproverEmployee()`    | Returns the Employee record for the acting approver                   |
| `canActOnLeaveRequest()`   | Full authorization check - status + identity + self-approval guard    |

---

## Status Transitions

### LeaveRequest Status

```
                          +------------+
                          |  Pending   |  (created, awaiting first approver)
                          +------+-----+
                                 |
               +-----------------+-----------------+
               |                 |                  |
          Employee            Approver           Approver
          cancels             approves            rejects
               |                 |                  |
               v                 v                  v
        +------------+   +------------+     +------------+
        | Cancelled  |   | In Review  |     |  Rejected  |
        +------------+   +------+-----+     +------------+
                                |
                    +-----------+-----------+
                    |                       |
               Next approver          Last approver
                approves                approves
                    |                       |
                    v                       v
             +------------+         +------------+
             | In Review  |         |  Approved  |
             |  (next)    |         | (balance   |
             +------------+         |  deducted) |
                                    +------------+
```

| Status      | Description                                                   |
|-------------|---------------------------------------------------------------|
| `pending`   | Initial state when employee submits                           |
| `in_review` | At least one approver has approved, waiting for next          |
| `approved`  | All workflow steps complete, leave balance deducted            |
| `rejected`  | Any approver rejected, chain stopped immediately              |
| `cancelled` | Employee cancelled before any approver acted                  |

### LeaveApproval Status

| Status     | Description                            |
|------------|----------------------------------------|
| `pending`  | Waiting for this approver to act       |
| `approved` | This approver approved the request     |
| `rejected` | This approver rejected the request     |

---

## Leave Balance Management

### How Balances Work

- Each employee has a `LeaveBalance` record per leave type per year
- `total`: Allocated days for the year (initialized from `leave_types.max_per_year`)
- `used`: Days consumed by approved leaves
- `remaining`: Computed as `total - used`

### When Balances Change

| Event                    | Effect on Balance                                        |
|--------------------------|----------------------------------------------------------|
| Leave request submitted  | Balance checked but NOT deducted                         |
| Leave approved (final)   | `used` incremented by `total_days`                       |
| Leave rejected           | No change (never deducted)                               |
| Leave cancelled          | No change (never deducted)                               |

### Auto-Creation

If no `LeaveBalance` record exists for an employee/leave-type/year combination, one is automatically created with `total = leave_type.max_per_year` and `used = 0`.

---

## Database Schema

### leave_requests

| Column              | Type     | Description                                            |
|---------------------|----------|--------------------------------------------------------|
| id                  | bigint   | Primary key                                            |
| title               | string   | Optional title/subject                                 |
| notes               | text     | Reason/notes for the leave                             |
| total_days          | integer  | Calculated: end_date - start_date + 1                  |
| company_id          | bigint   | FK -> companies                                        |
| employee_id         | bigint   | FK -> employees (who applied)                          |
| leave_type_id       | bigint   | FK -> leave_types                                      |
| current_approver_id | bigint   | FK -> employees (who needs to act next, null when done)|
| status              | enum     | pending / in_review / approved / rejected / cancelled  |
| started_at          | date     | Leave start date                                       |
| ended_at            | date     | Leave end date                                         |

### leave_approvals

| Column            | Type     | Description                              |
|-------------------|----------|------------------------------------------|
| id                | bigint   | Primary key                              |
| leave_request_id  | bigint   | FK -> leave_requests                     |
| approver_id       | bigint   | FK -> employees (who acted)              |
| workflow_step_id  | bigint   | FK -> approval_workflow_steps (nullable) |
| level             | integer  | Approver's designation level at the time |
| status            | enum     | pending / approved / rejected            |
| remarks           | text     | Optional remarks from approver           |
| acted_at          | datetime | When the action was taken                |

### leave_balances

| Column        | Type    | Description                          |
|---------------|---------|--------------------------------------|
| id            | bigint  | Primary key                          |
| employee_id   | bigint  | FK -> employees                      |
| leave_type_id | bigint  | FK -> leave_types                    |
| year          | integer | Calendar year                        |
| total         | integer | Total allocated days for the year    |
| used          | integer | Days used so far                     |
| remaining     | virtual | Computed: total - used               |

### leave_types

| Column               | Type    | Description                              |
|----------------------|---------|------------------------------------------|
| id                   | bigint  | Primary key                              |
| name                 | string  | e.g., "Sick Leave", "Casual Leave"       |
| max_per_year         | integer | Default annual quota                     |
| company_id           | bigint  | FK -> companies                          |
| approval_workflow_id | bigint  | FK -> approval_workflows (nullable)      |
| status               | boolean | Active/inactive                          |

### approval_workflows

| Column     | Type    | Description                    |
|------------|---------|--------------------------------|
| id         | bigint  | Primary key                    |
| name       | string  | Workflow name                  |
| company_id | bigint  | FK -> companies                |
| is_active  | boolean | Whether workflow is active     |

### approval_workflow_steps

| Column          | Type    | Description                                                      |
|-----------------|---------|------------------------------------------------------------------|
| id              | bigint  | Primary key                                                      |
| workflow_id     | bigint  | FK -> approval_workflows                                         |
| step_order      | integer | Execution order (1, 2, 3...)                                     |
| approver_type   | enum    | direct_manager / designation_level / specific_employee / department_head |
| approver_value  | string  | Value for the type (employee ID, designation ID, etc.)           |
| is_mandatory    | boolean | If true, must have approver or fallback to manager               |
| condition_type  | enum    | always / days_greater_than / days_less_than                      |
| condition_value | integer | Threshold for day-based conditions                               |

---

## API Routes

### Employee Portal

| Method | URI                                    | Name                     | Description                 |
|--------|----------------------------------------|--------------------------|-----------------------------|
| GET    | /leave                                 | emp-leave.index          | My leaves list page         |
| GET    | /leave/get                             | emp-leave.get            | JSON paginated data         |
| GET    | /leave/create                          | emp-leave.create         | Apply leave form            |
| POST   | /leave/store                           | emp-leave.store          | Submit leave request        |
| POST   | /leave/{leaveRequest}/cancel           | emp-leave.cancel         | Cancel pending request      |
| GET    | /leave/approvals                       | emp-leave.approvals      | Approvals pending from me   |
| GET    | /leave/approvals/get                   | emp-leave.approvals.get  | JSON paginated approvals    |
| GET    | /leave/approvals/{leaveRequest}        | emp-leave.approvals.show | Approval detail page        |
| POST   | /leave/approvals/{leaveRequest}/approve| emp-leave.approve        | Approve a request           |
| POST   | /leave/approvals/{leaveRequest}/reject | emp-leave.reject         | Reject a request            |

### Admin Panel

| Method | URI                                          | Name                    | Description                    |
|--------|----------------------------------------------|-------------------------|--------------------------------|
| GET    | /leave-requests                              | leave-requests.index    | All requests list              |
| GET    | /leave-requests/get                          | leave-requests.get      | JSON paginated data            |
| GET    | /leave-requests/{leaveRequest}/show          | leave-requests.show     | Detail + approval history      |
| POST   | /leave-requests/{leaveRequest}/approve       | leave-requests.approve  | Approve with optional remarks  |
| POST   | /leave-requests/{leaveRequest}/reject        | leave-requests.reject   | Reject with remarks            |

---

## Backend Services

### LeaveRequestService

Located at `app/Services/Backend/LeaveRequestService.php`

| Method          | Description                                                          |
|-----------------|----------------------------------------------------------------------|
| `list()`        | Paginated list with employee/leaveType/approver relations            |
| `store()`       | Validate balance + overlap, create request, init workflow, send email|
| `cancel()`      | Cancel own pending request if no approver has acted yet              |
| `getBalances()` | Get leave balances for an employee (auto-creates if missing)         |

### LeaveApprovalService

Located at `app/Services/Backend/LeaveApprovalService.php`

| Method                 | Description                                                        |
|------------------------|--------------------------------------------------------------------|
| `approve()`            | Approve and route to next step or finalize                         |
| `reject()`             | Reject and stop the chain                                          |
| `initializeApproval()` | Set up first approval step from workflow or fallback to manager    |

### NotificationService

Located at `app/Services/NotificationService.php`

| Method                     | Description                                                              |
|----------------------------|--------------------------------------------------------------------------|
| `leaveRequestCreated()`    | Email employee (confirmation), first approver (action), CEOs (awareness) |
| `leaveRequestForwarded()`  | Email next approver in the workflow chain (action needed)                |
| `leaveRequestApproved()`   | Email employee (approved + balance deducted), CEOs (awareness)           |
| `leaveRequestRejected()`   | Email employee (rejected + remarks), CEOs (awareness)                    |
| `leaveRequestCancelled()`  | Email current approver (cancelled), CEOs (awareness)                     |

---

## File Structure

```
app/
+-- Enums/
|   +-- LeaveRequestStatus.php        # pending, in_review, approved, rejected, cancelled
|   +-- LeaveApprovalStatus.php       # pending, approved, rejected
|   +-- ApproverType.php              # direct_manager, designation_level, specific_employee, department_head
|   +-- StepConditionType.php         # always, days_greater_than, days_less_than
|   +-- LeaveMessage.php              # User-facing messages
+-- Models/
|   +-- LeaveRequest.php
|   +-- LeaveApproval.php
|   +-- LeaveBalance.php
|   +-- LeaveType.php
|   +-- ApprovalWorkflow.php
|   +-- ApprovalWorkflowStep.php
+-- Services/
|   +-- NotificationService.php       # All email notification orchestration
|   +-- Backend/
|       +-- LeaveRequestService.php   # CRUD + balance management
|       +-- LeaveApprovalService.php  # Approval chain logic + workflow engine
+-- Mail/
|   +-- LeaveRequestSubmittedMail.php
|   +-- LeaveRequestApproverMail.php
|   +-- LeaveRequestApprovedMail.php
|   +-- LeaveRequestRejectedMail.php
|   +-- LeaveRequestCancelledMail.php
+-- Traits/
|   +-- ResolvesApprover.php          # Approver resolution + authorization
+-- Http/
    +-- Controllers/
    |   +-- Employee/LeaveController.php
    |   +-- Backend/LeaveRequestController.php
    +-- Requests/
        +-- LeaveRequestFormRequest.php

resources/
+-- views/emails/
|   +-- layout.blade.php              # Shared email layout (logo, styles, footer)
|   +-- leave/
|       +-- submitted.blade.php       # Submission confirmation
|       +-- approver-notify.blade.php # Approver action needed
|       +-- approved.blade.php        # Approval notification
|       +-- rejected.blade.php        # Rejection notification
|       +-- cancelled.blade.php       # Cancellation notification
+-- js/Pages/
    +-- Employee/Leave/
    |   +-- index.vue                 # My leaves list
    |   +-- create.vue                # Apply leave form
    |   +-- approvals.vue             # Pending approvals from me
    |   +-- approval-show.vue         # Approval detail + action buttons
    +-- Backend/LeaveRequest/
        +-- index.vue                 # All requests list (admin)
        +-- show.vue                  # Detail + approve/reject (admin)
```

---

## Example Walkthrough

### Scenario: 7-Day Sick Leave with 3-Step Workflow

**Setup:**
- Employee: John (Junior Staff, Level 7, Department: Engineering)
- Manager: Mary (Team Lead, Level 4)
- Sick Leave workflow has 3 steps:
  - Step 1: `direct_manager`, condition: `always`, mandatory
  - Step 2: `designation_level` = Middle Management (Level 3), condition: `days_greater_than 5`, mandatory
  - Step 3: `department_head`, condition: `days_greater_than 5`, optional

**Flow:**

1. **John submits 7-day sick leave**
   - Balance checked: has 10 remaining (sufficient)
   - No overlapping leaves
   - `LeaveRequest` created: status = `pending`
   - Workflow initialized: Step 1 applies (`always`), resolves Mary (direct manager)
   - `LeaveApproval` created: approver = Mary, status = `pending`
   - `current_approver_id` = Mary
   - Emails sent: John (confirmation), Mary (action needed), CEO (awareness)

2. **Mary (Team Lead) approves**
   - Her approval row: status = `approved`
   - Step 2 condition met (7 > 5): resolves by walking manager chain -> finds Bob (Manager, Level 3)
   - New `LeaveApproval`: approver = Bob, status = `pending`
   - Request: status = `in_review`, `current_approver_id` = Bob
   - Email sent: Bob (action needed)
   - Response: *"Approved and forwarded to Bob."*

3. **Bob (Manager) approves**
   - His approval row: status = `approved`
   - Step 3 condition met (7 > 5): resolves department head -> finds Alice (Senior Mgmt, Level 2)
   - New `LeaveApproval`: approver = Alice, status = `pending`
   - Request: status stays `in_review`, `current_approver_id` = Alice
   - Email sent: Alice (action needed)
   - Response: *"Approved and forwarded to Alice."*

4. **Alice (Senior Mgmt) approves**
   - Her approval row: status = `approved`
   - No more workflow steps -> `finalApprove()`
   - Request: status = `approved`, `current_approver_id` = null
   - Leave balance: `used` goes from 0 to 7, `remaining` drops to 3
   - Emails sent: John (approved), CEO (awareness)

### Alternative: Rejection at Step 2

If Bob rejects at step 2:
- Bob's approval row: status = `rejected`, remarks = "Insufficient team coverage"
- Request: status = `rejected`
- Emails sent: John (rejected with remarks), CEO (awareness)
- **Chain stops. No further approvals.**

### Alternative: Employee Cancels

If John cancels before Mary acts:
- Request: status = `cancelled`
- Emails sent: Mary (cancelled notice), CEO (awareness)
- Leave balance: unchanged

### Alternative: No Workflow Configured

If the leave type has no workflow or the workflow is inactive:
- System falls back to `employee.manager_id` as the sole approver
- One-step approval: manager approves -> `finalApprove()`
