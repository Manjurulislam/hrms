<?php

namespace App\Action\Attendance\Steps;

use App\Action\Attendance\Context\CheckInContext;
use App\Services\AttendanceService;
use Closure;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * One business rule: perform the check-in via the shared AttendanceService
 * (keeping schedule/summary side-effects identical to the web flow). A service
 * refusal (duplicate session, on-leave, …) stops the pipeline as a 409.
 */
class PerformCheckIn
{
    public function __construct(private readonly AttendanceService $service) {}

    public function handle(CheckInContext $context, Closure $next): mixed
    {
        $result = $this->service->checkIn($context->employee, $context->ip, $context->data);

        if (! ($result['success'] ?? false)) {
            throw new ConflictHttpException($result['message'] ?? 'Check-in failed.');
        }

        $context->session = $result['session'] ?? null;
        $context->message = $result['message'] ?? null;

        return $next($context);
    }
}
