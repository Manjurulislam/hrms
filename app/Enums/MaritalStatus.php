<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single   = 'single';
    case Married  = 'married';
    case Divorced = 'divorced';
    case Widowed  = 'widowed';

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn(self $case) => ['value' => $case->value, 'label' => $case->name])
            ->toArray();
    }
}
