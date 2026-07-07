<?php

namespace App\Action\Leave\Context;

use App\Models\Employee;
use App\Models\LeaveRequest;

/**
 * Shared context for the approve and reject pipelines.
 */
class ApprovalActionContext
{
    public ?string $message = null;

    public function __construct(
        public readonly LeaveRequest $leaveRequest,
        public readonly ?Employee $approver,
        public readonly ?string $remarks,
    ) {}
}
