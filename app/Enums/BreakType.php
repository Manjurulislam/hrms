<?php

namespace App\Enums;

enum BreakType: string
{
    case Lunch    = 'lunch';
    case Tea      = 'tea';
    case Personal = 'personal';
    case Prayer   = 'prayer';
    case Other    = 'other';
}
