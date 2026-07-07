<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\ApproveLeaveAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiApproveLeaveRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class ApproveLeaveController extends Controller
{
    public function __invoke(ApiApproveLeaveRequest $request, LeaveRequest $leaveRequest, ApproveLeaveAction $action): JsonResponse
    {
        try {
            $message = $action->execute($leaveRequest, $request->input('remarks'));

            return ResponseHandler::respondWithMessage($message);
        } catch (AccessDeniedHttpException $e) {
            return ResponseHandler::errorForbidden($e->getMessage());
        } catch (UnprocessableEntityHttpException $e) {
            return ResponseHandler::errorWrongArgs($e->getMessage(), 422);
        } catch (Throwable $e) {
            Log::error('[ApiLeaveApprove] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to approve leave request.');
        }
    }
}
