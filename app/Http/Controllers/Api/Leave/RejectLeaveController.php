<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\RejectLeaveAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiRejectLeaveRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class RejectLeaveController extends Controller
{
    public function __invoke(ApiRejectLeaveRequest $request, LeaveRequest $leaveRequest, RejectLeaveAction $action): JsonResponse
    {
        try {
            $message = $action->execute($leaveRequest, $request->input('remarks'));

            return ResponseHandler::respondWithMessage($message);
        } catch (AccessDeniedHttpException $e) {
            return ResponseHandler::errorForbidden($e->getMessage());
        } catch (UnprocessableEntityHttpException $e) {
            return ResponseHandler::errorWrongArgs($e->getMessage(), 422);
        } catch (Throwable $e) {
            Log::error('[ApiLeaveReject] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to reject leave request.');
        }
    }
}
