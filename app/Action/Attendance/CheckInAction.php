<?php

namespace App\Action\Attendance;

use App\Action\Attendance\Context\CheckInContext;
use App\Action\Attendance\Steps\PerformCheckIn;
use App\Http\Requests\Api\ApiCheckInRequest;
use App\Models\AttendanceSession;
use Illuminate\Pipeline\Pipeline;

/**
 * One API = one Action. Builds the context and runs the check-in pipeline;
 * no business logic lives here.
 */
class CheckInAction
{
    protected array $pipes = [
        PerformCheckIn::class,
    ];

    public function execute(ApiCheckInRequest $request): AttendanceSession
    {
        $context = new CheckInContext(
            $request->user()->employee,
            $request->resolvedClientIp(),
            $request->getSanitizedData(),
        );

        return app(Pipeline::class)
            ->send($context)
            ->through($this->pipes)
            ->thenReturn()
            ->session;
    }
}
