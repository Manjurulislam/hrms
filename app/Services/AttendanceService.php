<?php

namespace App\Services;

use App\Models\AttendanceBreak;
use App\Models\AttendanceSession;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Services\WorkScheduleService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    protected WorkScheduleService $scheduleService;

    public function __construct(WorkScheduleService $scheduleService = null)
    {
        $this->scheduleService = $scheduleService ?: new WorkScheduleService();
    }

    /**
     * Process check-in for an employee
     */
    public function checkIn(Employee $employee, string $ip, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $today = today();

            // Check if today is a working day
            $isWorkingDay = $this->scheduleService->isWorkingDay($employee, $today);
            $sessionType = 'regular';

            // If it's a weekend, mark session as overtime
            if (!$isWorkingDay) {
                $sessionType = 'overtime';
            }

            // Check for active session
            $activeSession = AttendanceSession::forEmployee($employee->id)
                ->today()
                ->active()
                ->first();

            if ($activeSession) {
                return [
                    'success' => false,
                    'message' => 'You already have an active session. Please check out first.',
                    'session' => $activeSession
                ];
            }

            // Check for active break
            $activeBreak = AttendanceBreak::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->active()
                ->first();

            if ($activeBreak) {
                return [
                    'success' => false,
                    'message' => 'You have an active break. Please end your break first.',
                    'break' => $activeBreak
                ];
            }

            // Get session number for today
            $sessionNumber = AttendanceSession::forEmployee($employee->id)
                ->today()
                ->max('session_number') + 1;

            // Get schedule information from company
            $company = $employee->company;
            $scheduledStartTime = null;
            $scheduledEndTime = null;
            $lateMinutes = 0;

            if ($company) {
                $scheduledStartTime = $company->office_start_time;
                $scheduledEndTime = $company->office_end_time;

                // Calculate if late
                $lateMinutes = $this->scheduleService->calculateLateMinutes($employee, now());
            }

            // Create new session
            $session = AttendanceSession::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'department_id' => $employee->department_id,
                'attendance_date' => today(),
                'session_number' => $sessionNumber,
                'check_in_time' => now(),
                'scheduled_start_time' => $scheduledStartTime,
                'scheduled_end_time' => $scheduledEndTime,
                'check_in_ip' => $ip,
                'check_in_location' => $data['location'] ?? 'office',
                'check_in_lat' => $data['lat'] ?? null,
                'check_in_long' => $data['long'] ?? null,
                'check_in_note' => $data['note'] ?? null,
                'session_type' => $sessionType,
                'status' => 'active',
                'is_late' => $lateMinutes > 0,
                'is_overtime' => !$isWorkingDay,
            ]);

            // Update or create daily summary
            $this->updateDailySummary($employee, $ip, $data['location'] ?? 'office');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Checked in successfully',
                'session' => $session
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to check in: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process check-out for an employee
     */
    public function checkOut(Employee $employee, string $ip, array $data = []): array
    {
        try {
            DB::beginTransaction();

            // Find active session
            $activeSession = AttendanceSession::forEmployee($employee->id)
                ->today()
                ->active()
                ->first();

            if (!$activeSession) {
                return [
                    'success' => false,
                    'message' => 'No active check-in found for today.'
                ];
            }

            // Check minimum session duration (1 minute)
            $duration = now()->diffInMinutes($activeSession->check_in_time);
            if ($duration < 1) {
                return [
                    'success' => false,
                    'message' => 'Session too short. Minimum 1 minute required.'
                ];
            }

            // Process check-out
            $activeSession->checkOut(
                $ip,
                $data['location'] ?? 'office',
                $data['lat'] ?? null,
                $data['long'] ?? null,
                $data['note'] ?? null
            );

            // Update daily summary
            $this->updateDailySummary($employee, $ip, $data['location'] ?? 'office');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Checked out successfully',
                'session' => $activeSession->fresh(),
                'duration' => $duration . ' minutes'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to check out: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Start a break
     */
    public function startBreak(Employee $employee, string $ip, string $breakType = 'personal', string $reason = null): array
    {
        try {
            DB::beginTransaction();

            // Check for active session
            $activeSession = AttendanceSession::forEmployee($employee->id)
                ->today()
                ->active()
                ->first();

            if (!$activeSession) {
                return [
                    'success' => false,
                    'message' => 'No active session found. Please check in first.'
                ];
            }

            // Check for existing active break
            $activeBreak = AttendanceBreak::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->active()
                ->first();

            if ($activeBreak) {
                return [
                    'success' => false,
                    'message' => 'You already have an active break.'
                ];
            }

            // Create break
            $break = AttendanceBreak::create([
                'employee_id' => $employee->id,
                'attendance_session_id' => $activeSession->id,
                'attendance_date' => today(),
                'break_start' => now(),
                'break_start_ip' => $ip,
                'break_type' => $breakType,
                'reason' => $reason,
                'status' => 'active',
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Break started successfully',
                'break' => $break
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to start break: ' . $e->getMessage()
            ];
        }
    }

    /**
     * End a break
     */
    public function endBreak(Employee $employee, string $ip): array
    {
        try {
            DB::beginTransaction();

            // Find active break
            $activeBreak = AttendanceBreak::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->active()
                ->first();

            if (!$activeBreak) {
                return [
                    'success' => false,
                    'message' => 'No active break found.'
                ];
            }

            // End the break
            $activeBreak->endBreak($ip);

            // Update daily summary for break time
            $this->updateDailySummary($employee, $ip);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Break ended successfully',
                'break' => $activeBreak->fresh(),
                'duration' => $activeBreak->duration_minutes . ' minutes'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to end break: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get today's attendance status for an employee
     */
    public function getTodayStatus(Employee $employee): array
    {
        $today = today();

        // Get active session
        $activeSession = AttendanceSession::forEmployee($employee->id)
            ->today()
            ->active()
            ->first();

        // Get active break
        $activeBreak = AttendanceBreak::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->active()
            ->first();

        // Get all sessions today
        $todaySessions = AttendanceSession::forEmployee($employee->id)
            ->today()
            ->orderBy('session_number')
            ->get();

        // Get summary
        $summary = AttendanceSummary::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        return [
            'has_active_session' => !is_null($activeSession),
            'active_session' => $activeSession,
            'has_active_break' => !is_null($activeBreak),
            'active_break' => $activeBreak,
            'today_sessions' => $todaySessions,
            'summary' => $summary,
            'can_check_in' => is_null($activeSession) && is_null($activeBreak),
            'can_check_out' => !is_null($activeSession),
            'can_start_break' => !is_null($activeSession) && is_null($activeBreak),
            'can_end_break' => !is_null($activeBreak),
        ];
    }

    /**
     * Get today's complete data (for replacing localStorage)
     */
    public function getTodayCompleteData(Employee $employee): array
    {
        $today = today();

        // Get all sessions for today
        $sessions = AttendanceSession::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->orderBy('session_number')
            ->get();

        // Get active session
        $activeSession = $sessions->where('status', 'active')->first();

        // Get active break
        $activeBreak = AttendanceBreak::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->where('status', 'active')
            ->first();

        // Get summary
        $summary = AttendanceSummary::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        // Calculate total worked seconds
        $totalWorkedSeconds = 0;
        $completedSessions = $sessions->where('status', 'completed');

        foreach ($completedSessions as $session) {
            if ($session->duration_minutes) {
                $totalWorkedSeconds += ($session->duration_minutes * 60);
            }
        }

        // If there's an active session, calculate current session time
        $currentSessionSeconds = 0;
        if ($activeSession) {
            $currentSessionSeconds = $activeSession->check_in_time->diffInSeconds(now());
        }

        // Get first check in time from sessions (not just summary)
        $firstCheckInTime = null;
        if ($sessions->isNotEmpty()) {
            $firstSession = $sessions->first();
            $firstCheckInTime = $firstSession->check_in_time->format('h:i A');
        }

        // Get last check out time
        $lastCheckOutTime = null;
        $completedSessionsWithCheckout = $sessions->whereNotNull('check_out_time');
        if ($completedSessionsWithCheckout->isNotEmpty()) {
            $lastSession = $completedSessionsWithCheckout->last();
            $lastCheckOutTime = $lastSession->check_out_time->format('h:i A');
        }

        // Format sessions for frontend
        $formattedSessions = $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'startTime' => $session->check_in_time->toIso8601String(),
                'endTime' => $session->check_out_time ? $session->check_out_time->toIso8601String() : null,
                'duration' => $session->duration_minutes ? $session->duration_minutes * 60 : 0,
                'status' => $session->status,
                'sessionNumber' => $session->session_number
            ];
        })->toArray();

        return [
            'sessions' => $formattedSessions,
            'totalWorkedSeconds' => $totalWorkedSeconds,
            'currentSession' => $activeSession ? [
                'startTime' => $activeSession->check_in_time->toIso8601String(),
                'isWorking' => true,
                'sessionId' => $activeSession->id,
                'currentDuration' => $currentSessionSeconds
            ] : null,
            'currentBreak' => $activeBreak ? [
                'startTime' => $activeBreak->break_start->toIso8601String(),
                'breakType' => $activeBreak->break_type,
                'breakId' => $activeBreak->id
            ] : null,
            'summary' => [
                'firstCheckIn' => $firstCheckInTime ?? '--:--',
                'lastCheckOut' => $lastCheckOutTime ?? '--:--',
                'totalHours' => $this->formatDuration($totalWorkedSeconds + $currentSessionSeconds),
                'status' => $summary ? $summary->status : 'absent'
            ]
        ];
    }

    /**
     * Format duration from seconds
     */
    private function formatDuration($seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "{$hours}h {$minutes}m";
    }

    /**
     * Update daily summary
     */
    private function updateDailySummary(Employee $employee, string $ip, string $location = 'office'): void
    {
        $today        = today();
        $company      = $employee->company;
        $isWorkingDay = $this->scheduleService->isWorkingDay($employee, $today);

        $summary = AttendanceSummary::firstOrCreate(
            [
                'employee_id'   => $employee->id,
                'attendance_date' => $today,
            ],
            [
                'company_id'           => $employee->company_id,
                'department_id'        => $employee->department_id,
                'scheduled_start_time' => $company?->office_start_time,
                'scheduled_end_time'   => $company?->office_end_time,
                'grace_minutes'        => config('attendance.late_grace_period', 15),
                'is_working_day'       => $isWorkingDay,
                'shift_name'           => $company ? 'Regular' : 'Default',
            ]
        );

        // Add IP and location to the arrays
        $summary->addIpAddress($ip);
        $summary->addLocation($location);

        // Recalculate all metrics
        $summary->recalculate();
    }

    /**
     * Determine session type based on time
     */
    private function determineSessionType(Employee $employee): string
    {
        $company = $employee->company;

        if (!$company || !$company->office_start_time) {
            $hour = now()->hour;
            if ($hour >= 18 || $hour < 8) {
                return 'overtime';
            }
            return 'regular';
        }

        return 'regular';
    }

    /**
     * Auto close all active sessions at end of day
     */
    public function autoCloseActiveSessions(): int
    {
        $activeSessions = AttendanceSession::where('status', 'active')
            ->whereDate('attendance_date', '<', today())
            ->get();

        $count = 0;
        foreach ($activeSessions as $session) {
            $session->autoClose();
            $count++;

            // Update summary
            $employee = $session->employee;
            if ($employee) {
                $this->updateDailySummary($employee, $session->check_in_ip, $session->check_in_location);
            }
        }

        return $count;
    }

    /**
     * Get monthly attendance report
     */
    public function getMonthlyReport(Employee $employee, int $year, int $month): array
    {
        $summaries = AttendanceSummary::where('employee_id', $employee->id)
            ->forMonth($year, $month)
            ->orderBy('attendance_date')
            ->get();

        $stats = [
            'total_days' => $summaries->count(),
            'present_days' => $summaries->where('status', 'present')->count(),
            'absent_days' => $summaries->where('status', 'absent')->count(),
            'half_days' => $summaries->where('status', 'half_day')->count(),
            'late_days' => $summaries->where('status', 'late')->count(),
            'holidays' => $summaries->where('status', 'holiday')->count(),
            'leaves' => $summaries->where('status', 'leave')->count(),
            'total_working_hours' => round($summaries->sum('total_working_minutes') / 60, 2),
            'total_overtime_hours' => round($summaries->sum('overtime_minutes') / 60, 2),
            'average_working_hours' => $summaries->count() > 0
                ? round($summaries->avg('total_working_minutes') / 60, 2)
                : 0,
        ];

        return [
            'summaries' => $summaries,
            'stats' => $stats
        ];
    }
}