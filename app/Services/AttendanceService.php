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
use Exception;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    use CompanySettings;

    public function __construct(
        protected ?WorkScheduleService $scheduleService = null
    ) {
        $this->scheduleService = $scheduleService ?: new WorkScheduleService();
    }

    // ─── Check In ────────────────────────────────────────────────

    public function checkIn(Employee $employee, string $ip, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $today = today();

            // Step 1: Validate employee can check in
            if ($error = $this->validateCheckIn($employee, $today)) {
                return $error;
            }

            // Step 2: Determine session type (regular or overtime)
            $isWorkingDay = $this->scheduleService->isWorkingDay($employee, $today);
            $sessionType  = $isWorkingDay ? 'regular' : 'overtime';

            // Step 3: Get schedule info and late minutes
            $schedule    = $this->getScheduleInfo($employee);
            $lateMinutes = data_get($schedule, 'late_minutes', 0);

            // Step 4: Create attendance session
            $session = $this->createSession($employee, $ip, $data, $sessionType, $schedule, $isWorkingDay, $lateMinutes);

            // Step 5: Update daily summary
            $this->updateDailySummary($employee, $ip, data_get($data, 'location', 'office'));

            DB::commit();

            return $this->success(AttendanceMessage::CheckInSuccess->value, ['session' => $session]);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error(AttendanceMessage::CheckInFailed->value);
        }
    }

    // ─── Check Out ───────────────────────────────────────────────

    public function checkOut(Employee $employee, string $ip, array $data = []): array
    {
        try {
            DB::beginTransaction();

            // Step 1: Find active session
            $activeSession = $this->findActiveSession($employee);

            if (!$activeSession) {
                return $this->error(AttendanceMessage::NoActiveSession->value);
            }

            // Step 2: Process check-out
            $activeSession->checkOut(
                $ip,
                data_get($data, 'location', 'office'),
                data_get($data, 'lat'),
                data_get($data, 'long'),
                data_get($data, 'note'),
            );

            // Step 3: Update daily summary
            $this->updateDailySummary($employee, $ip, data_get($data, 'location', 'office'));

            DB::commit();

            $freshSession = $activeSession->fresh();

            return $this->success(AttendanceMessage::CheckOutSuccess->value, [
                'session'  => $freshSession,
                'duration' => ($freshSession->duration_minutes ?? 0) . ' minutes',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error(AttendanceMessage::CheckOutFailed->value);
        }
    }

    // ─── Start Break ─────────────────────────────────────────────

    public function startBreak(Employee $employee, string $ip, string $breakType = 'personal', ?string $reason = null): array
    {
        try {
            DB::beginTransaction();

            // Step 1: Ensure active session exists
            $activeSession = $this->findActiveSession($employee);

            if (!$activeSession) {
                return $this->error(AttendanceMessage::NoActiveSessionBreak->value);
            }

            // Step 2: Ensure no active break
            if ($this->findActiveBreak($employee)) {
                return $this->error(AttendanceMessage::AlreadyOnBreak->value);
            }

            // Step 3: Create break record
            $break = AttendanceBreak::create([
                'employee_id'           => $employee->id,
                'attendance_session_id' => $activeSession->id,
                'attendance_date'       => today(),
                'break_start'           => now(),
                'break_start_ip'        => $ip,
                'break_type'            => $breakType,
                'reason'                => $reason,
                'status'                => BreakStatus::Active,
            ]);

            DB::commit();

            return $this->success(AttendanceMessage::BreakStartSuccess->value, ['break' => $break]);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error(AttendanceMessage::BreakStartFailed->value);
        }
    }

    // ─── End Break ───────────────────────────────────────────────

    public function endBreak(Employee $employee, string $ip): array
    {
        try {
            DB::beginTransaction();

            // Step 1: Find active break
            $activeBreak = $this->findActiveBreak($employee);

            if (!$activeBreak) {
                return $this->error(AttendanceMessage::NoActiveBreak->value);
            }

            // Step 2: End the break
            $activeBreak->endBreak($ip);

            // Step 3: Update daily summary
            $this->updateDailySummary($employee, $ip);

            DB::commit();

            return $this->success(AttendanceMessage::BreakEndSuccess->value, [
                'break'    => $activeBreak->fresh(),
                'duration' => $activeBreak->duration_minutes . ' minutes',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error(AttendanceMessage::BreakEndFailed->value);
        }
    }

    // ─── Today Status ────────────────────────────────────────────

    public function getTodayStatus(Employee $employee): array
    {
        $activeSession = $this->findActiveSession($employee);
        $activeBreak   = $this->findActiveBreak($employee);
        $todaySessions = $this->getTodaySessions($employee);
        $summary       = $this->getTodaySummary($employee);

        return [
            'has_active_session' => !is_null($activeSession),
            'active_session'     => $activeSession,
            'has_active_break'   => !is_null($activeBreak),
            'active_break'       => $activeBreak,
            'today_sessions'     => $todaySessions,
            'summary'            => $summary,
            'can_check_in'       => is_null($activeSession) && is_null($activeBreak),
            'can_check_out'      => !is_null($activeSession),
            'can_start_break'    => !is_null($activeSession) && is_null($activeBreak),
            'can_end_break'      => !is_null($activeBreak),
        ];
    }

    // ─── Today Complete Data (for frontend) ──────────────────────

    public function getTodayCompleteData(Employee $employee): array
    {
        $sessions      = $this->getTodaySessions($employee);
        $activeSession = $sessions->where('status', SessionStatus::Active)->first();
        $activeBreak   = $this->findActiveBreak($employee);
        $summary       = $this->getTodaySummary($employee);

        // Calculate total worked seconds from completed sessions
        $totalWorkedSeconds = $this->calculateTotalWorkedSeconds($sessions);

        // Calculate current active session duration
        $currentSessionSeconds = $activeSession
            ? (int) abs($activeSession->check_in_time->diffInSeconds(now()))
            : 0;

        return [
            'sessions'           => $this->formatSessionsForFrontend($sessions),
            'totalWorkedSeconds' => $totalWorkedSeconds,
            'currentSession'     => $this->formatActiveSession($activeSession, $currentSessionSeconds),
            'currentBreak'       => $this->formatActiveBreak($activeBreak),
            'summary'            => $this->formatSummary($summary, $sessions, $totalWorkedSeconds + $currentSessionSeconds),
        ];
    }

    // ─── Auto Close Stale Sessions ──────────────────────────────

    public function autoCloseActiveSessions(): int
    {
        $activeSessions = AttendanceSession::where('status', SessionStatus::Active)
            ->whereDate('attendance_date', '<', today())
            ->get();

        $count = 0;

        foreach ($activeSessions as $session) {
            $session->autoClose();
            $count++;

            if ($employee = $session->employee) {
                $this->updateDailySummary($employee, $session->check_in_ip, $session->check_in_location);
            }
        }

        return $count;
    }

    // ─── Monthly Report ─────────────────────────────────────────

    public function getMonthlyReport(Employee $employee, int $year, int $month): array
    {
        $summaries = AttendanceSummary::where('employee_id', $employee->id)
            ->forMonth($year, $month)
            ->orderBy('attendance_date')
            ->get();

        return [
            'summaries' => $summaries,
            'stats'     => $this->calculateMonthlyStats($summaries),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Validation Methods
    // ═══════════════════════════════════════════════════════════════

    // Validate all check-in preconditions
    private function validateCheckIn(Employee $employee, Carbon $today): ?array
    {
        // Check if today is a holiday
        if ($holiday = $this->findHolidayForDate($employee, $today)) {
            return $this->error(AttendanceMessage::HolidayRestricted->with($holiday->name));
        }

        // Check if employee is on approved leave
        if ($leave = $this->findApprovedLeaveForDate($employee, $today)) {
            return $this->error(AttendanceMessage::LeaveRestricted->with($leave->leaveType?->name ?? 'Leave'));
        }

        // Check for active session (already checked in)
        if ($this->findActiveSession($employee)) {
            return $this->error(AttendanceMessage::ActiveSessionExists->value);
        }

        // Check for active break
        if ($this->findActiveBreak($employee)) {
            return $this->error(AttendanceMessage::ActiveBreakExists->value);
        }

        return null;
    }

    // Check if given date falls on a holiday
    private function findHolidayForDate(Employee $employee, Carbon $date): ?Holiday
    {
        return Holiday::where('company_id', $employee->company_id)
            ->where('status', true)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    // Check if employee has approved leave for given date
    private function findApprovedLeaveForDate(Employee $employee, Carbon $date): ?LeaveRequest
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->where('status', LeaveRequestStatus::Approved)
            ->where('started_at', '<=', $date)
            ->where('ended_at', '>=', $date)
            ->first();
    }

    // ═══════════════════════════════════════════════════════════════
    // Query Helper Methods
    // ═══════════════════════════════════════════════════════════════

    // Find active session for employee today
    private function findActiveSession(Employee $employee): ?AttendanceSession
    {
        return AttendanceSession::forEmployee($employee->id)
            ->today()
            ->active()
            ->first();
    }

    // Find active break for employee today
    private function findActiveBreak(Employee $employee): ?AttendanceBreak
    {
        return AttendanceBreak::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->where('status', BreakStatus::Active)
            ->first();
    }

    // Get all sessions for employee today
    private function getTodaySessions(Employee $employee)
    {
        return AttendanceSession::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->orderBy('session_number')
            ->get();
    }

    // Get today's attendance summary
    private function getTodaySummary(Employee $employee): ?AttendanceSummary
    {
        return AttendanceSummary::where('employee_id', $employee->id)
            ->where('attendance_date', today())
            ->first();
    }

    // Get next session number for today
    private function getNextSessionNumber(Employee $employee): int
    {
        return (AttendanceSession::forEmployee($employee->id)
                ->today()
                ->max('session_number') ?? 0) + 1;
    }

    // ═══════════════════════════════════════════════════════════════
    // Schedule & Session Methods
    // ═══════════════════════════════════════════════════════════════

    // Get schedule info from employee's company
    private function getScheduleInfo(Employee $employee): array
    {
        $company = $employee->company;

        return [
            'start_time'   => $this->companySetting($company, 'office_start'),
            'end_time'     => $this->companySetting($company, 'office_end'),
            'late_minutes' => $company
                ? $this->scheduleService->calculateLateMinutes($employee, now())
                : 0,
        ];
    }

    // Create a new attendance session
    private function createSession(Employee $employee, string $ip, array $data, string $sessionType, array $schedule, bool $isWorkingDay, int $lateMinutes): AttendanceSession
    {
        return AttendanceSession::create([
            'employee_id'          => $employee->id,
            'company_id'           => $employee->company_id,
            'department_id'        => $employee->department_id,
            'attendance_date'      => today(),
            'session_number'       => $this->getNextSessionNumber($employee),
            'check_in_time'        => now(),
            'scheduled_start_time' => data_get($schedule, 'start_time'),
            'scheduled_end_time'   => data_get($schedule, 'end_time'),
            'check_in_ip'          => $ip,
            'check_in_location'    => data_get($data, 'location', 'office'),
            'check_in_lat'         => data_get($data, 'lat'),
            'check_in_long'        => data_get($data, 'long'),
            'check_in_note'        => data_get($data, 'note'),
            'session_type'         => $sessionType,
            'status'               => SessionStatus::Active,
            'is_late'              => $lateMinutes > 0,
            'is_overtime'          => !$isWorkingDay,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Summary Methods
    // ═══════════════════════════════════════════════════════════════

    // Update or create daily attendance summary
    private function updateDailySummary(Employee $employee, string $ip, string $location = 'office'): void
    {
        $company      = $employee->company;
        $isWorkingDay = $this->scheduleService->isWorkingDay($employee, today());

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
                'is_working_day'       => $isWorkingDay,
                'shift_name'           => $company ? 'Regular' : 'Default',
            ]
        );

        $summary->addIpAddress($ip);
        $summary->addLocation($location);
        $summary->recalculate();
    }

    // Calculate monthly attendance stats
    private function calculateMonthlyStats($summaries): array
    {
        $count = $summaries->count();

        return [
            'total_days'            => $count,
            'present_days'          => $summaries->where('status', AttendanceStatus::Present)->count(),
            'absent_days'           => $summaries->where('status', AttendanceStatus::Absent)->count(),
            'half_days'             => $summaries->where('status', AttendanceStatus::HalfDay)->count(),
            'late_days'             => $summaries->where('status', AttendanceStatus::Late)->count(),
            'holidays'              => $summaries->where('status', AttendanceStatus::Holiday)->count(),
            'leaves'                => $summaries->where('status', AttendanceStatus::Leave)->count(),
            'total_working_hours'   => round($summaries->sum('total_working_minutes') / 60, 2),
            'total_overtime_hours'  => round($summaries->sum('overtime_minutes') / 60, 2),
            'average_working_hours' => $count > 0
                ? round($summaries->avg('total_working_minutes') / 60, 2)
                : 0,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Formatting Methods (for frontend)
    // ═══════════════════════════════════════════════════════════════

    // Calculate total worked seconds from completed sessions
    private function calculateTotalWorkedSeconds($sessions): int
    {
        return $sessions
            ->whereIn('status', [SessionStatus::Completed, SessionStatus::AutoClosed])
            ->sum(fn($session) => ($session->duration_minutes ?? 0) * 60);
    }

    // Format sessions collection for frontend response
    private function formatSessionsForFrontend($sessions): array
    {
        return $sessions->map(fn($session) => [
            'id'            => $session->id,
            'startTime'     => $session->check_in_time->toIso8601String(),
            'endTime'       => $session->check_out_time?->toIso8601String(),
            'duration'      => ($session->duration_minutes ?? 0) * 60,
            'status'        => $session->status,
            'sessionNumber' => $session->session_number,
        ])->toArray();
    }

    // Format active session for frontend response
    private function formatActiveSession(?AttendanceSession $session, int $currentSeconds): ?array
    {
        return $session ? [
            'startTime'       => $session->check_in_time->toIso8601String(),
            'isWorking'       => true,
            'sessionId'       => $session->id,
            'currentDuration' => $currentSeconds,
        ] : null;
    }

    // Format active break for frontend response
    private function formatActiveBreak(?AttendanceBreak $break): ?array
    {
        return $break ? [
            'startTime' => $break->break_start->toIso8601String(),
            'breakType' => $break->break_type,
            'breakId'   => $break->id,
        ] : null;
    }

    // Format summary for frontend response
    private function formatSummary(?AttendanceSummary $summary, $sessions, int $totalSeconds): array
    {
        $breakMinutes = $summary?->total_break_minutes ?? 0;

        return [
            'firstCheckIn'    => $sessions->isNotEmpty()
                ? $sessions->first()->check_in_time->format('h:i A')
                : '--:--',
            'lastCheckOut'    => $sessions->whereNotNull('check_out_time')->last()?->check_out_time?->format('h:i A') ?? '--:--',
            'totalHours'      => $this->formatDuration($totalSeconds),
            'totalBreakTime'  => $breakMinutes > 0 ? $this->formatDuration($breakMinutes * 60) : null,
            'status'          => $summary?->status ?? 'absent',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Employee Dashboard Methods
    // ═══════════════════════════════════════════════════════════════

    public function getOfficeHours(Employee $employee): array
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

    public function getMonthlyStats(Employee $employee): array
    {
        $now    = now();
        $report = $this->getMonthlyReport($employee, $now->year, $now->month);

        $workingDays    = $this->scheduleService->getMonthlyWorkingDays($employee, $now->year, $now->month);
        $attendanceRate = $workingDays > 0
            ? round(($report['stats']['present_days'] / $workingDays) * 100)
            : 0;

        return [
            'present' => $report['stats']['present_days'],
            'absent'  => $report['stats']['absent_days'],
            'late'    => $report['stats']['late_days'],
            'rate'    => $attendanceRate,
        ];
    }

    public function getAttendanceRecords(Employee $employee, int $months = 3): array
    {
        $records = [];

        for ($i = 0; $i < $months; $i++) {
            $date     = now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            $summaries = AttendanceSummary::where('employee_id', $employee->id)
                ->forMonth($date->year, $date->month)
                ->orderBy('attendance_date')
                ->get();

            $monthData = $summaries->map(fn($s) => [
                'date'     => $s->attendance_date->day,
                'checkIn'  => $s->first_check_in ? Carbon::parse($s->first_check_in)->format('H:i') : null,
                'checkOut' => $s->last_check_out ? Carbon::parse($s->last_check_out)->format('H:i') : null,
                'hours'    => $s->total_working_hours,
                'status'   => $this->mapStatus($s->status),
                'late'     => $s->late_minutes > 0,
            ])->toArray();

            if (!empty($monthData)) {
                $records[$monthKey] = $monthData;
            }
        }

        return $records;
    }

    public function getMonthlyData(Employee $employee, int $year, int $month): array
    {
        $summaries = AttendanceSummary::where('employee_id', $employee->id)
            ->forMonth($year, $month)
            ->orderBy('attendance_date')
            ->get();

        return $summaries->map(fn($s) => [
            'date'     => $s->attendance_date->day,
            'checkIn'  => $s->first_check_in ? Carbon::parse($s->first_check_in)->format('H:i') : null,
            'checkOut' => $s->last_check_out ? Carbon::parse($s->last_check_out)->format('H:i') : null,
            'hours'    => $s->total_working_hours,
            'status'   => $this->mapStatus($s->status),
            'late'     => $s->late_minutes > 0,
        ])->toArray();
    }

    // ═══════════════════════════════════════════════════════════════
    // Response & Utility Methods
    // ═══════════════════════════════════════════════════════════════

    public function formatDuration(int $seconds): string
    {
        return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
    }

    private function mapStatus($status): string
    {
        $key = $status instanceof \BackedEnum ? $status->value : $status;

        return match ($key) {
            'present'        => 'Present',
            'absent'         => 'Absent',
            'half_day'       => 'Half Day',
            'late'           => 'Present',
            'holiday'        => 'Holiday',
            'weekend'        => 'Weekend',
            'leave'          => 'Leave',
            'work_from_home' => 'WFH',
            default          => 'Unknown',
        };
    }

    private function success(string $message, array $extra = []): array
    {
        return collect(['success' => true, 'message' => $message])->merge($extra)->toArray();
    }

    private function error(string $message, array $extra = []): array
    {
        return collect(['success' => false, 'message' => $message])->merge($extra)->toArray();
    }
}
