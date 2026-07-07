<?php

namespace App\Action\Leave\Context;

use App\Models\Employee;
use App\Models\LeaveRequest;

/**
 * Typed context for the cancel-leave pipeline.
 */
class CancelLeaveContext
{
    public function __construct(
        public readonly Employee $employee,
        public readonly LeaveRequest $leaveRequest,
    ) {}
}
