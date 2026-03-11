<?php

namespace App\Enums;

enum StepConditionType: string
{
    case Always          = 'always';
    case DaysGreaterThan = 'days_greater_than';
    case DaysLessThan    = 'days_less_than';
}
