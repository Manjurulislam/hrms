<?php

namespace App\Action\Attendance\Context;

use App\Models\AttendanceSession;
use App\Models\Employee;

/**
 * Typed context passed through the check-in pipeline. Steps read the immutable
 * inputs and enrich it with the created session / result message.
 */
class CheckInContext
{
    public ?AttendanceSession $session = null;

    public ?string $message = null;

    public function __construct(
        public readonly Employee $employee,
        public readonly string $ip,
        public readonly array $data,
    ) {}
}
