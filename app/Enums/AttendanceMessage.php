<?php

namespace App\Enums;

enum AttendanceMessage: string
{
    // Success messages
    case CheckInSuccess    = 'Checked in successfully.';
    case CheckOutSuccess   = 'Checked out successfully.';
    case BreakStartSuccess = 'Break started successfully.';
    case BreakEndSuccess   = 'Break ended successfully.';

    // Validation messages
    case HolidayRestricted    = 'Today is a holiday (:name). Attendance is not allowed.';
    case LeaveRestricted      = 'You are on approved leave (:name). Attendance is not allowed.';
    case ActiveSessionExists  = 'You already have an active session. Please check out first.';
    case ActiveBreakExists    = 'You have an active break. Please end your break first.';
    case NoActiveSession      = 'No active check-in found for today.';
    case NoActiveSessionBreak = 'No active session found. Please check in first.';
    case AlreadyOnBreak       = 'You already have an active break.';
    case NoActiveBreak        = 'No active break found.';

    // Failure messages
    case CheckInFailed    = 'Failed to check in.';
    case CheckOutFailed   = 'Failed to check out.';
    case BreakStartFailed = 'Failed to start break.';
    case BreakEndFailed   = 'Failed to end break.';

    // Replace :name placeholder with actual value
    public function with(string $name): string
    {
        return str_replace(':name', $name, $this->value);
    }
}
