<?php

namespace App\Http\Controllers\Api\Auth;

use App\Action\Auth\LoginAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginController extends Controller
{
    public function __invoke(ApiLoginRequest $request, LoginAction $action): JsonResponse
    {
        try {
            return ResponseHandler::success($action->execute($request), 'Logged in successfully.');
        } catch (AuthorizationException $e) {
            return ResponseHandler::errorForbidden($e->getMessage());
        } catch (Throwable $e) {
            Log::error('[ApiLogin] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Login failed.');
        }
    }
}
