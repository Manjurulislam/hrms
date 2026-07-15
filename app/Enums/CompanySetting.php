<?php

namespace App\Enums;

enum CompanySetting: string
{
    case OfficeStart   = 'office_start';
    case OfficeEnd     = 'office_end';
    case CheckInOpen   = 'check_in_open';
    case WorkHours     = 'work_hours';
    case HalfDayHours  = 'half_day_hours';
    case LateGrace     = 'late_grace';
    case EarlyGrace    = 'early_grace';
    case MaxSessions   = 'max_sessions';
    case MinSessionGap = 'min_session_gap';
    case MaxBreaks     = 'max_breaks';
    case AutoClose     = 'auto_close';
    case AutoCloseAt   = 'auto_close_at';
    case TrackIp       = 'track_ip';
    case TrackLocation = 'track_location';

    // Default applied when a company has no value for this setting (e.g. settings
    // like check_in_open that are not stored as a column).
    public function default(): string|int|bool
    {
        return match ($this) {
            self::OfficeStart   => '09:00',
            self::OfficeEnd     => '18:00',
            self::CheckInOpen   => '06:00',
            self::WorkHours     => 9,
            self::HalfDayHours  => 5,
            self::LateGrace     => 30,
            self::EarlyGrace    => 15,
            self::MaxSessions   => 10,
            self::MinSessionGap => 2,
            self::MaxBreaks     => 5,
            self::AutoClose     => true,
            self::AutoCloseAt   => '23:59',
            self::TrackIp       => true,
            self::TrackLocation => true,
        };
    }
}
