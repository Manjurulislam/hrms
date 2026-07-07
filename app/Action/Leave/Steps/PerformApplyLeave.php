<?php

namespace App\Action\Leave\Steps;

use App\Action\Leave\Context\ApplyLeaveContext;
use App\Models\LeaveRequest;
use App\Services\Backend\LeaveRequestService;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * One business rule: submit the leave request via LeaveRequestService (which
 * initializes the approval workflow and notifications). A business refusal is
 * returned as a string message and stops the pipeline as a 422.
 */
class PerformApplyLeave
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function handle(ApplyLeaveContext $context, Closure $next): mixed
    {
        $result = $this->service->store($context->employee, $context->data);

        if (! $result instanceof LeaveRequest) {
            throw new UnprocessableEntityHttpException((string) $result);
        }

        $context->leaveRequest = $result;

        return $next($context);
    }
}
