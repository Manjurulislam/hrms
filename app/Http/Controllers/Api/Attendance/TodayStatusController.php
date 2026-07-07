<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Action\Attendance\GetTodayStatusAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TodayStatusController extends Controller
{
    public function __invoke(Request $request, GetTodayStatusAction $action): JsonResponse
    {
        try {
            return ResponseHandler::success($action->execute($request->user()->employee));
        } catch (Throwable $e) {
            Log::error('[ApiAttendanceToday] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load today\'s attendance.');
        }
    }
}
