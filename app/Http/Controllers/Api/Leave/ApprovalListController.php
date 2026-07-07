<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\ListApprovalsAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApprovalListController extends Controller
{
    public function __invoke(Request $request, ListApprovalsAction $action): JsonResponse
    {
        try {
            return ResponseHandler::success($action->execute($request->user()->employee));
        } catch (Throwable $e) {
            Log::error('[ApiApprovalList] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load approvals.');
        }
    }
}
