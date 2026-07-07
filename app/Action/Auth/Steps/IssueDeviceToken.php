<?php

namespace App\Action\Auth\Steps;

use App\Action\Auth\Context\LoginContext;
use Closure;

/**
 * One business rule: issue a Sanctum personal access token for the device.
 */
class IssueDeviceToken
{
    public function handle(LoginContext $context, Closure $next): mixed
    {
        $context->token = $context->user->createToken($context->deviceName)->plainTextToken;

        return $next($context);
    }
}
