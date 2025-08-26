<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentScheduleRequest;
use App\Models\Department;
use App\Models\DepartmentSchedule;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DepartmentScheduleController extends Controller
{
    use QueryParams, PaginateQuery;

    public function index()
    {
        return Inertia::render('Backend/DepartmentSchedule/index');
    }

    public function store(DepartmentScheduleRequest $request)
    {
        try {
            DepartmentSchedule::create($request->validated());
            return to_route('department-schedules.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function create()
    {
        $departments = Department::with('company:id,name')
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        return Inertia::render('Backend/DepartmentSchedule/create', [
            'departments'    => $departments,
            'workDayOptions' => $this->getWorkDayOptions()
        ]);
    }

    public function get(Request $request)
    {
        $query  = DepartmentSchedule::query()
            ->with(['department:id,name,company_id', 'department.company:id,name'])
            ->orderBy('id', 'desc');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);

        // Add formatted attributes to the response
        if (isset($result['data'])) {
            $result['data'] = collect($result['data'])->map(function ($schedule) {
                $schedule['work_days_string']          = $this->formatWorkDays($schedule['work_days']);
                $schedule['work_start_time_formatted'] = $this->formatTime($schedule['work_start_time']);
                $schedule['work_end_time_formatted']   = $this->formatTime($schedule['work_end_time']);
                $schedule['daily_work_hours']          = $this->calculateDailyHours($schedule['work_start_time'], $schedule['work_end_time']);
                $schedule['weekly_work_hours']         = $schedule['daily_work_hours'] * count($schedule['work_days'] ?? []);
                return $schedule;
            });
        }

        return response()->json($result);
    }

    /**
     * Format work days array to string
     */
    private function formatWorkDays(?array $workDays): string
    {
        if (!$workDays) {
            return '';
        }

        $dayNames = [
            'sunday'    => 'Sunday',
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
        ];

        return collect($workDays)
            ->map(fn($day) => $dayNames[$day] ?? $day)
            ->implode(', ');
    }

    /**
     * Format time for display
     */
    private function formatTime(?string $time): string
    {
        if (!$time) {
            return '';
        }
        return date('H:i', strtotime($time));
    }

    /**
     * Calculate daily work hours
     */
    private function calculateDailyHours(?string $startTime, ?string $endTime): float
    {
        if (!$startTime || !$endTime) {
            return 0;
        }

        $start = strtotime($startTime);
        $end   = strtotime($endTime);

        // Handle case where end time is next day
        if ($end <= $start) {
            $end += 24 * 60 * 60; // Add 24 hours
        }

        return ($end - $start) / 3600; // Convert to hours
    }

    /**
     * Get work day options for the form
     */
    private function getWorkDayOptions(): array
    {
        return [
            ['value' => 'sunday', 'label' => 'Sunday'],
            ['value' => 'monday', 'label' => 'Monday'],
            ['value' => 'tuesday', 'label' => 'Tuesday'],
            ['value' => 'wednesday', 'label' => 'Wednesday'],
            ['value' => 'thursday', 'label' => 'Thursday'],
            ['value' => 'friday', 'label' => 'Friday'],
            ['value' => 'saturday', 'label' => 'Saturday'],
        ];
    }

    public function edit(DepartmentSchedule $departmentSchedule)
    {
        $departments = Department::with('company:id,name')
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        $departmentSchedule->load(['department:id,name,company_id', 'department.company:id,name']);


        return Inertia::render('Backend/DepartmentSchedule/edit', [
            'item'           => $departmentSchedule,
            'departments'    => $departments,
            'workDayOptions' => $this->getWorkDayOptions()
        ]);
    }

    public function update(DepartmentScheduleRequest $request, DepartmentSchedule $departmentSchedule)
    {
        try {
            $departmentSchedule->fill($request->validated())->save();
            return to_route('department-schedules.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function destroy(DepartmentSchedule $departmentSchedule)
    {
        try {
            $departmentSchedule->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }
}
