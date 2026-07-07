<?php

namespace App\Action\Leave\Context;

use App\Models\Employee;
use App\Models\LeaveRequest;

/**
 * Typed context for the apply-leave pipeline.
 */
class ApplyLeaveContext
{
    public ?LeaveRequest $leaveRequest = null;

    public function __construct(
        public readonly Employee $employee,
        public readonly array $data,
    ) {}
}
