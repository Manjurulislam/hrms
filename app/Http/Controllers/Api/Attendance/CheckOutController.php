<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Action\Attendance\CheckOutAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiCheckOutRequest;
use App\Http\Resources\Api\AttendanceSessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class CheckOutController extends Controller
{
    public function __invoke(ApiCheckOutRequest $request, CheckOutAction $action): JsonResponse
    {
        try {
            $context = $action->execute($request);

            return ResponseHandler::success([
                'session'  => new AttendanceSessionResource($context->session),
                'duration' => $context->duration,
            ], 'Checked out successfully.');
        } catch (ConflictHttpException $e) {
            return ResponseHandler::errorConflict($e->getMessage());
        } catch (Throwable $e) {
            Log::error('[ApiCheckOut] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Check-out failed.');
        }
    }
}
