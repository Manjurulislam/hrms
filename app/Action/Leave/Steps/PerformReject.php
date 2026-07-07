<?php

namespace App\Action\Leave\Steps;

use App\Action\Leave\Context\ApprovalActionContext;
use App\Services\Backend\LeaveApprovalService;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * One business rule: reject the leave request via LeaveApprovalService.
 */
class PerformReject
{
    public function __construct(private readonly LeaveApprovalService $service) {}

    public function handle(ApprovalActionContext $context, Closure $next): mixed
    {
        $result = $this->service->reject($context->leaveRequest, $context->approver, $context->remarks);

        if (! ($result['success'] ?? false)) {
            throw new UnprocessableEntityHttpException($result['message'] ?? 'Failed to reject leave request.');
        }

        $context->message = $result['message'] ?? 'Leave rejected.';

        return $next($context);
    }
}
