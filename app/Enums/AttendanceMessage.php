<?php

namespace App\Enums;

enum AttendanceMessage: string
{
    // Success messages
    case CheckInSuccess    = 'Checked in.';
    case CheckOutSuccess   = 'Checked out.';
    case BreakStartSuccess = 'Break started.';
    case BreakEndSuccess   = 'Break ended.';

    // Validation messages
    case HolidayRestricted    = 'Holiday (:name). Check-in not allowed.';
    case LeaveRestricted      = 'On leave (:name). Check-in not allowed.';
    case ActiveSessionExists  = 'Already checked in. Check out first.';
    case ActiveBreakExists    = 'End your break first.';
    case NoActiveSession      = 'Not checked in yet.';
    case NoActiveSessionBreak = 'Check in before taking a break.';
    case AlreadyOnBreak       = 'Already on a break.';
    case NoActiveBreak        = 'No active break.';

    // Failure messages
    case CheckInFailed    = 'Check-in failed. Try again.';
    case CheckOutFailed   = 'Check-out failed. Try again.';
    case BreakStartFailed = 'Could not start break.';
    case BreakEndFailed   = 'Could not end break.';

    // Replace :name placeholder with actual value
    public function with(string $name): string
    {
        return str_replace(':name', $name, $this->value);
    }
}
