<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BreakEndRequest;
use App\Http\Requests\Attendance\BreakStartRequest;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Models\AttendanceSummary;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeAttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display attendance dashboard
     */
    public function index(): Response
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->renderNoEmployeePage($user);
        }

        // Get all necessary data from service
        $todayData = $this->attendanceService->getTodayCompleteData($employee);
        $monthlyStats = $this->getMonthlyStats($employee);
        $officeHours = $this->getOfficeHours($employee);
        $attendanceRecords = $this->getAttendanceRecords($employee);

        return Inertia::render('Employee/attendance', [
            'userInfo' => [
                'name' => $employee->full_name,
                'position' => $employee->designations->first()->title ?? 'Employee',
                'department' => $employee->department->name,
                'company' => $employee->department->company->name,
                'employeeId' => $employee->id
            ],
            'officeHours' => $officeHours,
            'monthlyStats' => $monthlyStats,
            'todayData' => $todayData,
            'attendanceRecords' => $attendanceRecords
        ]);
    }

    /**
     * Start work (Check-in) - API endpoint for child components
     */
    public function startWork(CheckInRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        // Use validated and sanitized data
        $result = $this->attendanceService->checkIn(
            $employee,
            $request->ip(),
            $request->getSanitizedData()
        );

        if ($result['success']) {
            // Get updated today's data
            $todayData = $this->attendanceService->getTodayCompleteData($employee);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'todayData' => $todayData,
                'startTime' => $result['session']->check_in_time->format('h:i A')
            ]);
        }

        return response()->json($result, 422);
    }

    /**
     * End work (Check-out) - API endpoint for child components
     */
    public function endWork(CheckOutRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        // Use validated and sanitized data
        $result = $this->attendanceService->checkOut(
            $employee,
            $request->ip(),
            $request->getSanitizedData()
        );

        if ($result['success']) {
            // Get updated today's data
            $todayData = $this->attendanceService->getTodayCompleteData($employee);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'todayData' => $todayData,
                'endTime' => $result['session']->check_out_time->format('h:i A'),
                'totalHours' => $this->formatDuration($result['session']->duration_minutes * 60)
            ]);
        }

        return response()->json($result, 422);
    }

    /**
     * Start break - API endpoint
     */
    public function startBreak(BreakStartRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $result = $this->attendanceService->startBreak(
            $employee,
            $request->ip(),
            $request->input('break_type'),
            $request->input('reason')
        );

        if ($result['success']) {
            $todayData = $this->attendanceService->getTodayCompleteData($employee);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'todayData' => $todayData,
                'break' => $result['break']
            ]);
        }

        return response()->json($result, 422);
    }

    /**
     * End break - API endpoint
     */
    public function endBreak(BreakEndRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $result = $this->attendanceService->endBreak(
            $employee,
            $request->ip()
        );

        if ($result['success']) {
            $todayData = $this->attendanceService->getTodayCompleteData($employee);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'todayData' => $todayData,
                'breakDuration' => $result['duration']
            ]);
        }

        return response()->json($result, 422);
    }

    /**
     * Get current status - API endpoint for syncing
     */
    public function currentStatus(): JsonResponse
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found'
            ], 404);
        }

        $todayData = $this->attendanceService->getTodayCompleteData($employee);

        return response()->json([
            'success' => true,
            'data' => $todayData
        ]);
    }

    /**
     * Get monthly attendance data - API endpoint
     */
    public function monthlyData(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        $employee = Auth::user()->employee;

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        [$year, $month] = explode('-', $request->month);

        $summaries = AttendanceSummary::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->get();

        $data = $summaries->map(function ($summary) {
            return [
                'date' => $summary->attendance_date->day,
                'checkIn' => $summary->first_check_in
                    ? Carbon::parse($summary->first_check_in)->format('H:i')
                    : null,
                'checkOut' => $summary->last_check_out
                    ? Carbon::parse($summary->last_check_out)->format('H:i')
                    : null,
                'hours' => $summary->total_working_hours,
                'status' => $this->mapStatus($summary->status),
                'late' => $summary->late_minutes > 0
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Render page for users without employee profile
     */
    private function renderNoEmployeePage($user): Response
    {
        return Inertia::render('Employee/attendance', [
            'userInfo' => [
                'name' => $user->name,
                'position' => 'Not Assigned'
            ],
            'officeHours' => [
                'start' => config('attendance.default_office_start', '9:00 AM'),
                'end' => config('attendance.default_office_end', '6:00 PM')
            ],
            'monthlyStats' => [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'rate' => 0
            ],
            'todayData' => null,
            'attendanceRecords' => []
        ]);
    }

    /**
     * Get office hours from department or config
     */
    private function getOfficeHours($employee): array
    {
        $schedule = $employee->department->schedule ?? null;

        if ($schedule && $schedule->work_start_time && $schedule->work_end_time) {
            return [
                'start' => Carbon::parse($schedule->work_start_time)->format('g:i A'),
                'end' => Carbon::parse($schedule->work_end_time)->format('g:i A'),
                'delay' => $schedule->delay ?? 0,
                'office_ip' => $schedule->office_ip ?? null,
                'company' => $employee->department->company->name ?? 'N/A',
                'department' => $employee->department->name ?? 'N/A'
            ];
        }

        return [
            'start' => Carbon::parse(config('attendance.default_office_start', '09:00'))->format('g:i A'),
            'end' => Carbon::parse(config('attendance.default_office_end', '18:00'))->format('g:i A'),
            'delay' => config('attendance.late_grace_period', 15),
            'office_ip' => null,
            'company' => $employee->department->company->name ?? 'N/A',
            'department' => $employee->department->name ?? 'N/A'
        ];
    }

    /**
     * Get monthly statistics
     */
    private function getMonthlyStats($employee): array
    {
        $currentMonth = now();
        $monthlyReport = $this->attendanceService->getMonthlyReport(
            $employee,
            $currentMonth->year,
            $currentMonth->month
        );

        $workingDays = $this->calculateWorkingDays($currentMonth->year, $currentMonth->month);
        $attendanceRate = $workingDays > 0
            ? round(($monthlyReport['stats']['present_days'] / $workingDays) * 100)
            : 0;

        return [
            'present' => $monthlyReport['stats']['present_days'],
            'absent' => $monthlyReport['stats']['absent_days'],
            'late' => $monthlyReport['stats']['late_days'],
            'rate' => $attendanceRate
        ];
    }

    /**
     * Get attendance records for display
     */
    private function getAttendanceRecords($employee): array
    {
        $records = [];

        // Get last 3 months
        for ($i = 0; $i < 3; $i++) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            $summaries = AttendanceSummary::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $date->year)
                ->whereMonth('attendance_date', $date->month)
                ->orderBy('attendance_date')
                ->get();

            $monthData = [];
            foreach ($summaries as $summary) {
                $monthData[] = [
                    'date' => $summary->attendance_date->day,
                    'checkIn' => $summary->first_check_in
                        ? Carbon::parse($summary->first_check_in)->format('H:i')
                        : null,
                    'checkOut' => $summary->last_check_out
                        ? Carbon::parse($summary->last_check_out)->format('H:i')
                        : null,
                    'hours' => $summary->total_working_hours,
                    'status' => $this->mapStatus($summary->status),
                    'late' => $summary->late_minutes > 0
                ];
            }

            if (!empty($monthData)) {
                $records[$monthKey] = $monthData;
            }
        }

        return $records;
    }

    /**
     * Calculate working days in a month
     */
    private function calculateWorkingDays($year, $month): int
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        while ($startDate <= $endDate) {
            if (!$startDate->isWeekend()) {
                $workingDays++;
            }
            $startDate->addDay();
        }

        return $workingDays;
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
     * Map internal status to display status
     */
    private function mapStatus($status): string
    {
        $statusMap = [
            'present' => 'Present',
            'absent' => 'Absent',
            'half_day' => 'Half Day',
            'late' => 'Present',
            'holiday' => 'Holiday',
            'weekend' => 'Weekend',
            'leave' => 'Leave',
            'work_from_home' => 'WFH'
        ];

        return $statusMap[$status] ?? 'Unknown';
    }
}