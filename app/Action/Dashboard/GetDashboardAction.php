<?php

namespace App\Action\Dashboard;

use App\Models\Employee;
use App\Services\AttendanceService;

/**
 * Read: the mobile home dashboard — office hours, month-to-date stats, and
 * today's attendance. Delegates to the shared AttendanceService aggregations.
 */
class GetDashboardAction
{
    public function __construct(private readonly AttendanceService $service) {}

    public function execute(Employee $employee): array
    {
        return [
            'officeHours'  => $this->service->getOfficeHours($employee),
            'monthlyStats' => $this->service->getMonthlyStats($employee),
            'todayData'    => $this->service->getTodayCompleteData($employee),
        ];
    }
}
