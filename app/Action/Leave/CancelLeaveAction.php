<?php

namespace App\Action\Leave;

use App\Action\Leave\Context\CancelLeaveContext;
use App\Action\Leave\Steps\EnsureOwnLeaveRequest;
use App\Action\Leave\Steps\PerformCancelLeave;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Pipeline\Pipeline;

/**
 * One API = one Action. Runs ownership check then cancellation.
 */
class CancelLeaveAction
{
    protected array $pipes = [
        EnsureOwnLeaveRequest::class,
        PerformCancelLeave::class,
    ];

    public function execute(Employee $employee, LeaveRequest $leaveRequest): void
    {
        $context = new CancelLeaveContext($employee, $leaveRequest);

        app(Pipeline::class)
            ->send($context)
            ->through($this->pipes)
            ->thenReturn();
    }
}
