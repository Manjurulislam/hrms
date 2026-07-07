<?php

namespace App\Action\Leave;

use App\Http\Resources\Api\LeaveResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read: the authenticated employee's own leave requests (scoped, paginated).
 */
class ListLeaveAction
{
    public function execute(Employee $employee, int $perPage = 20): AnonymousResourceCollection
    {
        $requests = LeaveRequest::forEmployee($employee->id)
            ->with('leaveType:id,name')
            ->paginate($perPage);

        return LeaveResource::collection($requests);
    }
}
