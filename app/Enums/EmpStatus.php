<?php

namespace App\Enums;

enum EmpStatus: string
{
    case Probation = 'probation';
    case Confirmed = 'confirmed';
    case Resigned  = 'resigned';

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn(self $case) => ['value' => $case->value, 'label' => $case->name])
            ->toArray();
    }
}
