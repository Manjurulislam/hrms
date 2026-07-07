<?php

namespace App\Http\Controllers\Api\Notice;

use App\Action\Notice\ShowNoticeAction;
use App\Facades\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NoticeResource;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShowNoticeController extends Controller
{
    public function __invoke(Request $request, Notice $notice, ShowNoticeAction $action): JsonResponse
    {
        try {
            $visible = $action->execute($request->user()->employee, $notice->id);

            if (! $visible) {
                return ResponseHandler::errorNotFound('Notice not found.');
            }

            return ResponseHandler::respondWithResource(new NoticeResource($visible));
        } catch (Throwable $e) {
            Log::error('[ApiNoticeShow] ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

            return ResponseHandler::errorInternalError('Failed to load notice.');
        }
    }
}
