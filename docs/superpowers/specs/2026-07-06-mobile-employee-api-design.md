# Mobile Employee API — Design Spec (v1)

**Date:** 2026-07-06
**Status:** Approved (pending spec review)
**Goal:** Expose the existing employee panel as a stateless, token-authenticated REST API for a native mobile app, reusing the current service layer so mobile and web share one source of truth.

**Conventions:** This API follows the established **hibachef** API structure (`/home/dev/Projects/hibachef`) — the `ResponseHandler` facade, `apiPrefix: 'v1'` routing, per-domain route files, and single-action invokable controllers. See §2 and §5.

## 1. Scope

**In scope (v1):** authentication, employee profile, dashboard, attendance (check-in/out/status/history), leave (list, types, balance, apply, cancel) including manager approvals, and notices.

**Out of scope:** breaks (removed from the product), push notifications, admin-panel features, self-service password reset (may be added later).

## 2. Architecture (matches hibachef)

- **API routing** registered in `bootstrap/app.php` via `->withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'v1')`. Effective prefix is **`/v1`** (not `/api`), matching hibachef.
- **`routes/api.php`** only `require`s per-domain files under `routes/apis/`:
  - `routes/apis/auth-routes.php`, `attendance-routes.php`, `leave-routes.php`, `notice-routes.php`.
- **Controllers:** **single-action invokable** controllers (`__invoke`) under `App\Http\Controllers\Api\<Domain>\<Action>Controller`, mirroring hibachef (e.g. `Api\Booking\ShowReservationController`). Each wraps its work in try/catch, logs failures with `Log::error`, and returns through the `ResponseHandler` facade.
- **Auth:** Laravel Sanctum personal access tokens (already installed). Add `Laravel\Sanctum\HasApiTokens` to `App\Models\User`. Protected routes use `auth:sanctum`.
- **Business logic:** controllers stay thin and delegate to existing services (`AttendanceService`, `LeaveRequestService`, `NoticeService`, `EmployeeDashboardService`). No logic duplicated or moved.
- **Serialization:** Eloquent API Resources (`EmployeeResource`, `AttendanceResource`, `LeaveResource`, `LeaveBalanceResource`, `NoticeResource`) returned via `ResponseHandler::respondWithResource` / `respondWithPagination`.
- **Scoping:** each controller resolves `$request->user()->employee` and scopes data to that employee; a missing employee profile → `errorForbidden` (`403`).

## 3. Response layer — ported from hibachef

Port these three pieces verbatim (adjusting namespaces only if needed):

1. `app/Facades/ResponseHandler.php` — facade with accessor `'api-response'`.
2. `app/Services/Lock/ServiceApiResponse.php` — the implementation.
3. Bind in `app/Providers/AppServiceProvider::register()`:
   ```php
   $this->app->singleton('api-response', fn () => new \App\Services\Lock\ServiceApiResponse());
   ```

**Success envelope:** `{ "success": true, "message": string, "data": <object|array> }` (the `data` key is omitted when null).

**Error envelope:** `{ "success": false, "message": string, "error": { "code": string, "details": mixed } }`.

**Facade methods used:** `success()`, `created()`, `respondWithResource()`, `respondWithPagination()`, `respondWithMessage()`, `noContent()`, `errorValidation($errors)`, `errorForbidden()`, `errorUnauthorized()`, `errorNotFound()`, `errorConflict()`, `errorInternalError()`, `customError()`. Error codes follow the `GEN-*` scheme already defined (`GEN-VALIDATION`, `GEN-NOT-FOUND`, `GEN-FORBIDDEN`, `GEN-UNAUTHORIZED`, `GEN-CONFLICT`, `GEN-API-ERROR`).

**Global handling:** API validation exceptions map to `errorValidation` (422 with `error.code = GEN-VALIDATION`, `error.details = errors`), auth failures to `errorUnauthorized` (401), model-not-found to `errorNotFound` (404), via the exception handler in `bootstrap/app.php` so controllers stay clean.

## 4. Authentication flow

- `POST /v1/auth/login` — `Api\Auth\LoginController` — body `{ email, password, device_name }`. Validates credentials; on success returns `success({ token, employee })` where the token is a Sanctum personal access token named by `device_name`. Invalid credentials → `errorValidation` / `errorUnauthorized`.
- `POST /v1/auth/logout` — `Api\Auth\LogoutController` — revokes the **current** token (`currentAccessToken()->delete()`) → `respondWithMessage`.
- `GET /v1/auth/me` — `Api\Auth\MeController` — returns the authenticated employee profile resource.
- **Token lifetime:** no auto-expiry in v1 (valid until logout); a configurable TTL can be added later via Sanctum config without contract changes.

## 5. Endpoint surface (all under `/v1`)

Each row is one single-action invokable controller.

### Auth / profile / dashboard
| Method | Path | Controller | Reuses |
|---|---|---|---|
| POST | `/auth/login` | `Api\Auth\LoginController` | user provider + Sanctum |
| POST | `/auth/logout` | `Api\Auth\LogoutController` | Sanctum |
| GET | `/auth/me` | `Api\Auth\MeController` | Employee |
| GET | `/dashboard` | `Api\Dashboard\DashboardController` | `EmployeeDashboardService` / `AttendanceService` |

### Attendance (no break endpoints)
| Method | Path | Controller | Reuses |
|---|---|---|---|
| GET | `/attendance/today` | `Api\Attendance\TodayStatusController` | `AttendanceService::getTodayCompleteData` |
| POST | `/attendance/check-in` | `Api\Attendance\CheckInController` | `AttendanceService::checkIn` |
| POST | `/attendance/check-out` | `Api\Attendance\CheckOutController` | `AttendanceService::checkOut` |
| GET | `/attendance/monthly?month=YYYY-MM` | `Api\Attendance\MonthlyController` | `AttendanceService::getMonthlyData` |
| GET | `/attendance/records?months=3` | `Api\Attendance\RecordsController` | `AttendanceService::getAttendanceRecords` |

### Leave
| Method | Path | Controller | Reuses |
|---|---|---|---|
| GET | `/leave` | `Api\Leave\LeaveListController` | `LeaveRequestService::list` |
| GET | `/leave/types` | `Api\Leave\LeaveTypeListController` | `LeaveType` (company-scoped) |
| GET | `/leave/balance?year=YYYY` | `Api\Leave\LeaveBalanceController` | `LeaveRequestService::getBalances` |
| POST | `/leave` | `Api\Leave\ApplyLeaveController` | store flow |
| POST | `/leave/{leaveRequest}/cancel` | `Api\Leave\CancelLeaveController` | cancel flow (3-day rule) |
| GET | `/leave/approvals` | `Api\Leave\ApprovalListController` | getApprovals |
| GET | `/leave/approvals/{leaveRequest}` | `Api\Leave\ShowApprovalController` | showApproval data |
| POST | `/leave/approvals/{leaveRequest}/approve` | `Api\Leave\ApproveLeaveController` | approve flow |
| POST | `/leave/approvals/{leaveRequest}/reject` | `Api\Leave\RejectLeaveController` | reject flow (reason required) |

### Notices
| Method | Path | Controller | Reuses |
|---|---|---|---|
| GET | `/notices` | `Api\Notice\NoticeListController` | `NoticeService::employeeNotices` |
| GET | `/notices/{notice}` | `Api\Notice\ShowNoticeController` | show data |

## 6. Check-in behavior on mobile

Mobile check-in works **from anywhere, anytime**. It enforces only:
- the core integrity rules already in `AttendanceService::checkIn` — no duplicate active session, not on approved leave (holiday no longer blocks), and
- the daily **max-sessions** cap (light spam guard).

It does **not** apply the web-only office-hours or office-network gates. `lat`/`long`/`note`/`location` are collected and stored on the session for management visibility, never used to allow or deny check-in.

Because the web `CheckInRequest` bundles the gates we want to skip, mobile uses a separate lightweight `Api\ApiCheckInRequest` (validates `lat` between -90..90, `long` between -180..180, `note` max length, optional `location`, `device_name`) and calls the service directly. FormRequest failures are rendered as `errorValidation` by the global handler.

## 7. Authorization details

- Regular employee endpoints scope strictly to `$request->user()->employee->id`; route-model-bound records (`{leaveRequest}`, `{notice}`) are verified to belong to / be visible to that employee, else `errorNotFound` / `errorForbidden`.
- Manager approval endpoints apply the same manager condition the web `getApprovals`/`approve`/`reject` already use; non-managers get `errorForbidden` (`403`).

## 8. Testing

Feature tests per endpoint group, using Sanctum's `Sanctum::actingAs(...)`:
- **Auth:** valid login issues a token; invalid credentials → `422` with `error.code = GEN-VALIDATION`; protected route without token → `401`; logout revokes the token.
- **Attendance:** check-in happy path creates a session and persists `lat/long`; check-in works on a holiday; second concurrent check-in blocked; check-out closes the session; monthly/records return only the caller's data.
- **Leave:** apply creates a request; cancel within/after the 3-day window behaves per service; types/balance return company-scoped data.
- **Approvals:** a manager can approve/reject; a non-manager gets `403`; approving another company's request is rejected.
- **Notices:** list + show return only notices visible to the employee.
- **Isolation:** employee A cannot read/act on employee B's attendance, leave, or notices.
- **Envelope:** responses conform to the `ResponseHandler` success/error shapes.

## 9. Out-of-scope / future

- Push notifications (FCM/APNs) for notices and approvals.
- Self-service password reset / change password over the API.
- Token TTL / refresh-token rotation.
- API rate limiting tuning (start with Laravel's default `throttle:api`).

## 10. Deliverables checklist

1. Port `ResponseHandler` facade + `ServiceApiResponse`; bind `api-response` in `AppServiceProvider`.
2. `HasApiTokens` on `User`; API routing (`apiPrefix: 'v1'`) + exception mapping in `bootstrap/app.php`; `routes/api.php` requiring per-domain files in `routes/apis/`.
3. Single-action `Api\<Domain>\*` controllers per §5.
4. API Resources + `ApiCheckInRequest` (and any other API requests).
5. Feature tests per §8.
6. Short API reference doc (endpoints, payloads, sample responses) for the mobile team.
