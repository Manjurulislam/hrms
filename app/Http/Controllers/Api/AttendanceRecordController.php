<?php

namespace App\Http\Controllers\Api;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyDataRequest;
use App\Services\Backend\AttendanceRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceRecordController extends Controller
{
    public function __construct(
        protected readonly AttendanceRecordService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function export(MonthlyDataRequest $request): BinaryFileResponse|JsonResponse
    {
        $employeeId = $this->service->resolveEmployeeId($request);

        if (!$employeeId) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $filename = "attendance_{$request->input('month')}.xlsx";

        return Excel::download(
            new AttendanceExport($employeeId, $request->input('month')),
            $filename
        );
    }
}
