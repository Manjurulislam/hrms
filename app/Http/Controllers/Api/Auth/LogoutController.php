<?php

namespace App\Http\Controllers\Api\Auth;

use App\Action\Auth\LogoutAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogoutController extends Controller
{
    public function __invoke(Request $request, LogoutAction $action): JsonResponse
    {
        try {
            $action->execute($request->user());

            return ResponseHandler::respondWithMessage('Logged out successfully.');
        } catch (Throwable $e) {
            Log::error('[ApiLogout] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Logout failed.');
        }
    }
}
