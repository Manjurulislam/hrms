<?php

namespace App\Http\Controllers\Api\Auth;

use App\Action\Auth\GetProfileAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EmployeeResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeController extends Controller
{
    public function __invoke(Request $request, GetProfileAction $action): JsonResponse
    {
        try {
            return ResponseHandler::respondWithResource(
                new EmployeeResource($action->execute($request->user()))
            );
        } catch (AuthorizationException $e) {
            return ResponseHandler::errorForbidden($e->getMessage());
        } catch (Throwable $e) {
            Log::error('[ApiMe] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load profile.');
        }
    }
}
