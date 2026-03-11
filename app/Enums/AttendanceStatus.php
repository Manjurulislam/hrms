<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present      = 'present';
    case Absent       = 'absent';
    case HalfDay      = 'half_day';
    case Late         = 'late';
    case Holiday      = 'holiday';
    case Weekend      = 'weekend';
    case Leave        = 'leave';
    case WorkFromHome = 'work_from_home';

    public function label(): string
    {
        return match ($this) {
            self::Present      => 'Present',
            self::Absent       => 'Absent',
            self::HalfDay      => 'Half Day',
            self::Late         => 'Late',
            self::Holiday      => 'Holiday',
            self::Weekend      => 'Weekend',
            self::Leave        => 'Leave',
            self::WorkFromHome => 'WFH',
        };
    }

    public static function labelFor(mixed $status): string
    {
        if ($status instanceof self) {
            return $status->label();
        }

        $enum = self::tryFrom($status);

        return $enum?->label() ?? (string) $status;
    }
}
