<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Action\Attendance\GetMonthlyAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiMonthlyAttendanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonthlyController extends Controller
{
    public function __invoke(ApiMonthlyAttendanceRequest $request, GetMonthlyAction $action): JsonResponse
    {
        try {
            return ResponseHandler::success(
                $action->execute($request->user()->employee, $request->year(), $request->month())
            );
        } catch (Throwable $e) {
            Log::error('[ApiAttendanceMonthly] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load monthly attendance.');
        }
    }
}
