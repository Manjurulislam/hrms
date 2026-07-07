<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Action\Attendance\GetRecordsAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiAttendanceRecordsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordsController extends Controller
{
    public function __invoke(ApiAttendanceRecordsRequest $request, GetRecordsAction $action): JsonResponse
    {
        try {
            return ResponseHandler::success(
                $action->execute($request->user()->employee, $request->months())
            );
        } catch (Throwable $e) {
            Log::error('[ApiAttendanceRecords] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load attendance records.');
        }
    }
}
