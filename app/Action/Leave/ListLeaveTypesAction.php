<?php

namespace App\Action\Leave;

use App\Http\Resources\Api\LeaveTypeResource;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read: active leave types for the employee's company.
 */
class ListLeaveTypesAction
{
    public function execute(Employee $employee): AnonymousResourceCollection
    {
        $types = LeaveType::activeForCompany($employee->company_id)
            ->get(['id', 'name', 'max_per_year']);

        return LeaveTypeResource::collection($types);
    }
}
