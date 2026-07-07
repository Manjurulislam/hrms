<?php

namespace App\Http\Controllers\Api\Notice;

use App\Action\Notice\ListNoticesAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class NoticeListController extends Controller
{
    public function __invoke(Request $request, ListNoticesAction $action): JsonResponse
    {
        try {
            return ResponseHandler::respondWithPagination(
                $action->execute($request->user()->employee, $request->integer('per_page', 10))
            );
        } catch (Throwable $e) {
            Log::error('[ApiNoticeList] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load notices.');
        }
    }
}
