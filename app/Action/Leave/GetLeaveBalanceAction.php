<?php

namespace App\Action\Leave;

use App\Models\Employee;
use App\Services\Backend\LeaveRequestService;

/**
 * Read: leave balances for a year. Delegates to LeaveRequestService::getBalances,
 * which seeds missing balance rows from each type's yearly allowance.
 */
class GetLeaveBalanceAction
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function execute(Employee $employee, int $year): array
    {
        return $this->service->getBalances($employee, $year);
    }
}
