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
}
