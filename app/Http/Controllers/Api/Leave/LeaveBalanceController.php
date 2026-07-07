<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\GetLeaveBalanceAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeaveBalanceController extends Controller
{
    public function __invoke(Request $request, GetLeaveBalanceAction $action): JsonResponse
    {
        try {
            $year = $request->integer('year', now()->year);

            return ResponseHandler::success($action->execute($request->user()->employee, $year));
        } catch (Throwable $e) {
            Log::error('[ApiLeaveBalance] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load leave balance.');
        }
    }
}
