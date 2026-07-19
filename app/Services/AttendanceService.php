<?php

namespace App\Services;

use App\Enums\AttendanceMessage;
use App\Enums\AttendanceStatus;
use App\Enums\BreakStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\SessionStatus;
use App\Models\AttendanceBreak;
use App\Models\AttendanceSession;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Traits\CompanySettings;
use Carbon\Carbon;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    use CompanySettings;

    public function __construct(
        protected readonly WorkScheduleService $scheduleService,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // Attendance Actions
    // ═══════════════════════════════════════════════════════════════

    public function checkIn(Employee $employee, string $ip, array $data = []): array
    {
        return $this->transact($employee, AttendanceMessage::CheckInFailed, function () use ($employee, $ip, $data) {
            if ($blocked = $this->checkInBlocker($employee)) {
                return $blocked;
            }

            $session = $this->openSession($employee, $ip, $data);
            $this->syncSummary($employee, $ip, data_get($data, 'location', 'office'));

            return $this->success(AttendanceMessage::CheckInSuccess->value, ['session' => $session]);
        });
    }

    public function checkOut(Employee $employee, string $ip, array $data = []): array
    {
        return $this->transact($employee, AttendanceMessage::CheckOutFailed, function () use ($employee, $ip, $data) {
            $session = $this->activeSession($employee);

            if (blank($session)) {
                return $this->error(AttendanceMessage::NoActiveSession->value);
            }

            $session->checkOut(
                $ip,
                data_get($data, 'location', 'office'),
                data_get($data, 'lat'),
                data_get($data, 'long'),
                data_get($data, 'note'),
            );

            $this->syncSummary($employee, $ip, data_get($data, 'location', 'office'));

            $session = $session->fresh();

            return $this->success(AttendanceMessage::CheckOutSuccess->value, [
                'session'  => $session,
                'duration' => ($session->duration_minutes ?? 0).' minutes',
            ]);
        });
    }

    public function startBreak(Employee $employee, string $ip, array $data = []): array
    {
        return $this->transact($employee, AttendanceMessage::BreakStartFailed, function () use ($employee, $ip, $data) {
            $session = $this->activeSession($employee);

            if (blank($session)) {
                return $this->error(AttendanceMessage::NoActiveSessionBreak->value);
            }

            if (filled($this->activeBreak($employee))) {
                return $this->error(AttendanceMessage::AlreadyOnBreak->value);
            }

            $break = AttendanceBreak::create([
                'employee_id'           => $employee->id,
                'attendance_session_id' => $session->id,
                'attendance_date'       => today(),
                'break_start'           => now(),
                'break_start_ip'        => $ip,
                'break_type'            => data_get($data, 'break_type', 'personal'),
                'reason'                => data_get($data, 'reason'),
                'status'                => BreakStatus::Active,
            ]);

            return $this->success(AttendanceMessage::BreakStartSuccess->value, ['break' => $break]);
        });
    }

    public function endBreak(Employee $employee, string $ip): array
    {
        return $this->transact($employee, AttendanceMessage::BreakEndFailed, function () use ($employee, $ip) {
            $break = $this->activeBreak($employee);

            if (blank($break)) {
                return $this->error(AttendanceMessage::NoActiveBreak->value);
            }

            $break->endBreak($ip);
            $this->syncSummary($employee, $ip);

            return $this->success(AttendanceMessage::BreakEndSuccess->value, [
                'break'    => $break->fresh(),
                'duration' => $break->duration_minutes.' minutes',
            ]);
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // Today
    // ═══════════════════════════════════════════════════════════════

    public function todayStatus(Employee $employee): array
    {
        $session = $this->activeSession($employee);
        $break   = $this->activeBreak($employee);

        return [
            'has_active_session' => filled($session),
            'active_session'     => $session,
            'has_active_break'   => filled($break),
            'active_break'       => $break,
            'today_sessions'     => $this->todaySessions($employee),
            'summary'            => $this->todaySummary($employee),
            'can_check_in'       => blank($session) && blank($break),
            'can_check_out'      => filled($session),
            'can_start_break'    => filled($session) && blank($break),
            'can_end_break'      => filled($break),
        ];
    }

    // Full today payload for the frontend (clock, timers, summary).
    public function todayData(Employee $employee): array
    {
        // No scheduler runs in this deployment, so this is the safety net for a
        // forgotten check-out: on every attendance open, close any stale prior-day
        // session and fix its summary.
        $this->closeStaleRecords($employee);

        $sessions = $this->todaySessions($employee);
        $active   = $sessions->firstWhere('status', SessionStatus::Active);
        $worked   = $this->workedSeconds($sessions);
        $running  = $active ? (int) abs($active->check_in_time->diffInSeconds(now())) : 0;

        return [
            'sessions'           => $this->formatSessions($sessions),
            'totalWorkedSeconds' => $worked,
            'currentSession'     => $this->formatActiveSession($active, $running),
            'currentBreak'       => $this->formatActiveBreak($this->activeBreak($employee)),
            'summary'            => $this->formatSummary($this->todaySummary($employee), $sessions, $worked + $running),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Auto Close
    // ═══════════════════════════════════════════════════════════════

    public function autoCloseSessions(): int
    {
        $count = 0;

        AttendanceSession::where('status', SessionStatus::Active)->get()->each(function ($session) use (&$count) {
            $company = $session->company;

            // Respect the per-company auto-close toggle
            if ($company && !$company->auto_close) {
                return;
            }

            // Only close once the company's auto-close time has passed for that day
            $closeAt = Carbon::parse($this->companySetting($company, 'auto_close_at'))
                ->setDate(
                    $session->attendance_date->year,
                    $session->attendance_date->month,
                    $session->attendance_date->day,
                );

            if (now()->lt($closeAt)) {
                return;
            }

            $session->autoClose();
            $count++;

            if ($employee = $session->employee) {
                $this->syncSummary($employee, $session->check_in_ip, $session->check_in_location);
            }
        });

        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // Reporting & Dashboard
    // ═══════════════════════════════════════════════════════════════

    public function monthlyReport(Employee $employee, int $year, int $month): array
    {
        $summaries = AttendanceSummary::where('employee_id', $employee->id)
            ->forMonth($year, $month)
            ->orderBy('attendance_date')
            ->get();

        return [
            'summaries' => $summaries,
            'stats'     => $this->statsFor($summaries),
        ];
    }

    public function officeHours(Employee $employee): array
    {
        $schedule = $this->scheduleService->getEmployeeSchedule($employee);

        return [
            'start'      => Carbon::parse($schedule['work_start_time'])->format('g:i A'),
            'end'        => Carbon::parse($schedule['work_end_time'])->format('g:i A'),
            'delay'      => $this->companySetting($employee->company, 'late_grace'),
            'office_ip'  => $schedule['office_ip'],
            'company'    => $employee->company?->name ?? 'N/A',
            'department' => $employee->department?->name ?? 'N/A',
        ];
    }

    public function monthlyStats(Employee $employee): array
    {
        return collect($this->monthlyPerformance($employee))
            ->only(['present', 'absent', 'late', 'rate'])
            ->all();
    }

    // Monthly performance metrics for the attendance page's Performance card.
    public function monthlyPerformance(Employee $employee): array
    {
        $now    = now();
        $report = $this->monthlyReport($employee, $now->year, $now->month);
        $stats  = $report['stats'];

        $workingDays = $this->scheduleService->getMonthlyWorkingDays($employee, $now->year, $now->month);

        // Attendance rate counts every day the employee showed up: present + late + WFH.
        $attended = $report['summaries']->whereIn('status', [
            AttendanceStatus::Present,
            AttendanceStatus::Late,
            AttendanceStatus::WorkFromHome,
        ])->count();

        return [
            'month_label'  => $now->format('F Y'),
            'rate'         => $workingDays > 0 ? (int) round(($attended / $workingDays) * 100) : 0,
            'working_days' => $workingDays,
            'present'      => $stats['present_days'],
            'late'         => $stats['late_days'],
            'absent'       => $stats['absent_days'],
        ];
    }

    // Today's status of the employee's direct reports (managers/leads only).
    // Returns null when the employee has no active subordinates.
    public function teamToday(Employee $employee): ?array
    {
        $reports = $employee->subordinates()
            ->where('status', true)
            ->with('designation:id,title')
            ->orderBy('first_name')
            ->get();

        if ($reports->isEmpty()) {
            return null;
        }

        // Single grouped lookup over all report ids (avoid N+1)
        $summaries = AttendanceSummary::whereIn('employee_id', $reports->pluck('id'))
            ->whereDate('attendance_date', today())
            ->get()
            ->keyBy('employee_id');

        $members = $reports->map(function ($report) use ($summaries) {
            [$status, $checkIn] = $this->teamMemberStatus($summaries->get($report->id));

            return [
                'id'           => $report->id,
                'name'         => $report->full_name,
                'role'         => $report->designation?->title ?? 'Employee',
                'check_in'     => $checkIn,
                'status'       => $status,
                'status_label' => $status === 'working' ? 'Working' : AttendanceStatus::labelFor($status),
            ];
        });

        return [
            // present bucket = present + wfh + working; late = late; absent = absent + no-record
            'present' => $members->whereIn('status', ['present', 'work_from_home', 'working'])->count(),
            'late'    => $members->where('status', 'late')->count(),
            'absent'  => $members->where('status', 'absent')->count(),
            'members' => $members->values()->all(),
        ];
    }

    // Holidays overlapping the current month for the Performance card.
    public function monthlyHolidays(Employee $employee): array
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        return Holiday::where('company_id', $employee->company_id)
            ->where('status', true)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->orderBy('start_date')
            ->get()
            ->map(fn($holiday) => [
                'name' => $holiday->name,
                'day'  => $holiday->start_date->format('D'),
                'date' => $holiday->start_date->isSameDay($holiday->end_date)
                    ? $holiday->start_date->format('d M')
                    : $holiday->start_date->format('d M').' – '.$holiday->end_date->format('d M'),
            ])
            ->all();
    }

    public function records(Employee $employee, int $months = 3): array
    {
        return collect(range(0, $months - 1))
            ->mapWithKeys(function ($ago) use ($employee) {
                $date = now()->subMonths($ago);

                $rows = AttendanceSummary::where('employee_id', $employee->id)
                    ->forMonth($date->year, $date->month)
                    ->orderBy('attendance_date')
                    ->get()
                    ->map(fn($summary) => $this->summaryRow($summary));

                return $rows->isEmpty() ? [] : [$date->format('Y-m') => $rows->all()];
            })
            ->all();
    }

    public function monthlyData(Employee $employee, int $year, int $month): array
    {
        return AttendanceSummary::where('employee_id', $employee->id)
            ->forMonth($year, $month)
            ->orderBy('attendance_date')
            ->get()
            ->map(fn($summary) => $this->summaryRow($summary))
            ->all();
    }

    // ═══════════════════════════════════════════════════════════════
    // Check-in Guards
    // ═══════════════════════════════════════════════════════════════

    // Returns an error payload if the employee cannot check in, otherwise null.
    private function checkInBlocker(Employee $employee): ?array
    {
        // Auto-close any prior-day Active rows so a forgotten check-out doesn't block today.
        $this->closeStaleRecords($employee);

        // Holidays no longer block check-in — employees may work on holidays and the
        // attendance is recorded normally so management can see who worked.

        if ($leave = $this->approvedLeave($employee)) {
            return $this->error(AttendanceMessage::LeaveRestricted->with($leave->leaveType?->name ?? 'Leave'));
        }

        if (filled($this->activeSession($employee))) {
            return $this->error(AttendanceMessage::ActiveSessionExists->value);
        }

        if (filled($this->activeBreak($employee))) {
            return $this->error(AttendanceMessage::ActiveBreakExists->value);
        }

        return null;
    }

    private function approvedLeave(Employee $employee): ?LeaveRequest
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->where('status', LeaveRequestStatus::Approved)
            ->where('started_at', '<=', today())
            ->where('ended_at', '>=', today())
            ->first();
    }

    // ═══════════════════════════════════════════════════════════════
    // Sessions & Breaks
    // ═══════════════════════════════════════════════════════════════

    // Create a new attendance session (owns its own schedule/late/overtime derivation).
    private function openSession(Employee $employee, string $ip, array $data): AttendanceSession
    {
        $company     = $employee->company;
        $workingDay  = $this->scheduleService->isWorkingDay($employee, today());
        $lateMinutes = $company ? $this->scheduleService->calculateLateMinutes($employee, now()) : 0;

        return AttendanceSession::create([
            'employee_id'          => $employee->id,
            'company_id'           => $employee->company_id,
            'department_id'        => $employee->department_id,
            'attendance_date'      => today(),
            'session_number'       => $this->nextSessionNumber($employee),
            'check_in_time'        => now(),
            'scheduled_start_time' => $this->companySetting($company, 'office_start'),
            'scheduled_end_time'   => $this->companySetting($company, 'office_end'),
            'check_in_ip'          => $ip,
            'check_in_location'    => data_get($data, 'location', 'office'),
            'check_in_lat'         => data_get($data, 'lat'),
            'check_in_long'        => data_get($data, 'long'),
            'check_in_note'        => data_get($data, 'note'),
            'session_type'         => $workingDay ? 'regular' : 'overtime',
            'status'               => SessionStatus::Active,
            'is_late'              => $lateMinutes > 0,
            'is_overtime'          => !$workingDay,
        ]);
    }

    // Latest open session (unscoped by date so cross-midnight check-outs still work).
    private function activeSession(Employee $employee): ?AttendanceSession
    {
        return AttendanceSession::forEmployee($employee->id)
            ->active()
            ->latest('check_in_time')
            ->first();
    }

    // Latest open break (unscoped by date; auto-close handles stale prior-day rows).
    private function activeBreak(Employee $employee): ?AttendanceBreak
    {
        return AttendanceBreak::where('employee_id', $employee->id)
            ->where('status', BreakStatus::Active)
            ->latest('break_start')
            ->first();
    }

    private function todaySessions(Employee $employee)
    {
        return AttendanceSession::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->orderBy('session_number')
            ->get();
    }

    private function todaySummary(Employee $employee): ?AttendanceSummary
    {
        return AttendanceSummary::where('employee_id', $employee->id)
            ->where('attendance_date', today())
            ->first();
    }

    private function nextSessionNumber(Employee $employee): int
    {
        return (AttendanceSession::forEmployee($employee->id)->today()->max('session_number') ?? 0) + 1;
    }

    // Close any active session/break from a previous day and recalculate those days' summaries.
    private function closeStaleRecords(Employee $employee): void
    {
        $today         = today();
        $affectedDates = collect();

        AttendanceSession::forEmployee($employee->id)
            ->active()
            ->whereDate('attendance_date', '<', $today)
            ->get()
            ->each(function ($session) use ($affectedDates) {
                $session->autoClose();
                $affectedDates->push($session->attendance_date->toDateString());
            });

        AttendanceBreak::where('employee_id', $employee->id)
            ->where('status', BreakStatus::Active)
            ->whereDate('attendance_date', '<', $today)
            ->get()
            ->each(function ($break) use ($affectedDates) {
                $break->update([
                    'break_end'    => $break->break_start,
                    'status'       => BreakStatus::Completed,
                    'break_end_ip' => null,
                ]);
                $affectedDates->push($break->attendance_date->toDateString());
            });

        // autoClose() updates the session but not the daily summary, which would
        // otherwise stay frozen at its check-in snapshot for the forgotten-checkout day.
        $affectedDates->unique()->each(fn($date) => AttendanceSummary::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first()
            ?->recalculate());
    }

    // ═══════════════════════════════════════════════════════════════
    // Summary
    // ═══════════════════════════════════════════════════════════════

    // Create/refresh today's summary from the given check-in IP and location.
    private function syncSummary(Employee $employee, string $ip, string $location = 'office'): void
    {
        $company = $employee->company;

        $summary = AttendanceSummary::firstOrCreate(
            [
                'employee_id'     => $employee->id,
                'attendance_date' => today(),
            ],
            [
                'company_id'           => $employee->company_id,
                'department_id'        => $employee->department_id,
                'scheduled_start_time' => $this->companySetting($company, 'office_start'),
                'scheduled_end_time'   => $this->companySetting($company, 'office_end'),
                'grace_minutes'        => $this->companySetting($company, 'late_grace'),
                'is_working_day'       => $this->scheduleService->isWorkingDay($employee, today()),
                'shift_name'           => $company ? 'Regular' : 'Default',
            ]
        );

        $summary->addIpAddress($ip);
        $summary->addLocation($location);
        $summary->recalculate();
    }

    private function statsFor($summaries): array
    {
        return [
            'total_days'            => $summaries->count(),
            'present_days'          => $summaries->where('status', AttendanceStatus::Present)->count(),
            'absent_days'           => $summaries->where('status', AttendanceStatus::Absent)->count(),
            'half_days'             => $summaries->where('status', AttendanceStatus::HalfDay)->count(),
            'late_days'             => $summaries->where('status', AttendanceStatus::Late)->count(),
            'holidays'              => $summaries->where('status', AttendanceStatus::Holiday)->count(),
            'leaves'                => $summaries->where('status', AttendanceStatus::Leave)->count(),
            'total_working_hours'   => round($summaries->sum('total_working_minutes') / 60, 2),
            'total_overtime_hours'  => round($summaries->sum('overtime_minutes') / 60, 2),
            'average_working_hours' => $summaries->isNotEmpty() ? round($summaries->avg('total_working_minutes') / 60, 2) : 0,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Frontend Formatting
    // ═══════════════════════════════════════════════════════════════

    private function workedSeconds($sessions): int
    {
        return $sessions
            ->whereIn('status', [SessionStatus::Completed, SessionStatus::AutoClosed])
            ->sum(fn($session) => ($session->duration_minutes ?? 0) * 60);
    }

    private function formatSessions($sessions): array
    {
        return $sessions->map(fn($session) => [
            'id'            => $session->id,
            'startTime'     => $session->check_in_time->toIso8601String(),
            'endTime'       => $session->check_out_time?->toIso8601String(),
            'duration'      => ($session->duration_minutes ?? 0) * 60,
            'status'        => $session->status,
            'sessionNumber' => $session->session_number,
        ])->all();
    }

    private function formatActiveSession(?AttendanceSession $session, int $running): ?array
    {
        return $session ? [
            'startTime'       => $session->check_in_time->toIso8601String(),
            'isWorking'       => true,
            'sessionId'       => $session->id,
            'currentDuration' => $running,
        ] : null;
    }

    private function formatActiveBreak(?AttendanceBreak $break): ?array
    {
        return $break ? [
            'startTime' => $break->break_start->toIso8601String(),
            'breakType' => $break->break_type,
            'breakId'   => $break->id,
        ] : null;
    }

    private function formatSummary(?AttendanceSummary $summary, $sessions, int $totalSeconds): array
    {
        $breakMinutes = $summary?->total_break_minutes ?? 0;

        return [
            'firstCheckIn'   => $sessions->isNotEmpty() ? $sessions->first()->check_in_time->format('h:i A') : '--:--',
            'lastCheckOut'   => $sessions->whereNotNull('check_out_time')->last()?->check_out_time?->format('h:i A') ?? '--:--',
            'totalHours'     => $this->formatDuration($totalSeconds),
            'totalBreakTime' => $breakMinutes > 0 ? $this->formatDuration($breakMinutes * 60) : null,
            'status'         => $summary?->status ?? 'absent',
        ];
    }

    // One attendance-summary row for the records/monthly listings.
    private function summaryRow(AttendanceSummary $summary): array
    {
        return [
            'date'     => $summary->attendance_date->day,
            'checkIn'  => filled($summary->first_check_in) ? Carbon::parse($summary->first_check_in)->format('H:i') : null,
            'checkOut' => filled($summary->last_check_out) ? Carbon::parse($summary->last_check_out)->format('H:i') : null,
            'hours'    => $summary->total_working_hours,
            'status'   => $this->mapStatus($summary->status),
            'late'     => $summary->late_minutes > 0,
        ];
    }

    // Resolve a report's today status + formatted check-in from their summary.
    // "working" = checked in today and not yet checked out (and not marked absent).
    private function teamMemberStatus(?AttendanceSummary $summary): array
    {
        if (blank($summary)) {
            return ['absent', null];
        }

        $status  = $summary->status instanceof \BackedEnum ? $summary->status->value : $summary->status;
        $checkIn = filled($summary->first_check_in) ? Carbon::parse($summary->first_check_in)->format('g:i A') : null;
        $working = filled($summary->first_check_in) && blank($summary->last_check_out) && $status !== 'absent';

        return [$working ? 'working' : $status, $checkIn];
    }

    // ═══════════════════════════════════════════════════════════════
    // Utilities
    // ═══════════════════════════════════════════════════════════════

    public function formatDuration(int $seconds): string
    {
        return floor($seconds / 3600).'h '.floor(($seconds % 3600) / 60).'m';
    }

    private function mapStatus($status): string
    {
        $key = $status instanceof \BackedEnum ? $status->value : $status;

        return match ($key) {
            'present', 'late' => 'Present',
            'absent'          => 'Absent',
            'half_day'        => 'Half Day',
            'holiday'         => 'Holiday',
            'weekend'         => 'Weekend',
            'leave'           => 'Leave',
            'work_from_home'  => 'WFH',
            default           => 'Unknown',
        };
    }

    // Run an attendance mutation in a transaction; log and return an error payload on failure.
    private function transact(Employee $employee, AttendanceMessage $failure, callable $work): array
    {
        try {
            return DB::transaction($work);
        } catch (Throwable $e) {
            Log::error('Attendance action failed', [
                'employee_id' => $employee->id,
                'failure'     => $failure->name,
                'error'       => $e->getMessage(),
            ]);

            return $this->error($failure->value);
        }
    }

    private function success(string $message, array $extra = []): array
    {
        return ['success' => true, 'message' => $message, ...$extra];
    }

    private function error(string $message, array $extra = []): array
    {
        return ['success' => false, 'message' => $message, ...$extra];
    }
}
