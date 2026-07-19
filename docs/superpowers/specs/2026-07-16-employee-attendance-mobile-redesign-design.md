# Employee Attendance — Mobile-App Redesign (Design)

**Date:** 2026-07-16
**Status:** Approved (mockup iterated and signed off)
**Mockup:** desktop-first, mobile-responsive; big circular Clock In/Out tap button hero,
two full-width stacked tables below (My Attendance + Team Members).

## Goal

Redesign the **employee self-service attendance page** (`resources/js/Pages/Employee/attendance.vue`
and its components) into a modern, mobile-app-style UI. Same page is used on **desktop web** and
inside the **mobile app** (webview), so it is desktop-first and fully responsive. All existing
attendance behaviour is preserved; one new read-only feature is added: a **Team Members** table
that a manager sees for their direct reports' today status.

## Non-Goals

- No change to attendance business logic: check-in/out flow, the 30s server sync, IP capture,
  office-hours progress math, or the (currently disabled) break feature.
- No change to the admin `Backend/Attendance` pages or the API attendance controllers.
- No new attendance columns/schema. Team Members is read-only, derived from existing data.
- No drill-into-member-history in v1 (today's status only).

## Approved Design (from the mockup)

Layout on **desktop (≥ ~880px)**: full-width page (~1120px max), hero as a two-part row, then two
full-width tables stacked. On **mobile (< ~880px)**: hero collapses to a centered single column,
tables reflow (horizontal scroll / stacked). Colours in the mockup are indicative and map to the
app's Vuetify theme tokens on build; semantic status colours are preserved.

### 1. Hero (reworked `trackerClock`)
- **Live clock** (h:mm:ss AM/PM) + full weekday, date.
- **Big circular gradient tap-button** as the primary action, with a hand-tap icon:
  - Idle → **CLOCK IN**, blue→indigo gradient (`primary`-family).
  - Working → **CLOCK OUT**, pink→magenta gradient.
  - A **thin ring** around the button shows office-hours progress (existing `progressPercentage`).
  - A subtle pulse behind the button (respects `prefers-reduced-motion`).
- **Work-duration** shown as a small live pill under the button (existing `workTimer` / `totalHours`).
- **Office hours** and **client IP** as pill chips (existing data).
- **Today** stats: **Clock In / Clock Out / Working Hours** (existing `startTime` / `endTime` /
  `totalHours`). Break stat is dropped (breaks disabled). On desktop these sit in the hero's right
  half; on mobile they become a 3-across row under the button.
- The "office hours completed" state chip is kept.

### 2. My Attendance (reworked `attendantRecords`)
- A **clean full-width table**, no row background, hairline dividers, **only the status is coloured**
  (a coloured pill). Columns: **Date · Day (full name) · In · Out · Hours · Status**.
- Keep the existing **Month picker**, **Specific Date**, **Status** filters and **Export** — restyled
  as plain outlined controls (no filled backgrounds).
- Keep expandable session/break detail (tap a row) — same data as today.
- Late check-in time is subtly emphasised (existing `late_minutes`).

### 3. Team Members (new, conditional)
- Renders **only when the employee has active direct reports** (`Employee::subordinates()`), i.e.
  managers/leads. Regular employees never see it.
- A summary line (**Present · Late · Absent** counts) + a **clean full-width table**, no row
  background, only status coloured. Columns: **Member (name + role) · In · Status**.
- **Today only.** Each report's status derives from their today `AttendanceSummary`:
  - `working` if checked in today and not yet checked out (has `first_check_in`, no `last_check_out`, not absent) → green "Working" pill.
  - otherwise the mapped day status: present / late / wfh / leave / holiday / weekend / absent
    (no summary today ⇒ absent), using the existing status→string/colour mapping.

### 4. Removed
- The **This Month** stat card (`empStats`) is removed from this page (it lives on the dashboard).
  The profile identity (name, designation) moves into a compact page header.

## Data / Backend

`EmployeeAttendanceController@index` currently passes `userInfo`, `officeHours`, `monthlyStats`,
`todayData`, `attendanceRecords`. Changes:

- **Drop** `monthlyStats` from this page's props (This Month removed). *(Leave the service method;
  the dashboard may still use it.)*
- **Add** `teamToday` prop from a new `AttendanceService::getTeamTodayStatus(Employee $employee): ?array`:
  - Returns `null` when the employee has no active subordinates.
  - Otherwise `{ present:int, late:int, absent:int, members: [ { id, name, role, check_in:string|null, status:string, status_label:string } ] }`.
  - Reports = `$employee->subordinates()->where('status', true)->with('designation:id,title')->orderBy('first_name')->get()`.
  - Each report's today status from their `AttendanceSummary` where `attendance_date = today` (single
    grouped query for all report ids), reusing the existing status→string mapping; derive `working`
    from `first_check_in` present + `last_check_out` null.
  - Summary counts: `present` = present + wfh + working; `late` = late; `absent` = absent + no-record.

No new routes are required for v1 (data ships with the page render). A refresh of team data can piggyback
on the existing `router.reload` used after check-in/out if desired, but is not required.

## Component Plan

- `resources/js/Pages/Employee/attendance.vue` — page shell: compact header (name/designation +
  avatar/status), hero, My Attendance, Team Members (v-if teamToday). Drops `empStats`.
- `resources/js/Components/modules/employee/attendance/trackerClock.vue` — reworked into the hero
  (all timers, clock, check-in/out logic, sync unchanged; template + styles redesigned).
- `resources/js/Components/modules/employee/attendance/attendantRecords.vue` — restyled to the clean
  full-width table (logic/filters/expand unchanged; presentation redesigned).
- `resources/js/Components/modules/employee/attendance/teamMembers.vue` — **new** presentational
  table (props: `team` object). No fetching in v1; receives `teamToday`.
- `empStats.vue` — no longer used by this page (left in repo; not imported here).

## Theme / Responsiveness

- Use Vuetify theme tokens (`primary`, `success`, `warning`, `error`, `info`, surface, etc.). The
  gradient hero button uses a `primary`-family gradient (idle) and a pink→magenta gradient (working)
  defined in the component's scoped styles.
- Single breakpoint around Vuetify `md`: two-part hero + full-width tables on desktop, single column
  + reflowed tables on mobile. Tables get `overflow-x:auto` so the app viewport never scrolls sideways.
- Respect `prefers-reduced-motion` for the pulse/ring transitions.

## Testing / Verification

- **Backend:** a feature/unit test for `getTeamTodayStatus`: returns `null` for an employee with no
  reports; returns correct counts + member statuses (present / late / absent / working) for a manager
  with reports whose today `AttendanceSummary` rows vary. (Env note: tests need a working test DB —
  this box lacks `pdo_sqlite`, so tests run in the user's configured environment.)
- **Frontend:** `npx vite build` compiles; then human interactive verification on desktop and a
  narrow (mobile) viewport — clock ticks, Clock In→Out toggles colour/label, ring reflects progress,
  My Attendance filters/export/expand work, Team Members shows only for a manager account and renders
  correct statuses.

## Risks

- The `working` derivation assumes `AttendanceSummary` exposes `last_check_out`; confirm the field
  name during implementation (fallback: treat any today record with `first_check_in` and non-absent
  status as present).
- Team query must be a single grouped lookup over report ids (avoid N+1).
- Reworking `trackerClock`'s template must not disturb its timer/sync logic — restyle only.
