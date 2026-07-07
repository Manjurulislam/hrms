<?php

namespace App\Action\Auth;

use App\Models\User;

class LogoutAction
{
    /**
     * Revoke the token used for the current request only, leaving other
     * devices' sessions intact.
     */
    public function execute(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
