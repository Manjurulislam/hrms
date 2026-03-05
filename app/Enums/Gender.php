<?php

namespace App\Enums;

enum Gender: string
{
    case Male   = 'male';
    case Female = 'female';
    case Other  = 'other';

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn(self $case) => ['value' => $case->value, 'label' => $case->name])
            ->toArray();
    }
}
