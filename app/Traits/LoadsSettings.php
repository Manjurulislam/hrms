<?php

namespace App\Traits;

use App\Services\Backend\SettingService;

trait LoadsSettings
{
    protected function loadSettings(): void
    {
        try {
            $service = app(SettingService::class);
            $attendance = $service->getGroup('attendance');

            $map = [
                'min_session_duration'     => 'minimum_session_duration',
                'min_session_gap'          => 'minimum_session_gap',
                'max_sessions_per_day'     => 'max_sessions_per_day',
                'max_breaks_per_day'       => 'max_breaks_per_day',
                'min_break_duration'       => 'minimum_break_duration',
                'max_break_duration'       => 'maximum_break_duration',
                'default_office_start'     => 'default_office_start',
                'default_office_end'       => 'default_office_end',
                'late_grace_period'        => 'late_grace_period',
                'early_leave_grace_period' => 'early_leave_grace_period',
                'standard_working_hours'   => 'standard_working_hours',
                'half_day_hours'           => 'half_day_hours',
                'auto_close_enabled'       => 'auto_close_enabled',
                'auto_close_time'          => 'auto_close_time',
                'track_ip_address'         => 'track_ip_address',
                'track_location'           => 'track_location',
            ];

            foreach ($map as $dbKey => $configKey) {
                if (isset($attendance[$dbKey])) {
                    config(["attendance.{$configKey}" => $attendance[$dbKey]]);
                }
            }
        } catch (\Throwable) {
            // Falls back to config/attendance.php defaults
        }
    }
}
