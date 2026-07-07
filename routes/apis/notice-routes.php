<?php

use App\Http\Controllers\Api\Notice\NoticeListController;
use App\Http\Controllers\Api\Notice\ShowNoticeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notices')->group(function () {
    Route::get('/', NoticeListController::class);
    Route::get('{notice}', ShowNoticeController::class);
});
