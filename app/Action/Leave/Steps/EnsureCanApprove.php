<?php

namespace App\Action\Leave\Steps;

use App\Action\Leave\Context\ApprovalActionContext;
use App\Traits\ResolvesApprover;
use Closure;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * One business rule: only the current approver (or a super admin) may act, and
 * never on their own request. Reuses the shared ResolvesApprover authorization.
 */
class EnsureCanApprove
{
    use ResolvesApprover;

    public function handle(ApprovalActionContext $context, Closure $next): mixed
    {
        if (! $this->canActOnLeaveRequest($context->leaveRequest)) {
            throw new AccessDeniedHttpException('You are not authorized to act on this request.');
        }

        return $next($context);
    }
}
