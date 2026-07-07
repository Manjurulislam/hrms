<?php

namespace App\Action\Leave;

use App\Http\Resources\Api\LeaveApprovalResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read: leave requests awaiting action from this approver.
 */
class ListApprovalsAction
{
    public function execute(Employee $approver): AnonymousResourceCollection
    {
        $items = LeaveRequest::awaitingApprover($approver->id)
            ->with(['employee:id,first_name,last_name,id_no', 'leaveType:id,name'])
            ->get();

        return LeaveApprovalResource::collection($items);
    }
}
