<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Action\Dashboard\GetDashboardAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetDashboardAction $action): JsonResponse
    {
        try {
            return ResponseHandler::success($action->execute($request->user()->employee));
        } catch (Throwable $e) {
            Log::error('[ApiDashboard] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load dashboard.');
        }
    }
}
