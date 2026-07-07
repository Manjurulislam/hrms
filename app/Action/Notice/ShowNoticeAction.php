<?php

namespace App\Action\Notice;

use App\Models\Employee;
use App\Models\Notice;

/**
 * Read: a single notice, but only if it is visible to the employee.
 * Returns null when it is not (controller maps that to 404).
 */
class ShowNoticeAction
{
    public function execute(Employee $employee, int $noticeId): ?Notice
    {
        return Notice::visibleTo($employee)
            ->with('department:id,name')
            ->find($noticeId);
    }
}
