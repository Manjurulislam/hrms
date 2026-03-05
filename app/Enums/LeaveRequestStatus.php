<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case Pending   = 'pending';
    case InReview  = 'in_review';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Cancelled = 'cancelled';
}
