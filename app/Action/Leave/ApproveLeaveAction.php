<?php

namespace App\Action\Leave;

use App\Action\Leave\Context\ApprovalActionContext;
use App\Action\Leave\Steps\EnsureCanApprove;
use App\Action\Leave\Steps\PerformApprove;
use App\Models\LeaveRequest;
use App\Traits\ResolvesApprover;
use Illuminate\Pipeline\Pipeline;

/**
 * One API = one Action. Authorizes then advances the approval workflow.
 */
class ApproveLeaveAction
{
    use ResolvesApprover;

    protected array $pipes = [
        EnsureCanApprove::class,
        PerformApprove::class,
    ];

    public function execute(LeaveRequest $leaveRequest, ?string $remarks): string
    {
        $context = new ApprovalActionContext(
            $leaveRequest,
            $this->getApproverEmployee($leaveRequest),
            $remarks,
        );

        return app(Pipeline::class)
            ->send($context)
            ->through($this->pipes)
            ->thenReturn()
            ->message;
    }
}
