# Mobile Employee API (v1)

Stateless REST API for the native employee mobile app. Built with Laravel Sanctum
personal access tokens and the **Action + Pipeline** architecture defined in
[`docs/CODE_QUALITY.md`](../CODE_QUALITY.md) Part 2.

## Base URL & prefix

All endpoints are served under the `v1` prefix (set via `apiPrefix: 'v1'`), e.g.
`https://your-host/v1/auth/login`. There is **no** `/api` segment.

## Authentication

Obtain a token from `POST /v1/auth/login`, then send it on every protected request:

```
Authorization: Bearer <token>
Accept: application/json
```

Tokens do not auto-expire; call `POST /v1/auth/logout` to revoke the current one.

## Response envelope

Success:

```json
{ "success": true, "message": "…", "data": { } }
```

`data` is omitted when null. Paginated lists include Laravel's `links`/`meta`
alongside `data`.

Error:

```json
{ "success": false, "message": "…", "error": { "code": "GEN-…", "details": null } }
```

| Status | code               | When                                    |
|--------|--------------------|-----------------------------------------|
| 401    | GEN-UNAUTHORIZED   | Missing/invalid token                   |
| 403    | GEN-FORBIDDEN      | Authenticated but not allowed           |
| 404    | GEN-NOT-FOUND      | Resource not found / not visible        |
| 409    | GEN-CONFLICT       | State conflict (e.g. already checked in)|
| 422    | GEN-VALIDATION     | Field validation failed (`details` map) |
| 422    | GEN-WRONG-ARGS     | Business rule rejected the request      |
| 500    | GEN-API-ERROR      | Unexpected server error                 |

---

## Auth

### POST /v1/auth/login
Body: `email`, `password`, `device_name` (all required).
→ `200 { data: { token, employee } }`. Wrong credentials → `422`. Rate limited
after 5 failed attempts. Account with no linked employee → `403`.

### POST /v1/auth/logout  *(auth)*
Revokes the current token. → `200`.

### GET /v1/auth/me  *(auth)*
→ `200 { data: <employee> }` — `id, id_no, first_name, last_name, full_name,
email, phone, company, department, designation`.

---

## Dashboard

### GET /v1/dashboard  *(auth)*
→ `200 { data: { officeHours, monthlyStats, todayData } }`.

---

## Attendance  *(all auth)*

### GET /v1/attendance/today
→ `{ data: { date, sessions[], summary } }`.

### POST /v1/attendance/check-in
Body (all optional): `lat`, `long`, `note`, `location`.
→ `201 { data: <session> }`. Mobile check-in has no office-hours/network gate;
`lat`/`long` are stored only. Refused (duplicate session / on leave / max daily
sessions) → `422` or `409`.

### POST /v1/attendance/check-out
Body (all optional): `lat`, `long`, `note`, `location`.
→ `200 { data: { session, duration } }`. No active session → `409`.

### GET /v1/attendance/monthly?month=YYYY-MM
→ `{ data: { month, totals, days[] } }`. `month` required (`YYYY-MM`).

### GET /v1/attendance/records?months=N
`months` optional (1–12, default 3). → `{ data: { from, to, records[] } }`.

---

## Leave — self service  *(all auth)*

### GET /v1/leave?per_page=N
The employee's own requests (paginated). → `{ data: [ <leave> ], links, meta }`.

### GET /v1/leave/types
Active leave types for the employee's company. → `{ data: [ { id, name, max_per_year } ] }`.

### GET /v1/leave/balance?year=YYYY
→ `{ data: [ { id, name, total, used, remaining } ] }`. Defaults to current year.

### POST /v1/leave
Body: `leave_type_id`, `title`, `notes`, `started_at`, `ended_at` (all required;
`ended_at >= started_at`). → `201 { data: <leave> }`. Business rejection
(no working days / insufficient balance / overlap) → `422`.

### POST /v1/leave/{leaveRequest}/cancel
Only the owner, within the cancel window, before any approval action. → `200`.
Not owner → `403`; not cancellable → `422`.

---

## Leave — approvals (manager)  *(all auth)*

Authorization: the acting employee must be the request's `current_approver_id`
(super admins may also act). Acting on your own request is never allowed.

### GET /v1/leave/approvals
Requests awaiting this approver. → `{ data: [ <approval> ] }`.

### GET /v1/leave/approvals/{leaveRequest}
Single request with employee, type and approval timeline. Not the approver → `403`.

### POST /v1/leave/approvals/{leaveRequest}/approve
Body: `remarks` (optional). → `200 { message }`. Not authorized → `403`.

### POST /v1/leave/approvals/{leaveRequest}/reject
Body: `remarks` (required). → `200 { message }`. Not authorized → `403`.

---

## Notices  *(all auth)*

### GET /v1/notices?per_page=N
Notices visible to the employee (active, published, not expired, company-wide or
their department), newest first (paginated). → `{ data: [ <notice> ], links, meta }`.

### GET /v1/notices/{notice}
A single visible notice. Not visible → `404`.
