<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentScheduleRequest;
use App\Models\Department;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DepartmentScheduleController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(DepartmentScheduleRequest $request, Department $department)
    {
        DB::beginTransaction();
        try {
            $schedule = $department->schedule()->create($this->prepareData($request));

            if ($days = $request->get('working_days')) {
                foreach ($days as $day) {
                    $schedule->workingDays()->create(['day' => $day]);
                }
            }
            DB::commit();
            return to_route('departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            DB::rollBack();
            return redirect()->back();
        }
    }

    protected function prepareData(DepartmentScheduleRequest $request)
    {
        return [
            'work_start_time' => Carbon::createFromFormat('h:i A', $request->get('work_start_time'))->format('H:i:s'),
            'work_end_time'   => Carbon::createFromFormat('h:i A', $request->get('work_end_time'))->format('H:i:s'),
        ];
    }

    /**
     * Get existing schedule data for editing
     */
    public function edit(Department $department)
    {
        try {
            // Use direct property access for one-to-one relationship
            $schedule = $department->schedule()->with('workingDays')->first();

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'No schedule found for this department'
                ], 404);
            }

            // Convert time back to 12-hour format for frontend
            $workStartTime = Carbon::createFromFormat('H:i:s', $schedule->work_start_time)->format('h:i A');
            $workEndTime   = Carbon::createFromFormat('H:i:s', $schedule->work_end_time)->format('h:i A');

            // Get working days array
            $workingDays = $schedule->workingDays->pluck('day')->toArray();

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'              => $schedule->id,
                    'work_start_time' => $workStartTime,
                    'work_end_time'   => $workEndTime,
                    'working_days'    => $workingDays,
                ]
            ]);

        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error fetching schedule data'
            ], 500);
        }
    }

    /**
     * @throws Throwable
     */
    public function update(DepartmentScheduleRequest $request, Department $department)
    {
        DB::beginTransaction();
        try {
            // Get the schedule model instance (one-to-one relationship)
            $schedule = $department->schedule;
            // Update schedule times
            $schedule->update($this->prepareData($request));

            // Handle working days update without deleting existing ones
            if ($days = $request->get('working_days')) {
                // Get existing working days
                $existingDays = $schedule->workingDays->pluck('day')->toArray();
                $newDays      = $days;

                // Find days to add (in new but not in existing)
                $daysToAdd = array_diff($newDays, $existingDays);

                // Find days to remove (in existing but not in new)
                $daysToRemove = array_diff($existingDays, $newDays);

                // Remove unwanted days
                if (!empty($daysToRemove)) {
                    $schedule->workingDays()->whereIn('day', $daysToRemove)->delete();
                }

                // Add new days
                foreach ($daysToAdd as $day) {
                    $schedule->workingDays()->create(['day' => $day]);
                }
            } else {
                // If no working days provided, remove all existing ones
                $schedule->workingDays()->delete();
            }

            DB::commit();
            return to_route('departments.index');

        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update schedule']);
        }
    }

    /**
     * Check if department has existing schedule
     */
    public function hasSchedule(Department $department)
    {
        $schedule = $department->schedule;
        return response()->json([
            'has_schedule' => $schedule !== null,
            'schedule_id'  => $schedule?->id
        ]);
    }
}
