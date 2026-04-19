<?php

use App\Services\AttendanceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-close stale attendance sessions from previous days that were not checked out
Schedule::call(function () {
    $count = app(AttendanceService::class)->autoCloseActiveSessions();

    if ($count > 0) {
        info("Auto-closed {$count} stale attendance session(s).");
    }
})->dailyAt('00:05')->name('attendance:auto-close');
