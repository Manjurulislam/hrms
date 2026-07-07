<?php

namespace App\Action\Auth;

use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Resources\Api\EmployeeResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class LoginAction
{
    /**
     * Credentials are already verified by the request (ApiLoginRequest); here we
     * enforce the mobile-only employee rule and issue a device token.
     */
    public function execute(ApiLoginRequest $request): array
    {
        $user = Auth::user();

        if (! $user->employee) {
            throw new AuthorizationException('No employee profile linked to this account.');
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        $user->employee->load(['company', 'department', 'designation']);

        return [
            'token'    => $token,
            'employee' => new EmployeeResource($user->employee),
        ];
    }
}
