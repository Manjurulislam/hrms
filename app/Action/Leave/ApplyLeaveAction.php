<?php

namespace App\Action\Leave;

use App\Action\Leave\Context\ApplyLeaveContext;
use App\Action\Leave\Steps\PerformApplyLeave;
use App\Http\Requests\Api\ApiLeaveStoreRequest;
use App\Models\LeaveRequest;
use Illuminate\Pipeline\Pipeline;

/**
 * One API = one Action. Builds the context and runs the apply-leave pipeline.
 */
class ApplyLeaveAction
{
    protected array $pipes = [
        PerformApplyLeave::class,
    ];

    public function execute(ApiLeaveStoreRequest $request): LeaveRequest
    {
        $context = new ApplyLeaveContext(
            $request->user()->employee,
            $request->validated(),
        );

        return app(Pipeline::class)
            ->send($context)
            ->through($this->pipes)
            ->thenReturn()
            ->leaveRequest;
    }
}
