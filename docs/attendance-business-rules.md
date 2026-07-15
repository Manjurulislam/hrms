# Attendance Business Rules

**Status:** Draft (target behaviour) · **Date:** 2026-07-15

This document defines the complete, intended business rules for attendance. It is
the reference we fix the code toward. Where the current code diverges, it is
called out under **Current vs. Target**. Open decisions that need product sign-off
are marked **⟵ CONFIRM**.

Scope order follows setup → holidays → check-in/out → auto-close → status.

---

## 1. Company Setup (per-company configuration)

All attendance behaviour is driven by per-company settings. Every rule below reads
these values; nothing is hard-coded.

### 1.1 Core schedule

| Setting | Meaning | Default | Notes |
|---|---|---|---|
| `office_start` | Official work start time | 09:00 | Reference for lateness & window |
| `office_end` | Official work end time | 18:00 | Reference for early-leave, auto-close cap, and check-in cutoff |
| `work_hours` | Hours required for a full **Present** day | 8 | Status threshold |
| `half_day_hours` | Hours required for a **Half Day** | 4 | Status threshold |

### 1.2 Grace periods

| Setting | Meaning | Default | Applies to |
|---|---|---|---|
| `late_grace` | Minutes after `office_start` an arrival is still "on time" | **30** | Late detection → **Late status** |
| `early_grace` | Minutes before `office_end` a check-out is not "early leave" | 15 | Early-leave detection only |
| `check_in_open` | Earliest time of day check-in is allowed (before `office_start`) | **06:00** | Opens the check-in window (§3.1) — system default, no DB column (add per-company later if ever needed) |

**Late-grace policy (decided):**
- System default is **30 minutes**.
- Softwind Tech Ltd is set to **30 minutes** (on-time until 9:30 AM).
- **The 120-minute value is not allowed.** The setting is capped at a sane maximum
  (**60 min**) so no company can configure a 2-hour grace again.
- Arriving **after** `office_start + late_grace` makes the day **Late** (see §6).

> **Removed to simplify:** session & break limits (`max_sessions`,
> `min_session_gap`, `max_breaks`) are **not** part of the attendance rules.
> Employees may check in/out and take breaks any number of times per day.

### 1.3 Auto-close

| Setting | Meaning | Default |
|---|---|---|
| `auto_close` | Whether the system auto-closes forgotten sessions | true |
| `auto_close_at` | Time of day after which stale same-day sessions may be auto-closed | 23:59 |

Auto-close always caps the recorded check-out at `office_end`, so forgotten
check-outs never inflate worked hours (see §5).

### 1.4 Location / network

| Setting | Meaning | Default |
|---|---|---|
| `track_ip` + `office_ip` | Restrict **office** check-ins to the office network | true |
| `track_location` (GPS) | Require GPS coordinates on check-in | Disabled (future use) |

Office-network restriction applies to `location = office` only; remote/WFH sessions
are exempt.

### 1.5 Working days & weekends

Working days are configured per company via `CompanyWorkingDay` (a per-weekday
`is_working` flag). If unconfigured, Sat/Sun are treated as weekend. Weekends are
non-working days for status purposes.

**Attendance is allowed on off days.** Just like holidays (§2), employees **may**
check in/out on a weekend or any non-working day — off days never block check-in.
Hours worked on an off day are recorded as **overtime**, and the day's status stays
`Weekend` (see §6) so the calendar stays honest while management can still see who
worked.

---

## 2. Holidays

### 2.1 Definition

Holidays are per-company date ranges (`Holiday`: `start_date`..`end_date`,
`status` active). A date is a holiday if an active holiday range covers it.

### 2.2 Rules

1. **Holidays are non-working days** — like weekends, they do not require attendance
   and never count as Absent.
2. **A working-day check must treat an active holiday as non-working.**
   *(Target: `isWorkingDay()` returns false on holidays.)*
3. **Employees MAY check in on a holiday** (point 5 — management visibility). A
   holiday must not block check-in.
4. Hours worked on a holiday are recorded as **overtime** (non-working day → OT).

### 2.3 Current vs. Target

- **Current:** Holiday no longer blocks check-in (✅). But `isWorkingDay()` ignores
  holidays (❌), and no summary is ever tagged `Holiday` (❌).
- **Target:** `isWorkingDay()` accounts for holidays; the daily status reflects
  Holiday (see §6).

---

## 3. Check-in Rules

A check-in starts a new session. It is allowed only when **all** of the following pass:

1. **Within the check-in window** (§3.1).
2. **Daily office hours not already completed.** If total worked minutes today
   already ≥ scheduled office minutes, no new regular check-in.
3. **No active session** already open.
4. **Office network** matches `office_ip` when `track_ip` is on and the location is
   `office`.
5. **Not on approved leave** for that date.

Holidays and weekends do **not** block check-in (§2, §1.5). Overtime-typed sessions
bypass the completed-hours check so employees can keep working, but the check-in
window (§3.1) still applies to everyone.

### 3.1 Check-in window (point 4 — "no check-in after office hours")

Check-in is allowed **from early morning up to office end**, and **blocked after
office hours** through the night:

- **Opens at `check_in_open`** — an early-morning time before `office_start`
  (default **06:00**). Employees may start early.
- **Closes at `office_end`** — no check-in once office hours are over.
- **Blocked window:** after `office_end` until the next day's `check_in_open`
  (i.e. evening and night).

**Example** (office 09:00–18:00, `check_in_open` 06:00):

| Time of check-in | Allowed? |
|---|---|
| 06:00 AM | ✅ (early) |
| 08:45 AM | ✅ |
| 09:20 AM | ✅ (but marked **Late**, §6) |
| 05:30 PM | ✅ |
| 06:01 PM | ❌ after office hours |
| 07:00 PM | ❌ |
| 03:00 AM / 05:00 AM | ❌ before `check_in_open` |

Notes:
- The window is separate from **`late_grace`**. Crossing `late_grace` marks you
  *Late*; it does not stop check-in. Crossing the window *stops* check-in.
- `check_in_open` replaces the old "`office_start − early_grace`" opening, which was
  only 15 minutes early. `early_grace` now applies to **early-leave** detection only.

---

## 4. Check-out Rules

1. A check-out closes the current active session and computes its duration.
2. Checking out before `office_end − early_grace` records **early-leave** minutes.
3. Multiple sessions per day are summed; gaps between sessions count as break time.

---

## 5. Forgot-to-Check-Out — Auto-Detect (point 6)

A session left open (employee forgot to check out) is resolved automatically.

> **No scheduler is running in this deployment.** Auto-close must NOT depend on a
> background job. The **next-activity sweep is the primary (and required)
> mechanism** — it runs inside the normal request flow whenever the employee comes
> back to the system.

**Next-activity sweep (primary).** Whenever the employee returns — on **login** or
on the next **attendance action for today** (check-in / "set attendance today") —
the system detects any still-open session from a **previous day**, auto-closes it at
`office_end`, and **recalculates that day's summary**.

- Trigger points: employee **login** and **check-in** (both must run the sweep).
- Only **prior-day** open sessions are auto-closed; today's active session is left
  running.
- Check-out time is capped at `office_end`, so a forgotten session never inflates
  hours (and never counts time overnight).
- The affected day's summary is recalculated so its status/hours are correct
  (see §6) instead of staying frozen at the check-in snapshot.

**Scheduled sweep (optional, if a scheduler is ever enabled).** A background job may
also close stale sessions after each company's `auto_close_at`, recalculating the
summary the same way. It is a redundancy, not a dependency — the system is correct
without it.

### 5.1 Current vs. Target — **known bug**

Two gaps make forgotten check-outs corrupt attendance today:

1. **Summary not recalculated.** The next-activity sweep
   (`closeStaleActiveRecords()`, run during check-in) closes the session but **does
   not recalculate the summary** (❌). The day stays frozen at its check-in snapshot
   (`0 min / Absent`) even though a full day was worked.
2. **Only triggers on check-in, not login.** The sweep runs during check-in
   validation only. With no scheduler, an employee who forgot to check out and does
   not check in again (e.g. next day is off) never gets closed. The sweep must also
   run **on login**.

- **Impact (verified in DB):** 25 summaries currently show `Absent / 0 min` despite
  real worked time.
- **Target:**
  - Recalculate each affected day's summary inside the sweep.
  - Run the sweep on **login** as well as check-in.
  - One-time **backfill** to recompute the 25 corrupted rows.

---

## 6. Attendance Status (point 7)

Each day produces exactly one status on the summary. Status is resolved by
**precedence** — the first matching rule wins.

### 6.1 Precedence (target)

Resolve in this order; the **first** matching rule wins.

1. **Leave** — an approved leave covers the date → `Leave`.
   *(Leave blocks check-in, so a leave day has no work.)*
2. **Holiday** — the date is an active holiday →
   - no work recorded → `Holiday`
   - work recorded → `Holiday`; **all worked hours are overtime** (extra work, §6.3).
3. **Weekend / non-working day** →
   - no work recorded → `Weekend`
   - work recorded → `Weekend`; **all worked hours are overtime** (extra work, §6.3).

   *(On holidays/weekends arrival time is irrelevant — there is no schedule, so
   "late" never applies. That is why these rank above Late.)*

4. **Working day — no work** (no session at all for the day) → `Absent`.
   *(A no-show cannot be "late", so Absent is decided before Late.)*
5. **Working day — short day** — the day is finished (all sessions closed) and net
   worked hours `< work_hours` → `Half Day`. Management always sees a short day as
   Half Day, whether the employee was on time or late (lateness is still carried by
   the **late flag**, below).
6. **Working day — full day** — the day is finished and net worked hours
   `≥ work_hours`:
   - arrived on time → `Present`
   - first check-in after `office_start + late_grace` → `Late`
7. **Working day — in progress** — an **active (not yet closed)** session and no
   finished total yet → `Present` (in progress). If the arrival was late, the late
   flag is set; the final Present/Late/Half Day is decided at checkout/auto-close.

**Two independent dimensions.** *Duration* drives the status
(`Present` / `Half Day` / `Absent`); *arrival* drives lateness. They are combined as:

- A **late flag** (`is_late` + `late_minutes`) is set on **any** working-day session
  whose first check-in is after `office_start + late_grace` — regardless of the
  final status. So "Half Day + late" and "Present-but-late (= `Late`)" are both
  visible.
- The `Late` **status** is specifically a *full day that was started late*. It is a
  present-type status (counts as attended).

This way management sees **both** facts: whether it was a full/half day **and**
whether the person came in late. `Late` status = full day + late; `Half Day` +
late flag = short day + late.

**WFH (`work_from_home`)** is reserved for remote sessions (location ≠ office). It
is a present-type status like Late/Present. Out of scope for the current pass unless
you want remote days distinguished now — noting it so the enum's meaning is defined.

**At a glance:**

| Day type | Worked? | Arrival | Status | Counts as |
|---|---|---|---|---|
| Approved leave | — | — | `Leave` | leave |
| Holiday | no | — | `Holiday` | — |
| Holiday | yes | — | `Holiday` | overtime (extra work) |
| Weekend / off | no | — | `Weekend` | — |
| Weekend / off | yes | — | `Weekend` | overtime (extra work) |
| Working day | no | — | `Absent` | absent |
| Working day | yes (≥ full) | on time | `Present` | present |
| Working day | yes (≥ full) | after grace | `Late` | present |
| Working day | yes (< full) | on time | `Half Day` | present (half) |
| Working day | yes (< full) | after grace | `Half Day` **+ late flag** | present (half) |
| Working day | in progress | on time | `Present` (in progress) | present |
| Working day | in progress | after grace | `Present` (in progress) **+ late flag** | present |

*The **late flag** (`is_late` / `late_minutes`) is orthogonal to the status — it can
sit on `Present`, `Half Day`, or an in-progress day. The `Late` **status** is only
"full day started late".*

*"Counts as" drives the monthly stats: present/late/half feed attendance; holiday/
weekend work feeds the separate overtime figure (§6.3); leave and absent are their
own tallies.*

### 6.2 Current vs. Target

| Aspect | Current | Target |
|---|---|---|
| Present / Half Day by hours | ✅ | ✅ |
| `Late` meaning | ❌ "worked < ½ day" | Late = late **arrival** |
| Holiday / Weekend / Leave / WFH assigned to summary | ❌ never set | ✅ set by precedence |
| Absent while session still active (pre-checkout) | ⚠️ shows Absent | shows in-progress Present |
| Absent from stale-summary bug | ❌ | fixed (§5) |

### 6.3 Extra work (holiday / weekend overtime)

Work done on a holiday or an off day is **extra work** and must be visible to
management **separately** from regular attendance:

- The day keeps its `Holiday` / `Weekend` status (it is **not** counted as a normal
  "present" working day and does not affect the working-day count or attendance
  rate).
- **All** hours worked that day are recorded as **overtime** (not just the part
  beyond `work_hours`), because the whole day is outside the normal schedule.
- Management can see extra work as its own figure — an **overtime / extra-work**
  column and a total — so weekend/holiday effort is credited and reviewable without
  being mixed into regular Present days.
- The session is tagged as an overtime session (`is_overtime = true`) at check-in on
  any non-working day, which is what drives the separate reporting.

> This keeps two things distinct: **attendance** (did you show up on days you were
> supposed to) and **extra work** (effort put in on days you were not required to).

---

## 7. Fix Backlog (derived from this spec)

Ordered; each is a separate change so we can verify one at a time.

1. **Fix next-activity auto-close** — (a) recalculate the summary for each
   auto-closed day in `closeStaleActiveRecords()`; (b) run the sweep on **login** as
   well as check-in, since no scheduler runs. *(Stops new corruption.)*
2. **Backfill corrupted summaries** — recompute the 25 `Absent / 0 min` rows.
3. **Holiday-aware working day** — `isWorkingDay()` returns false on active
   holidays, so holiday work is tagged `is_overtime` (currently only weekends get
   this, because `isWorkingDay()` ignores holidays).
3b. **Surface extra work separately** — expose holiday/weekend overtime as its own
   figure/column for management, distinct from regular Present days (§6.3).
4. **Status precedence engine** — rewrite `determineStatus()`/`recalculate()` to
   assign Leave / Holiday / Weekend / Present / Half Day / Absent per §6, with late
   arrival as a separate flag.
5. **Late-grace default & cap** — set default to **30**, cap the max at **60**
   (reject 120), and correct the `120` value on existing companies (§1.2).
6. **Remove session & break limit enforcement** — drop `max_sessions`,
   `min_session_gap`, `max_breaks` checks from `CheckInRequest`, `ApiCheckInRequest`,
   and `BreakStartRequest` (columns dropped later). *(Deferred — doc first.)*
7. **Check-in window** — add `check_in_open` setting (default 06:00); allow check-in
   from `check_in_open` to `office_end`; block after `office_end` and before
   `check_in_open`; stop using `early_grace` for the check-in window (§3.1).

---

## Open Decisions Summary

| # | Decision | Resolution |
|---|---|---|
| D1 | `late_grace` value | ✅ Default **30**; company **30**; max capped at **60** (120 disallowed) |
| D2 | Check-in window | ✅ Open at `check_in_open` (default 06:00), close at `office_end`; blocked overnight |
| D3 | Holiday/weekend work status | Keep Holiday/Weekend label + record OT |
| D4 | "Late" status semantics | ✅ Late = **late arrival**, shown as its own status (present-type) |
| D5 | Worked < full day | ✅ Always `Half Day` (management-visible) |
| D6 | Session & break limits | ✅ Removed from rules (code cleanup deferred) |
| D7 | Late **and** short day | ✅ `Half Day` status + **late flag** (Half Day wins; lateness kept as flag) |
| D8 | WFH status | Reserved (remote sessions); out of scope this pass |
