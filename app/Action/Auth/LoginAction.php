<?php

namespace App\Action\Auth;

use App\Action\Auth\Context\LoginContext;
use App\Action\Auth\Steps\EnsureEmployeeLinked;
use App\Action\Auth\Steps\IssueDeviceToken;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Resources\Api\EmployeeResource;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;

/**
 * One API = one Action. Credentials are already verified by ApiLoginRequest;
 * this runs the post-auth pipeline (employee check → token) and returns the
 * token plus employee profile.
 */
class LoginAction
{
    protected array $pipes = [
        EnsureEmployeeLinked::class,
        IssueDeviceToken::class,
    ];

    public function execute(ApiLoginRequest $request): array
    {
        $context = new LoginContext(Auth::user(), $request->device_name);

        $result = app(Pipeline::class)
            ->send($context)
            ->through($this->pipes)
            ->thenReturn();

        $result->user->employee->load(['company', 'department', 'designation']);

        return [
            'token'    => $result->token,
            'employee' => new EmployeeResource($result->user->employee),
        ];
    }
}
