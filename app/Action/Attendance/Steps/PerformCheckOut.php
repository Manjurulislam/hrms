<?php

namespace App\Action\Attendance\Steps;

use App\Action\Attendance\Context\CheckOutContext;
use App\Services\AttendanceService;
use Closure;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * One business rule: perform the check-out via the shared AttendanceService.
 * No active session (or any refusal) stops the pipeline as a 409.
 */
class PerformCheckOut
{
    public function __construct(private readonly AttendanceService $service) {}

    public function handle(CheckOutContext $context, Closure $next): mixed
    {
        $result = $this->service->checkOut($context->employee, $context->ip, $context->data);

        if (! ($result['success'] ?? false)) {
            throw new ConflictHttpException($result['message'] ?? 'Check-out failed.');
        }

        $context->session  = $result['session'] ?? null;
        $context->message  = $result['message'] ?? null;
        $context->duration = $result['duration'] ?? null;

        return $next($context);
    }
}
