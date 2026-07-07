<?php

namespace App\Action\Auth;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class GetProfileAction
{
    /**
     * Return the authenticated user's employee profile with the relations the
     * mobile profile screen needs. No linked employee → 403.
     */
    public function execute(User $user): Employee
    {
        $employee = $user->employee;

        if (! $employee) {
            throw new AuthorizationException('No employee profile linked to this account.');
        }

        $employee->load(['company', 'department', 'designation']);

        return $employee;
    }
}
