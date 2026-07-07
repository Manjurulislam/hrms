<?php

namespace App\Action\Leave\Steps;

use App\Action\Leave\Context\CancelLeaveContext;
use App\Services\Backend\LeaveRequestService;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * One business rule: cancel via LeaveRequestService (enforces the cancel window /
 * prior-review checks and notifies). A refusal is a string message → 422.
 */
class PerformCancelLeave
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function handle(CancelLeaveContext $context, Closure $next): mixed
    {
        $result = $this->service->cancel($context->leaveRequest);

        if ($result !== true) {
            throw new UnprocessableEntityHttpException((string) $result);
        }

        return $next($context);
    }
}
