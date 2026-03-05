<?php

namespace App\Enums;

enum SessionType: string
{
    case Regular     = 'regular';
    case Overtime    = 'overtime';
    case BreakReturn = 'break_return';
}
