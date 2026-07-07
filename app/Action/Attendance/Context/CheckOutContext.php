<?php

namespace App\Action\Attendance\Context;

use App\Models\AttendanceSession;
use App\Models\Employee;

/**
 * Typed context passed through the check-out pipeline.
 */
class CheckOutContext
{
    public ?AttendanceSession $session = null;

    public ?string $message = null;

    public ?string $duration = null;

    public function __construct(
        public readonly Employee $employee,
        public readonly string $ip,
        public readonly array $data,
    ) {}
}
