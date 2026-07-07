<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\ShowApprovalAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShowApprovalController extends Controller
{
    public function __invoke(Request $request, LeaveRequest $leaveRequest, ShowApprovalAction $action): JsonResponse
    {
        if ($leaveRequest->current_approver_id !== $request->user()->employee->id) {
            return ResponseHandler::errorForbidden('You are not the current approver.');
        }

        try {
            return ResponseHandler::respondWithResource($action->execute($leaveRequest));
        } catch (Throwable $e) {
            Log::error('[ApiApprovalShow] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load approval.');
        }
    }
}
