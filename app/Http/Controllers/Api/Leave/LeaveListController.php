<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\ListLeaveAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeaveListController extends Controller
{
    public function __invoke(Request $request, ListLeaveAction $action): JsonResponse
    {
        try {
            return ResponseHandler::respondWithPagination(
                $action->execute($request->user()->employee, $request->integer('per_page', 20))
            );
        } catch (Throwable $e) {
            Log::error('[ApiLeaveList] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load leave requests.');
        }
    }
}
