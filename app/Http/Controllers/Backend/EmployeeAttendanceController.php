<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BreakEndRequest;
use App\Http\Requests\Attendance\BreakStartRequest;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Services\AttendanceService;
use App\Services\Utility\CatchIPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeAttendanceController extends Controller
{
    public function __construct(
        protected readonly AttendanceService $service,
        protected readonly CatchIPService    $ipService,
    ) {}

    public function index()
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return to_route('dashboard')->withErrors(['error' => 'No employee profile linked to your account.']);
        }

        return Inertia::render('Employee/attendance', [
            'userInfo'          => [
                'name'       => $employee->full_name,
                'position'   => $employee->designation?->title ?? 'Employee',
                'department' => $employee->department?->name,
                'company'    => $employee->company?->name,
                'employeeId' => $employee->id,
            ],
            'officeHours'       => $this->service->getOfficeHours($employee),
            'monthlyStats'      => $this->service->getMonthlyStats($employee),
            'todayData'         => $this->service->getTodayCompleteData($employee),
            'attendanceRecords' => $this->service->getAttendanceRecords($employee),
        ]);
    }

    public function startWork(CheckInRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $result = $this->service->checkIn(
            $employee,
            $this->resolveIp($request),
            $request->getSanitizedData()
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success'   => true,
            'message'   => $result['message'],
            'todayData' => $this->service->getTodayCompleteData($employee),
            'startTime' => data_get($result, 'session.check_in_time'),
        ]);
    }

    public function endWork(CheckOutRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $result = $this->service->checkOut(
            $employee,
            $this->resolveIp($request),
            $request->getSanitizedData()
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success'    => true,
            'message'    => $result['message'],
            'todayData'  => $this->service->getTodayCompleteData($employee),
            'endTime'    => data_get($result, 'session.check_out_time'),
            'totalHours' => $this->service->formatDuration(data_get($result, 'session.duration_minutes', 0) * 60),
        ]);
    }

    public function startBreak(BreakStartRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $result = $this->service->startBreak(
            $employee,
            $this->resolveIp($request),
            $request->input('break_type'),
            $request->input('reason')
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success'   => true,
            'message'   => $result['message'],
            'todayData' => $this->service->getTodayCompleteData($employee),
            'break'     => $result['break'],
        ]);
    }

    public function endBreak(BreakEndRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $result = $this->service->endBreak($employee, $this->resolveIp($request));

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success'       => true,
            'message'       => $result['message'],
            'todayData'     => $this->service->getTodayCompleteData($employee),
            'breakDuration' => $result['duration'],
        ]);
    }

    public function currentStatus(): JsonResponse
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee profile not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->service->getTodayCompleteData($employee),
        ]);
    }

    public function monthlyData(Request $request): JsonResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $employee = auth()->user()->employee;

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        [$year, $month] = explode('-', $request->month);

        return response()->json([
            'success' => true,
            'data'    => $this->service->getMonthlyData($employee, (int) $year, (int) $month),
        ]);
    }

    protected function resolveIp(Request $request): string
    {
        return $this->ipService->getPublicIp() ?? $request->ip();
    }
}
