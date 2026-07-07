<?php

namespace App\Action\Leave\Steps;

use App\Action\Leave\Context\ApprovalActionContext;
use App\Services\Backend\LeaveApprovalService;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * One business rule: advance the approval workflow via LeaveApprovalService.
 */
class PerformApprove
{
    public function __construct(private readonly LeaveApprovalService $service) {}

    public function handle(ApprovalActionContext $context, Closure $next): mixed
    {
        $result = $this->service->approve($context->leaveRequest, $context->approver, $context->remarks);

        if (! ($result['success'] ?? false)) {
            throw new UnprocessableEntityHttpException($result['message'] ?? 'Failed to approve leave request.');
        }

        $context->message = $result['message'] ?? 'Leave approved.';

        return $next($context);
    }
}
