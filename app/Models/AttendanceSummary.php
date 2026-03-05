<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSummary extends Model
{
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
        'ip_addresses' => 'array',
        'locations' => 'array',
        'is_working_day' => 'boolean',
        'status' => AttendanceStatus::class,
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

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'employee_id', 'employee_id')
            ->whereDate('attendance_date', $this->attendance_date)
            ->orderBy('session_number');
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class, 'employee_id', 'employee_id')
            ->whereDate('attendance_date', $this->attendance_date);
    }

    // Accessors
    public function getTotalWorkingHoursAttribute(): float
    {
        return round($this->total_working_minutes / 60, 2);
    }

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

    // Scopes
    public function scopePresent($query)
    {
        return $query->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::WorkFromHome]);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', AttendanceStatus::Absent);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);
    }

    // Methods
    public function recalculate(): void
    {
        // Get ALL sessions for the day (including active ones for first_check_in)
        $allSessions = $this->sessions()->get();

        // Get finished sessions (completed + auto-closed) for duration calculations
        $completedSessions = $allSessions->whereIn('status', [SessionStatus::Completed, SessionStatus::AutoClosed]);

        if ($allSessions->isEmpty()) {
            $this->update([
                'status' => AttendanceStatus::Absent,
                'total_working_minutes' => 0,
                'total_sessions' => 0,
            ]);
            return;
        }

        // Calculate totals (use completed sessions for duration)
        $totalMinutes = $completedSessions->sum('duration_minutes');

        // Get first check in from ALL sessions (including active)
        $firstCheckIn = $allSessions->min('check_in_time');

        // Get last checkout from completed sessions only
        $lastCheckOut = $completedSessions->max('check_out_time');

        // Calculate breaks (time between completed sessions)
        $breakMinutes = 0;
        $sortedCompletedSessions = $completedSessions->sortBy('check_in_time')->values();

        for ($i = 1; $i < $sortedCompletedSessions->count(); $i++) {
            $previousCheckOut = $sortedCompletedSessions[$i - 1]->check_out_time;
            $currentCheckIn = $sortedCompletedSessions[$i]->check_in_time;

            if ($previousCheckOut && $currentCheckIn) {
                $breakMinutes += (int) abs($previousCheckOut->diffInMinutes($currentCheckIn));
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

        // Calculate overtime (assuming 8 hours = 480 minutes standard)
        $standardMinutes = 480;
        $overtimeMinutes = max(0, $totalMinutes - $standardMinutes);

        // Determine status
        $netWorkingMinutes = $totalMinutes - $breakMinutes;
        $status = $this->determineStatus($netWorkingMinutes);

        // Update summary
        $this->update([
            'first_check_in' => $firstCheckIn ? $firstCheckIn->format('H:i:s') : null,
            'last_check_out' => $lastCheckOut ? $lastCheckOut->format('H:i:s') : null,
            'total_working_minutes' => $totalMinutes,
            'total_break_minutes' => $breakMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'total_sessions' => $allSessions->count(),
            'status' => $status,
            'ip_addresses' => $ipAddresses,
            'locations' => $locations,
        ]);
    }

    private function determineStatus($netWorkingMinutes): AttendanceStatus
    {
        $hours = $netWorkingMinutes / 60;

        if ($hours >= 8) {
            return AttendanceStatus::Present;
        } elseif ($hours >= 4) {
            return AttendanceStatus::HalfDay;
        } elseif ($hours > 0) {
            return AttendanceStatus::Late;
        }

        return AttendanceStatus::Absent;
    }

    public function addIpAddress($ip): void
    {
        $ips = $this->ip_addresses ?? [];
        if (!in_array($ip, $ips)) {
            $ips[] = $ip;
            $this->update(['ip_addresses' => $ips]);
        }
    }

    public function addLocation($location): void
    {
        $locations = $this->locations ?? [];
        if (!in_array($location, $locations)) {
            $locations[] = $location;
            $this->update(['locations' => $locations]);
        }
    }
}