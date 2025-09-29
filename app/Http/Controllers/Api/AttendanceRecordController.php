<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSummary;
use App\Traits\QueryParams;
use App\Traits\PaginateQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;

class AttendanceRecordController extends Controller
{
    use QueryParams, PaginateQuery;

    /**
     * Get paginated attendance records
     */
    public function index(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'employee_id' => 'nullable|integer',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer',
            'page' => 'nullable|integer'
        ]);

        // Get employee ID - either from request or current user
        $employeeId = $request->employee_id;
        if (!$employeeId) {
            $user = Auth::user();
            if ($user && $user->employee) {
                $employeeId = $user->employee->id;
            }
        }

        if (!$employeeId) {
            return response()->json([
                'total' => 0,
                'data' => []
            ]);
        }

        // Build query
        $query = AttendanceSummary::query()
            ->where('employee_id', $employeeId)
            ->with(['employee:id,first_name,last_name,id_no']);

        // Filter by month
        if ($request->month) {
            [$year, $month] = explode('-', $request->month);
            $query->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month);
        } else {
            // Default to current month
            $query->whereYear('attendance_date', now()->year)
                ->whereMonth('attendance_date', now()->month);
        }

        // Apply search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('status', 'like', '%' . $request->search . '%')
                    ->orWhere('attendance_date', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort', []);
        if (!empty($sortBy) && isset($sortBy[0])) {
            $sortField = $sortBy[0]['key'] ?? 'attendance_date';
            $sortDirection = $sortBy[0]['order'] ?? 'desc';
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('attendance_date', 'desc');
        }

        // Paginate with transformation
        $rows = $request->get('per_page', 15);
        $result = $this->transformAttendance($query, $rows);

        return response()->json($result);
    }

    /**
     * Export attendance records
     */
    public function export(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'employee_id' => 'nullable|integer'
        ]);

        // Get employee ID
        $employeeId = $request->employee_id;
        if (!$employeeId) {
            $user = Auth::user();
            if ($user && $user->employee) {
                $employeeId = $user->employee->id;
            }
        }

        if (!$employeeId) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        [$year, $month] = explode('-', $request->month);

        $records = AttendanceSummary::where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->with('employee')
            ->get();

        // For now, return CSV format
        $csv = $this->generateCsv($records);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="attendance_' . $request->month . '.csv"');
    }

    /**
     * Generate CSV from records
     */
    private function generateCsv($records)
    {
        $output = "Date,Day,Check In,Check Out,Working Hours,Break Hours,Sessions,Status\n";

        foreach ($records as $record) {
            $date = Carbon::parse($record->attendance_date);

            // Format hours
            $workingHours = $record->total_working_minutes
                ? sprintf('%dh %dm', floor($record->total_working_minutes / 60), $record->total_working_minutes % 60)
                : '0h 0m';
            $breakHours = $record->total_break_minutes
                ? sprintf('%dh %dm', floor($record->total_break_minutes / 60), $record->total_break_minutes % 60)
                : '0h 0m';

            $output .= sprintf(
                "%s,%s,%s,%s,%s,%s,%d,%s\n",
                $date->format('Y-m-d'),
                $date->format('D'),
                $record->first_check_in ?: '--:--',
                $record->last_check_out ?: '--:--',
                $workingHours,
                $breakHours,
                $record->total_sessions ?? 0,
                $record->status
            );
        }

        return $output;
    }

}