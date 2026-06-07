<?php

use App\Services\AttendanceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-close active attendance sessions once each company's auto_close_at time passes
// (also catches prior-day sessions that were never checked out). Runs often so the
// per-company auto_close_at time is honored closely; the service skips companies
// where auto_close is disabled and sessions whose close time has not yet arrived.
Schedule::call(function () {
    $count = app(AttendanceService::class)->autoCloseActiveSessions();

    if ($count > 0) {
        info("Auto-closed {$count} stale attendance session(s).");
    }
})->everyThirtyMinutes()->name('attendance:auto-close');
