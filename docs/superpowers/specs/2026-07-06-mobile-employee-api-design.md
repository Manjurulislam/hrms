# Mobile Employee API — Design Spec (v1)

**Date:** 2026-07-06
**Status:** Approved (pending spec review)
**Goal:** Expose the existing employee panel as a stateless, token-authenticated REST API for a native mobile app, reusing the current service layer so mobile and web share one source of truth.

## 1. Scope

**In scope (v1):** authentication, employee profile, dashboard, attendance (check-in/out/status/history), leave (list, types, balance, apply, cancel) including manager approvals, and notices.

**Out of scope:** breaks (removed from the product), push notifications, admin-panel features, self-service password reset (may be added later).

## 2. Architecture

- **New stateless API layer.** Register API routing in `bootstrap/app.php` (`api: routes/api.php`). All routes live under prefix `/api/v1`.
- **Auth:** Laravel Sanctum personal access tokens (already installed). Add `Laravel\Sanctum\HasApiTokens` to `App\Models\User`.
- **Controllers:** new `App\Http\Controllers\Api\V1\*`, kept thin — they delegate to the existing services (`AttendanceService`, `LeaveRequestService`, `NoticeService`, `EmployeeDashboardService`). No business logic is duplicated or moved.
- **Serialization:** Eloquent API Resources (`EmployeeResource`, `AttendanceResource`, `LeaveResource`, `LeaveBalanceResource`, `NoticeResource`) shape all JSON.
- **Requests:** dedicated API FormRequests (e.g. `Api\ApiCheckInRequest`) validate input and return JSON `422`s. They do **not** apply the web-only gates (office-hours, office-network) — see §6.
- **Guard/scoping:** every route except `login` is behind `auth:sanctum`. Each controller resolves `$request->user()->employee` and scopes all data to that employee; a missing employee profile → `403`.

## 3. Authentication flow

- `POST /api/v1/auth/login` — body `{ email, password, device_name }`. Validates credentials against the existing user provider. On success returns `{ token, employee }`; the token is a Sanctum personal access token named by `device_name`. Invalid credentials → `422`.
- The app stores the token and sends `Authorization: Bearer <token>` on every request.
- `POST /api/v1/auth/logout` — revokes the **current** access token only (`$request->user()->currentAccessToken()->delete()`).
- `GET /api/v1/auth/me` — returns the authenticated employee profile.
- **Token lifetime:** no auto-expiry in v1 (valid until logout). A configurable TTL can be added later via Sanctum config without contract changes.

## 4. Endpoint surface (all under `/api/v1`)

### Auth / profile / dashboard
| Method | Path | Reuses | Notes |
|---|---|---|---|
| POST | `/auth/login` | user provider + Sanctum | issues token |
| POST | `/auth/logout` | Sanctum | revoke current token |
| GET | `/auth/me` | Employee | profile resource |
| GET | `/dashboard` | `EmployeeDashboardService` / `AttendanceService` | office hours, monthly stats, today's data |

### Attendance (no break endpoints)
| Method | Path | Reuses | Notes |
|---|---|---|---|
| GET | `/attendance/today` | `AttendanceService::getTodayCompleteData` | today's status/sessions |
| POST | `/attendance/check-in` | `AttendanceService::checkIn` | body `{ lat?, long?, note?, location?, device_name? }`; collects location, no location gate |
| POST | `/attendance/check-out` | `AttendanceService::checkOut` | body `{ lat?, long?, note? }` |
| GET | `/attendance/monthly?month=YYYY-MM` | `AttendanceService::getMonthlyData` | month grid |
| GET | `/attendance/records?months=3` | `AttendanceService::getAttendanceRecords` | history |

### Leave
| Method | Path | Reuses | Notes |
|---|---|---|---|
| GET | `/leave` | `LeaveRequestService::list` | my requests (paginated) |
| GET | `/leave/types` | `LeaveType` (company-scoped) | for the apply form |
| GET | `/leave/balance?year=YYYY` | `LeaveRequestService::getBalances` | remaining balances |
| POST | `/leave` | `LeaveRequestService` store flow | apply |
| POST | `/leave/{leaveRequest}/cancel` | existing cancel flow | respects the 3-day cancellation rule already in the service |
| GET | `/leave/approvals` | `LeaveRequestService::...` (getApprovals) | manager: pending list |
| GET | `/leave/approvals/{leaveRequest}` | existing showApproval data | manager: detail |
| POST | `/leave/approvals/{leaveRequest}/approve` | existing approve flow | manager action |
| POST | `/leave/approvals/{leaveRequest}/reject` | existing reject flow | manager action, requires reason |

### Notices
| Method | Path | Reuses | Notes |
|---|---|---|---|
| GET | `/notices` | `NoticeService::employeeNotices` | list (paginated) |
| GET | `/notices/{notice}` | existing show data | detail |

## 5. Response & error conventions

- Uniform envelope: `{ "success": bool, "message": string, "data": <object|array|null> }`. List endpoints include pagination meta under `data` (or a `meta` block) mirroring the existing paginated shape.
- Status codes:
  - `200` OK, `201` Created (check-in, leave apply).
  - `401` unauthenticated (missing/invalid token).
  - `403` authenticated but no employee profile / not authorized (e.g. non-manager hitting approvals).
  - `422` validation error — `{ success:false, message, errors: { field: [ ... ] } }`.
  - `404` not found (including route-model-bound records the employee may not access).
- The API never returns HTML redirects; existing web controllers that `to_route(...)` are **not** reused directly — the API controllers call the underlying services and format JSON.

## 6. Check-in behavior on mobile

Mobile check-in works **from anywhere, anytime**. It enforces only:
- the core integrity rules already in `AttendanceService::checkIn` — no duplicate active session, not on approved leave (holiday no longer blocks), and
- the daily **max-sessions** cap (light spam guard).

It does **not** apply the web-only office-hours or office-network gates. `lat`/`long`/`note`/`location` are collected and stored on the session for management visibility, never used to allow or deny check-in.

Because the web `CheckInRequest` bundles the gates we want to skip, mobile uses a separate lightweight `ApiCheckInRequest` (validates `lat` between -90..90, `long` between -180..180, `note` max length, optional `location`, `device_name`) and calls the service directly.

## 7. Authorization details

- Regular employee endpoints scope strictly to `$request->user()->employee->id`; route-model-bound records (`{leaveRequest}`, `{notice}`) are verified to belong to / be visible to that employee, returning `403`/`404` otherwise.
- Manager approval endpoints check the same manager condition the web `getApprovals`/`approve`/`reject` already use; non-managers get `403`.

## 8. Testing

Feature tests (per endpoint group), using Sanctum's `actingAs(..., ['*'])` where appropriate:
- **Auth:** valid login issues a token; invalid credentials → `422`; protected route without token → `401`; logout revokes the token.
- **Attendance:** check-in happy path creates a session and persists `lat/long`; check-in works on a holiday; second concurrent check-in blocked; check-out closes the session; monthly/records return the employee's data only.
- **Leave:** apply creates a request; cancel within/after the 3-day window behaves per service; types/balance return company-scoped data.
- **Approvals:** a manager can approve/reject; a non-manager gets `403`; approving another company's request is rejected.
- **Notices:** list + show return only notices visible to the employee.
- **Isolation:** employee A cannot read/act on employee B's attendance, leave, or notices.

## 9. Out-of-scope / future

- Push notifications (FCM/APNs) for notices and approvals.
- Self-service password reset / change password over the API.
- Token TTL / refresh-token rotation.
- API rate limiting tuning (start with Laravel's default `throttle:api`).

## 10. Deliverables checklist

1. `HasApiTokens` on `User`; API routing registered in `bootstrap/app.php`; `routes/api.php` with `/api/v1` group.
2. `Api\V1` controllers: `AuthController`, `ProfileController`/`DashboardController`, `AttendanceController`, `LeaveController`, `NoticeController`.
3. API Resources and `ApiCheckInRequest` (+ any other API requests).
4. Feature tests per §8.
5. Short API reference doc (endpoints, payloads, sample responses) for the mobile team.
