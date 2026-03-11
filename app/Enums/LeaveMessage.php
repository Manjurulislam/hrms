<?php

namespace App\Enums;

enum LeaveMessage: string
{
    // Approval messages
    case Approved          = 'Leave request approved.';
    case Rejected          = 'Leave request rejected.';
    case ForwardedTo       = 'Approved and forwarded to :name.';
    case ApprovalNotFound  = 'Approval record not found.';

    // Request messages
    case Created           = 'Leave request submitted successfully.';
    case Cancelled         = 'Leave request cancelled successfully.';
    case CannotCancel      = 'This leave request cannot be cancelled.';
    case AlreadyReviewed   = 'This leave request has already been reviewed by an approver and cannot be cancelled.';
    case InsufficientBalance = 'Insufficient leave balance. You have :remaining day(s) remaining.';
    case OverlappingDates  = 'You already have a leave request that overlaps with the selected dates.';

    public function with(array $replacements): string
    {
        $message = $this->value;

        foreach ($replacements as $key => $value) {
            $message = str_replace(":{$key}", $value, $message);
        }

        return $message;
    }
}
