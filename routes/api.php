<?php

use Illuminate\Support\Facades\Route;

// Temporary sanctum-protected probe used by ApiInfraTest; removed once auth routes exist.
Route::middleware('auth:sanctum')->get('_ping', fn () => 1);

require __DIR__ . '/apis/auth-routes.php';
require __DIR__ . '/apis/attendance-routes.php';
require __DIR__ . '/apis/leave-routes.php';
require __DIR__ . '/apis/notice-routes.php';
