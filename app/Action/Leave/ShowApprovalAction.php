<?php

namespace App\Action\Leave;

use App\Http\Resources\Api\LeaveApprovalResource;
use App\Models\LeaveRequest;

/**
 * Read: a single approval with its employee, type and approval timeline.
 */
class ShowApprovalAction
{
    public function execute(LeaveRequest $leaveRequest): LeaveApprovalResource
    {
        $leaveRequest->load([
            'employee:id,first_name,last_name,id_no',
            'leaveType:id,name',
            'approvals' => fn ($q) => $q->orderBy('created_at'),
            'approvals.approver:id,first_name,last_name',
        ]);

        return new LeaveApprovalResource($leaveRequest);
    }
}
