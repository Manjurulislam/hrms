<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Active     = 'active';
    case Completed  = 'completed';
    case AutoClosed = 'auto_closed';
}
