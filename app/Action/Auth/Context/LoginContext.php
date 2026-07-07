<?php

namespace App\Action\Auth\Context;

use App\Models\User;

/**
 * Typed context for the login pipeline (post credential-verification).
 */
class LoginContext
{
    public ?string $token = null;

    public function __construct(
        public readonly User $user,
        public readonly string $deviceName,
    ) {}
}
