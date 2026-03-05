<?php

namespace App\Enums;

enum BloodGroup: string
{
    case APositive  = 'A+';
    case ANegative  = 'A-';
    case BPositive  = 'B+';
    case BNegative  = 'B-';
    case ABPositive = 'AB+';
    case ABNegative = 'AB-';
    case OPositive  = 'O+';
    case ONegative  = 'O-';

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn(self $case) => ['value' => $case->value, 'label' => $case->value])
            ->toArray();
    }
}
