<?php

namespace App\Enums;

enum DesignationLevel: int
{
    case TopExecutive     = 1;
    case SeniorManagement = 2;
    case MiddleManagement = 3;
    case TeamLead         = 4;
    case SeniorStaff      = 5;
    case MidLevelStaff    = 6;
    case JuniorStaff      = 7;
    case EntryLevel       = 8;

    public function label(): string
    {
        return match ($this) {
            self::TopExecutive     => 'Top Executive (CEO, MD)',
            self::SeniorManagement => 'Senior Management (CTO, CFO, VP)',
            self::MiddleManagement => 'Middle Management (Manager, HOD)',
            self::TeamLead         => 'Team Lead / Supervisor',
            self::SeniorStaff      => 'Senior Staff',
            self::MidLevelStaff    => 'Mid-Level Staff',
            self::JuniorStaff      => 'Junior Staff',
            self::EntryLevel       => 'Entry Level / Intern',
        };
    }

    public static function options(): array
    {
        return array_map(fn(self $case) => [
            'value' => $case->value,
            'title' => "Level {$case->value} - {$case->label()}",
        ], self::cases());
    }
}
