<?php

namespace App\Action\Leave\Steps;

use App\Action\Leave\Context\CancelLeaveContext;
use Closure;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * One business rule: an employee may only cancel their own leave request.
 */
class EnsureOwnLeaveRequest
{
    public function handle(CancelLeaveContext $context, Closure $next): mixed
    {
        if ($context->leaveRequest->employee_id !== $context->employee->id) {
            throw new AccessDeniedHttpException('You cannot cancel this request.');
        }

        return $next($context);
    }
}
