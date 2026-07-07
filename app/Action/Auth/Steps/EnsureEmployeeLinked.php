<?php

namespace App\Action\Auth\Steps;

use App\Action\Auth\Context\LoginContext;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * One business rule: the mobile app is employee-only, so the account must have
 * a linked employee profile. Mapped to a 403.
 */
class EnsureEmployeeLinked
{
    public function handle(LoginContext $context, Closure $next): mixed
    {
        if (! $context->user->employee) {
            throw new AuthorizationException('No employee profile linked to this account.');
        }

        return $next($context);
    }
}
