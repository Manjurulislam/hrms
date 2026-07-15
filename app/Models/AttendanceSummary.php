<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\SessionStatus;
use App\Services\WorkScheduleService;
use App\Traits\CompanySettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSummary extends Model
{
    use CompanySettings;

    protected $fillable = [
        'employee_id',
        'company_id',
        'department_id',
        'attendance_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'grace_minutes',
        'first_check_in',
        'last_check_out',
        'total_working_minutes',
        'total_break_minutes',
        'overtime_minutes',
        'late_minutes',
        'early_leave_minutes',
        'total_sessions',
        'status',
        'is_working_day',
        'shift_name',
        'ip_addresses',
        'locations',
    ];


    protected $casts = [
        'attendance_date' => 'date',
        'ip_addresses'    => 'array',
        'locations'       => 'array',
        'is_working_day'  => 'boolean',
        'status'          => AttendanceStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class, 'employee_id', 'employee_id')
            ->where('attendance_breaks.attendance_date', $this->attendance_date);
    }

    public function getTotalWorkingHoursAttribute(): float
    {
        return round($this->total_working_minutes / 60, 2);
    }

    // Accessors

    public function getTotalBreakHoursAttribute(): float
    {
        return round($this->total_break_minutes / 60, 2);
    }

    public function getOvertimeHoursAttribute(): float
    {
        return round($this->overtime_minutes / 60, 2);
    }

    public function getNetWorkingHoursAttribute(): float
    {
        return round(($this->total_working_minutes - $this->total_break_minutes) / 60, 2);
    }

    public function getLateHoursAttribute(): float
    {
        return round($this->late_minutes / 60, 2);
    }

    public function getEarlyLeaveHoursAttribute(): float
    {
        return round($this->early_leave_minutes / 60, 2);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome]);
    }

    // Scopes

    public function scopeAbsent($query)
    {
        return $query->where('status', AttendanceStatus::Absent);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeSince($query, $date)
    {
        return $query->where('attendance_date', '>=', $date);
    }

    public function recalculate(): void
    {
        // Get ALL sessions for the day (including active ones for first_check_in)
        $allSessions = $this->sessions()->get();

        // Get finished sessions (completed + auto-closed) for duration calculations
        $completedSessions = $allSessions->whereIn('status', [SessionStatus::Completed, SessionStatus::AutoClosed]);

        if ($allSessions->isEmpty()) {
            $this->update([
                'status'                => $this->determineStatus(0, false, false, false),
                'total_working_minutes' => 0,
                'total_sessions'        => 0,
                'is_working_day'        => $this->isWorkingDay(),
            ]);
            return;
        }

        // Calculate totals (use completed sessions for duration)
        $totalMinutes = $completedSessions->sum('duration_minutes');

        // Get first check in from ALL sessions (including active)
        $firstCheckIn = $allSessions->min('check_in_time');

        // Get last checkout from completed sessions only
        $lastCheckOut = $completedSessions->max('check_out_time');

        // Calculate breaks from actual break records in all sessions
        $breakMinutes = 0;
        foreach ($allSessions as $session) {
            $breakMinutes += $session->breaks()
                ->whereNotNull('break_end')
                ->sum('duration_minutes');
        }

        // Also add gaps between completed sessions as break time
        $sortedCompletedSessions = $completedSessions->sortBy('check_in_time')->values();

        for ($i = 1; $i < $sortedCompletedSessions->count(); $i++) {
            $previousCheckOut = $sortedCompletedSessions[$i - 1]->check_out_time;
            $currentCheckIn   = $sortedCompletedSessions[$i]->check_in_time;

            if ($previousCheckOut && $currentCheckIn) {
                $breakMinutes += (int)abs($previousCheckOut->diffInMinutes($currentCheckIn));
            }
        }

        // Collect all unique IPs and locations from all sessions
        $ipAddresses = $allSessions->pluck('check_in_ip')
            ->merge($allSessions->pluck('check_out_ip'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $locations = $allSessions->pluck('check_in_location')
            ->merge($allSessions->pluck('check_out_location'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // On a non-working day (holiday/weekend) the whole day is extra work, so all
        // worked minutes are overtime. On a working day, only time beyond the standard
        // working day (derived from office start/end).
        $isWorkingDay    = $this->isWorkingDay();
        $overtimeMinutes = $isWorkingDay
            ? max(0, $totalMinutes - $this->fullDayMinutes())
            : $totalMinutes;

        // Calculate late minutes from first check-in vs scheduled start, using the
        // company's CURRENT late-grace policy (not the value snapshotted at check-in,
        // which goes stale when the policy changes).
        $graceMinutes = (int) $this->companySetting($this->company, 'late_grace');
        $lateMinutes  = 0;
        if ($firstCheckIn && $this->scheduled_start_time) {
            $scheduledStart = Carbon::parse($this->scheduled_start_time)
                ->setDate($firstCheckIn->year, $firstCheckIn->month, $firstCheckIn->day);
            $scheduledStartWithGrace = $scheduledStart->copy()->addMinutes($graceMinutes);

            if ($firstCheckIn->gt($scheduledStartWithGrace)) {
                $lateMinutes = (int) abs($scheduledStart->diffInMinutes($firstCheckIn));
            }
        }

        // Calculate early leave minutes from last check-out vs scheduled end (with early grace)
        $earlyLeaveMinutes = 0;
        if ($lastCheckOut && $this->scheduled_end_time) {
            $scheduledEnd = Carbon::parse($this->scheduled_end_time)
                ->setDate($lastCheckOut->year, $lastCheckOut->month, $lastCheckOut->day);

            $earlyGrace = (int) $this->companySetting($this->company, 'early_grace');
            $scheduledEndWithGrace = $scheduledEnd->copy()->subMinutes($earlyGrace);

            if ($lastCheckOut->lt($scheduledEndWithGrace)) {
                $earlyLeaveMinutes = (int) abs($lastCheckOut->diffInMinutes($scheduledEnd));
            }
        }

        // Determine status by precedence: leave → holiday → weekend → in-progress →
        // absent → present/late/half-day. Lateness is carried by late_minutes.
        $netWorkingMinutes = $totalMinutes - $breakMinutes;
        $inProgress        = $allSessions->contains('status', SessionStatus::Active);
        $status            = $this->determineStatus($netWorkingMinutes, $inProgress, $lateMinutes > 0, $earlyLeaveMinutes > 0);

        // Update summary
        $this->update([
            'first_check_in'        => $firstCheckIn ? $firstCheckIn->format('H:i:s') : null,
            'last_check_out'        => $lastCheckOut ? $lastCheckOut->format('H:i:s') : null,
            'total_working_minutes' => $totalMinutes,
            'total_break_minutes'   => $breakMinutes,
            'overtime_minutes'      => $overtimeMinutes,
            'late_minutes'          => $lateMinutes,
            'early_leave_minutes'   => $earlyLeaveMinutes,
            'grace_minutes'         => $graceMinutes,
            'total_sessions'        => $allSessions->count(),
            'status'                => $status,
            'is_working_day'        => $isWorkingDay,
            'ip_addresses'          => $ipAddresses,
            'locations'             => $locations,
        ]);
    }

    // Methods

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'employee_id', 'employee_id')
            ->where('attendance_sessions.attendance_date', $this->attendance_date)
            ->orderBy('session_number');
    }

    // Resolve the day's status by precedence (first match wins). Status is driven by
    // arrival (on time vs late) and whether the employee stayed the full day (vs
    // leaving early) — not by a strict minute count against the scheduled hours.
    // `Late` = worked a working day but arrived after the grace period.
    private function determineStatus(int $netWorkingMinutes, bool $inProgress, bool $isLate, bool $leftEarly): AttendanceStatus
    {
        // 1. Approved leave.
        if ($this->hasApprovedLeave()) {
            return AttendanceStatus::Leave;
        }

        // 2. Holiday / 3. Weekend — worked hours become overtime (see recalculate),
        //    but the day keeps its Holiday/Weekend label so the calendar is honest.
        if ($this->isHoliday()) {
            return AttendanceStatus::Holiday;
        }

        if (!$this->isWorkingDay()) {
            return AttendanceStatus::Weekend;
        }

        // Working day from here.
        // Still checked in (in progress): Late if they arrived late, else Present.
        if ($inProgress) {
            return $isLate ? AttendanceStatus::Late : AttendanceStatus::Present;
        }

        // No work recorded → Absent.
        if ($netWorkingMinutes <= 0) {
            return AttendanceStatus::Absent;
        }

        // Arrived late → Late, regardless of how many hours were worked.
        if ($isLate) {
            return AttendanceStatus::Late;
        }

        // On time and stayed the full day (not an early leave) → Present.
        if (!$leftEarly) {
            return AttendanceStatus::Present;
        }

        // On time but left early → Half Day if at least half the day was worked,
        // otherwise Absent.
        $halfMinutes = (int) $this->companySetting($this->company, 'half_day_hours') * 60;

        return $netWorkingMinutes >= $halfMinutes
            ? AttendanceStatus::HalfDay
            : AttendanceStatus::Absent;
    }

    // Full working day in minutes, always taken from the company work_hours setting.
    private function fullDayMinutes(): int
    {
        return (int) $this->companySetting($this->company, 'work_hours') * 60;
    }

    // Working day for this employee/date (holiday & weekend aware)
    private function isWorkingDay(): bool
    {
        return $this->employee
            ? app(WorkScheduleService::class)->isWorkingDay($this->employee, $this->attendance_date)
            : true;
    }

    private function isHoliday(): bool
    {
        return $this->employee
            ? app(WorkScheduleService::class)->isHoliday($this->employee, $this->attendance_date)
            : false;
    }

    private function hasApprovedLeave(): bool
    {
        if (!$this->employee_id) {
            return false;
        }

        return LeaveRequest::where('employee_id', $this->employee_id)
            ->where('status', LeaveRequestStatus::Approved)
            ->whereDate('started_at', '<=', $this->attendance_date)
            ->whereDate('ended_at', '>=', $this->attendance_date)
            ->exists();
    }

    public function addIpAddress($ip): void
    {
        $ips = $this->ip_addresses ?? [];
        if (!collect($ips)->contains($ip)) {
            $ips[] = $ip;
            $this->update(['ip_addresses' => $ips]);
        }
    }

    public function addLocation($location): void
    {
        $locations = $this->locations ?? [];
        if (!collect($locations)->contains($location)) {
            $locations[] = $location;
            $this->update(['locations' => $locations]);
        }
    }
}
