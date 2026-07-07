<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Action\Attendance\CheckInAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiCheckInRequest;
use App\Http\Resources\Api\AttendanceSessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class CheckInController extends Controller
{
    public function __invoke(ApiCheckInRequest $request, CheckInAction $action): JsonResponse
    {
        try {
            return ResponseHandler::created(
                new AttendanceSessionResource($action->execute($request)),
                'Checked in successfully.'
            );
        } catch (ConflictHttpException $e) {
            return ResponseHandler::errorConflict($e->getMessage());
        } catch (Throwable $e) {
            Log::error('[ApiCheckIn] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Check-in failed.');
        }
    }
}
