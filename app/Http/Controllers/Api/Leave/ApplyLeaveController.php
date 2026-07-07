<?php

namespace App\Http\Controllers\Api\Leave;

use App\Action\Leave\ApplyLeaveAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLeaveStoreRequest;
use App\Http\Resources\Api\LeaveResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class ApplyLeaveController extends Controller
{
    public function __invoke(ApiLeaveStoreRequest $request, ApplyLeaveAction $action): JsonResponse
    {
        try {
            $leaveRequest = $action->execute($request);

            return ResponseHandler::created(
                new LeaveResource($leaveRequest->load('leaveType')),
                'Leave request submitted.'
            );
        } catch (UnprocessableEntityHttpException $e) {
            return ResponseHandler::errorWrongArgs($e->getMessage(), 422);
        } catch (Throwable $e) {
            Log::error('[ApiLeaveApply] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to submit leave request.');
        }
    }
}
