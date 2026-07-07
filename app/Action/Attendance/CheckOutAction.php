<?php

namespace App\Action\Attendance;

use App\Action\Attendance\Context\CheckOutContext;
use App\Action\Attendance\Steps\PerformCheckOut;
use App\Http\Requests\Api\ApiCheckOutRequest;

use Illuminate\Pipeline\Pipeline;

/**
 * One API = one Action. Builds the context and runs the check-out pipeline.
 */
class CheckOutAction
{
    protected array $pipes = [
        PerformCheckOut::class,
    ];

    public function execute(ApiCheckOutRequest $request): CheckOutContext
    {
        $context = new CheckOutContext(
            $request->user()->employee,
            $request->resolvedClientIp(),
            $request->getSanitizedData(),
        );

        return app(Pipeline::class)
            ->send($context)
            ->through($this->pipes)
            ->thenReturn();
    }
}
