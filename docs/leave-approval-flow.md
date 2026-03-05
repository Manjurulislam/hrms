# Leave Request & Approval Flow

## Overview

The HRMS leave management system allows employees to apply for leave, which then flows through a hierarchical approval chain based on the company's designation structure. Any approver in the chain can reject the request (which stops the flow), while approvals move up the chain until a final approver closes it.

Super admins can act as top-level approvers on any pending/in-review request, regardless of the approval chain.

---

## Designation Hierarchy

| Level | Role           | Approval Behavior                          |
|-------|----------------|--------------------------------------------|
| 1     | CEO            | Always **final approve**                   |
| 2     | CTO            | Can **final approve** OR **forward to CEO** |
| 3     | Project Manager| Approve → auto-forwards to CTO             |
| 4     | Team Lead      | Approve → auto-forwards to PM              |
| 5+    | Developer/Staff| Cannot approve (submitters only)           |

---

## Approval Flow Diagram

```
Employee submits leave request
        │
        ▼
┌─────────────────┐
│  Direct Manager  │  (employee.manager_id)
│  (Team Lead)     │
├─────────────────┤
│ Approve │ Reject │
└────┬────┴────┬───┘
     │         │
     ▼         ▼
  Forward    REJECTED
  to PM      (flow stops)
     │
     ▼
┌─────────────────┐
│ Project Manager  │
├─────────────────┤
│ Approve │ Reject │
└────┬────┴────┬───┘
     │         │
     ▼         ▼
  Forward    REJECTED
  to CTO     (flow stops)
     │
     ▼
┌──────────────────────────┐
│         CTO (Level 2)     │
├──────────────────────────┤
│ Final    │ Forward │ Reject│
│ Approve  │ to CEO  │       │
└────┬─────┴────┬────┴───┬──┘
     │          │        │
     ▼          ▼        ▼
  APPROVED   Forward   REJECTED
  (closed)   to CEO    (flow stops)
             │
             ▼
┌─────────────────┐
│   CEO (Level 1)  │
├─────────────────┤
│ Approve │ Reject │
└────┬────┴────┬───┘
     │         │
     ▼         ▼
  APPROVED   REJECTED
  (closed)   (flow stops)

──────────────────────────────
Super Admin can intervene at
any step (acts as Level 1)
──────────────────────────────
```

---

## Database Schema

### leave_requests

| Column              | Type     | Description                              |
|---------------------|----------|------------------------------------------|
| id                  | bigint   | Primary key                              |
| title               | string   | Optional title/subject                   |
| notes               | text     | Reason/notes for the leave               |
| total_days          | integer  | Calculated: end_date - start_date + 1    |
| company_id          | bigint   | FK → companies                           |
| employee_id         | bigint   | FK → employees (who applied)             |
| leave_type_id       | bigint   | FK → leave_types                         |
| current_approver_id | bigint   | FK → employees (who needs to act next)   |
| status              | enum     | pending / in_review / approved / rejected / cancelled |
| started_at          | date     | Leave start date                         |
| ended_at            | date     | Leave end date                           |

### leave_approvals

| Column            | Type     | Description                              |
|-------------------|----------|------------------------------------------|
| id                | bigint   | Primary key                              |
| leave_request_id  | bigint   | FK → leave_requests                      |
| approver_id       | bigint   | FK → employees (who acted)               |
| level             | integer  | Approver's designation level at the time |
| status            | enum     | pending / approved / rejected            |
| remarks           | text     | Optional remarks from approver           |
| acted_at          | datetime | When the action was taken                |

### leave_balances

| Column        | Type    | Description                          |
|---------------|---------|--------------------------------------|
| id            | bigint  | Primary key                          |
| employee_id   | bigint  | FK → employees                       |
| leave_type_id | bigint  | FK → leave_types                     |
| year          | integer | Calendar year                        |
| total         | integer | Total allocated days for the year    |
| used          | integer | Days used so far                     |
| remaining     | virtual | Computed: total - used               |

### leave_types

| Column       | Type    | Description                    |
|--------------|---------|--------------------------------|
| id           | bigint  | Primary key                    |
| name         | string  | e.g., "Sick Leave", "Casual"   |
| max_per_year | integer | Default annual quota            |
| company_id   | bigint  | FK → companies                 |
| status       | boolean | Active/inactive                |

---

## Key Dependencies

The approval chain relies on two existing fields:

1. **`employees.manager_id`** — Points to the employee's direct manager. This creates the chain: Developer → Team Lead → PM → CTO → CEO.

2. **`designations.level`** — Determines approval behavior:
   - Level > 2: Auto-forward to `manager_id` on approve
   - Level 2 (CTO): Choice to final approve or forward
   - Level 1 (CEO): Always final approve

---

## Status Transitions

### LeaveRequest Status

```
pending ──► in_review ──► approved
  │             │
  │             ▼
  │          rejected
  ▼
cancelled
```

- **pending** → Initial state when employee submits
- **in_review** → At least one approver has approved, waiting for next
- **approved** → Final approver has approved (leave balance deducted)
- **rejected** → Any approver rejected (chain stops)
- **cancelled** → Employee cancelled their own pending request

### LeaveApproval Status

- **pending** → Waiting for this approver to act
- **approved** → Approver approved the request
- **rejected** → Approver rejected the request

---

## API Routes

### Employee Portal

| Method | URI                              | Name             | Description              |
|--------|----------------------------------|------------------|--------------------------|
| GET    | /leave                           | emp-leave.index  | My leaves list page      |
| GET    | /leave/get                       | emp-leave.get    | JSON paginated data      |
| GET    | /leave/create                    | emp-leave.create | Apply leave form         |
| POST   | /leave/store                     | emp-leave.store  | Submit leave request     |
| POST   | /leave/{leaveRequest}/cancel     | emp-leave.cancel | Cancel pending request   |

### Admin Panel

| Method | URI                                          | Name                    | Description              |
|--------|----------------------------------------------|-------------------------|--------------------------|
| GET    | /leave-requests                              | leave-requests.index    | All requests list        |
| GET    | /leave-requests/get                          | leave-requests.get      | JSON paginated data      |
| GET    | /leave-requests/{leaveRequest}/show          | leave-requests.show     | Detail + approval history|
| POST   | /leave-requests/{leaveRequest}/approve       | leave-requests.approve  | Approve (with optional forward) |
| POST   | /leave-requests/{leaveRequest}/reject        | leave-requests.reject   | Reject with remarks      |

---

## Backend Services

### LeaveRequestService

Located at `app/Services/Backend/LeaveRequestService.php`

| Method       | Description                                              |
|--------------|----------------------------------------------------------|
| `list()`     | Paginated list with employee/leaveType/approver relations|
| `store()`    | Create request, set first approver, create approval row  |
| `cancel()`   | Cancel own pending request                               |
| `getBalances()` | Get leave balances for an employee (auto-creates if missing) |

### LeaveApprovalService

Located at `app/Services/Backend/LeaveApprovalService.php`

| Method      | Description                                               |
|-------------|-----------------------------------------------------------|
| `approve()` | Approve and route based on approver's designation level   |
| `reject()`  | Reject and stop the chain                                 |

#### Approve Logic

```php
public function approve(LeaveRequest $leaveRequest, Employee $approver, ?string $remarks, bool $forward)
```

1. Updates/creates the approval row with status `approved`
2. **Level 1 (CEO)**: Always calls `finalApprove()` → status = approved, balance deducted
3. **Level 2 (CTO)**:
   - If `$forward = true` and has `manager_id`: forwards to CEO
   - Otherwise: calls `finalApprove()`
4. **Level > 2 (Team Lead, PM)**: Auto-forwards to their `manager_id`
5. If no manager above: calls `finalApprove()` as fallback

#### Final Approve

- Sets request status to `approved`
- Sets `current_approver_id` to `null`
- Deducts `total_days` from the employee's `LeaveBalance`

---

## Traits

### ResolvesApprover

Located at `app/Traits/ResolvesApprover.php`

Used by `LeaveRequestController` to handle approver resolution logic.

| Method                     | Description                                                      |
|----------------------------|------------------------------------------------------------------|
| `resolveApproverContext()` | Returns `[isCurrentApprover, approverLevel]` for the show page   |
| `getApproverEmployee()`   | Resolves the correct Employee record for approval/rejection      |
| `canActOnLeaveRequest()`  | Authorization check — is the user allowed to approve/reject?     |

**Super admin handling:**
- `resolveApproverContext()` checks if the request status is `pending` or `in_review` using `collect()->contains()` — if so, super admin is treated as level 1 approver
- `getApproverEmployee()` returns the super admin's own employee record if they have one, otherwise falls back to the `current_approver_id` employee
- `canActOnLeaveRequest()` allows super admin to act on any actionable request

---

## Frontend Pages

### Employee Portal

| Page                               | Description                                          |
|------------------------------------|------------------------------------------------------|
| `Employee/Leave/index.vue`         | Data table of employee's leave requests with status filter. Cancel button for pending requests. |
| `Employee/Leave/create.vue`        | Leave application form with clickable balance cards, date pickers, balance validation alert.    |

### Admin Panel

| Page                               | Description                                          |
|------------------------------------|------------------------------------------------------|
| `Backend/LeaveRequest/index.vue`   | All leave requests with company/employee/type/status filters. View action links to detail page. |
| `Backend/LeaveRequest/show.vue`    | Leave detail card, approval timeline, and action buttons based on approver level.               |

#### Action Buttons (show.vue)

Only visible when the logged-in user is the `current_approver` or a super admin:

| Approver Level | Buttons Available                           |
|----------------|---------------------------------------------|
| > 2 (TL/PM)   | **Approve** (auto-forwards) + **Reject**    |
| 2 (CTO)       | **Final Approve** + **Approve & Forward to CEO** + **Reject** |
| 1 (CEO/Super Admin) | **Final Approve** + **Reject**        |

---

## Menu Configuration

### Employee Menu (`config/services/emp-menus.php`)

```
Leave
├── My Leaves    → emp-leave.index
└── Apply Leave  → emp-leave.create
```

### Admin Menu (`config/services/menus.php`)

```
Leave
├── Leave Requests → leave-requests.index
└── Leave Types    → leave-types.index
```

---

## File Structure

```
app/
├── Enums/
│   ├── LeaveRequestStatus.php      # pending, in_review, approved, rejected, cancelled
│   └── LeaveApprovalStatus.php     # pending, approved, rejected
├── Models/
│   ├── LeaveRequest.php
│   ├── LeaveApproval.php
│   ├── LeaveBalance.php
│   └── LeaveType.php
├── Services/Backend/
│   ├── LeaveRequestService.php     # CRUD + balance management
│   └── LeaveApprovalService.php    # Approval chain logic
├── Traits/
│   └── ResolvesApprover.php        # Approver resolution logic
├── Http/
│   ├── Controllers/
│   │   ├── Employee/LeaveController.php
│   │   └── Backend/LeaveRequestController.php
│   └── Requests/
│       └── LeaveRequestFormRequest.php
resources/js/Pages/
├── Employee/Leave/
│   ├── index.vue                    # My leaves list
│   └── create.vue                   # Apply leave form
└── Backend/LeaveRequest/
    ├── index.vue                    # All requests list
    └── show.vue                     # Detail + approve/reject
```

---

## Example Walkthrough

1. **Hasib (Frontend Dev, Level 5)** applies for 3 days sick leave
   - Request created: status = `pending`, current_approver = Hasib's manager (Manjurul, Team Lead)
   - LeaveApproval row created: approver = Manjurul, status = `pending`

2. **Manjurul (Team Lead, Level 4)** sees the request → clicks **Approve**
   - His approval row updated: status = `approved`
   - Auto-forwards to his manager (Kabir, PM)
   - New LeaveApproval row: approver = Kabir, status = `pending`
   - Request status → `in_review`, current_approver → Kabir

3. **Kabir (PM, Level 3)** sees the request → clicks **Approve**
   - His approval row updated: status = `approved`
   - Auto-forwards to his manager (Mafuz, CTO)
   - New LeaveApproval row: approver = Mafuz, status = `pending`
   - Request status stays `in_review`, current_approver → Mafuz

4. **Mafuz (CTO, Level 2)** has two choices:
   - **Final Approve**: Request status → `approved`, balance deducted, done
   - **Approve & Forward to CEO**: Forwards to Moinur (CEO)

5. If forwarded: **Moinur (CEO, Level 1)** → clicks **Final Approve**
   - Request status → `approved`, balance deducted, done

6. **At any step**: If an approver clicks **Reject** → request status = `rejected`, chain stops immediately

7. **Super Admin** can intervene at any step on pending/in_review requests — acts as Level 1 (final approver)
