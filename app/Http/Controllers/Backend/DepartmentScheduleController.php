<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentScheduleRequest;
use App\Models\Department;
use App\Services\CatchIPService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentScheduleController extends Controller
{
    public function store(DepartmentScheduleRequest $request, Department $department)
    {
        return $this->handleScheduleOperation($request, $department, 'create');
    }

    private function handleScheduleOperation(DepartmentScheduleRequest $request, Department $department, string $operation)
    {
        DB::beginTransaction();

        try {
            $scheduleData = $this->prepareScheduleData($request, $department);

            if ($operation === 'create') {
                $schedule = $department->schedule()->create($scheduleData);
            } else {
                $schedule = $department->schedule;
                $schedule->update($scheduleData);
            }

            $this->syncWorkingDays($schedule, $request->get('working_days', []));

            DB::commit();
            return to_route('departments.index');

        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            DB::rollBack();

            $errorMessage = $operation === 'update' ? 'Failed to update schedule' : 'Failed to create schedule';
            return redirect()->back()->withErrors(['error' => $errorMessage]);
        }
    }

    private function prepareScheduleData(DepartmentScheduleRequest $request, Department $department): array
    {
        return [
            'office_ip'       => app(CatchIPService::class)->getPublicIp(),
            'company_id'      => $department->company_id,
            'work_start_time' => $this->formatTimeTo24Hour($request->get('work_start_time')),
            'work_end_time'   => $this->formatTimeTo24Hour($request->get('work_end_time')),
        ];
    }

    private function formatTimeTo24Hour(string $time): string
    {
        return Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
    }

    public function update(DepartmentScheduleRequest $request, Department $department)
    {
        return $this->handleScheduleOperation($request, $department, 'update');
    }

    private function syncWorkingDays($schedule, array $workingDays): void
    {
        if (empty($workingDays)) {
            $schedule->workingDays()->delete();
            return;
        }

        $existingDays = $schedule->workingDays->pluck('day')->toArray();

        // Remove days that are no longer selected
        $daysToRemove = array_diff($existingDays, $workingDays);
        if (!empty($daysToRemove)) {
            $schedule->workingDays()->whereIn('day', $daysToRemove)->delete();
        }

        // Add new days
        $daysToAdd = array_diff($workingDays, $existingDays);
        foreach ($daysToAdd as $day) {
            $schedule->workingDays()->create(['day' => $day]);
        }
    }

    public function edit(Department $department)
    {
        try {
            $schedule = $department->schedule()->with('workingDays')->first();

            if (!$schedule) {
                return $this->jsonResponse(false, 'No schedule found for this department', null, 404);
            }

            return $this->jsonResponse(true, 'Schedule data retrieved', [
                'id'              => $schedule->id,
                'work_start_time' => $this->formatTimeTo12Hour($schedule->work_start_time),
                'work_end_time'   => $this->formatTimeTo12Hour($schedule->work_end_time),
                'working_days'    => $schedule->workingDays->pluck('day')->toArray(),
            ]);

        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return $this->jsonResponse(false, 'Error fetching schedule data', null, 500);
        }
    }

    private function jsonResponse(bool $success, string $message, $data = null, int $status = 200)
    {
        $response = ['success' => $success, 'message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    private function formatTimeTo12Hour(string $time): string
    {
        return Carbon::createFromFormat('H:i:s', $time)->format('h:i A');
    }

    public function hasSchedule(Department $department)
    {
        $schedule = $department->schedule;

        return response()->json([
            'has_schedule' => $schedule !== null,
            'schedule_id'  => $schedule?->id
        ]);
    }
}
