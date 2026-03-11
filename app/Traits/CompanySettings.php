<?php

namespace App\Traits;

use App\Models\Company;

trait CompanySettings
{
    private static array $defaults = [
        'office_start'    => '09:00',
        'office_end'      => '18:00',
        'work_hours'      => 8,
        'half_day_hours'  => 4,
        'late_grace'      => 15,
        'early_grace'     => 15,
        'max_sessions'    => 10,
        'min_session_gap' => 2,
        'max_breaks'      => 5,
        'auto_close'      => true,
        'auto_close_at'   => '23:59',
        'track_ip'        => true,
        'track_location'  => true,
    ];

    protected function companySetting(?Company $company, string $key): mixed
    {
        return $company?->{$key} ?? self::$defaults[$key] ?? null;
    }
}
