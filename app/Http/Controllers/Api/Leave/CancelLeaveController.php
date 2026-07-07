<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\CancelLeaveAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class CancelLeaveController extends Controller
{
    public function __invoke(Request $request, LeaveRequest $leaveRequest, CancelLeaveAction $action): JsonResponse
    {
        try {
            $action->execute($request->user()->employee, $leaveRequest);

            return ResponseHandler::respondWithMessage('Leave request cancelled.');
        } catch (AccessDeniedHttpException $e) {
            return ResponseHandler::errorForbidden($e->getMessage());
        } catch (UnprocessableEntityHttpException $e) {
            return ResponseHandler::errorWrongArgs($e->getMessage(), 422);
        } catch (Throwable $e) {
            Log::error('[ApiLeaveCancel] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to cancel leave request.');
        }
    }
}
