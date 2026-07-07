<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\ListLeaveTypesAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeaveTypeListController extends Controller
{
    public function __invoke(Request $request, ListLeaveTypesAction $action): JsonResponse
    {
        try {
            return ResponseHandler::success($action->execute($request->user()->employee));
        } catch (Throwable $e) {
            Log::error('[ApiLeaveTypes] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load leave types.');
        }
    }
}
