<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'notes'      => $this->notes,
            'total_days' => $this->total_days,
            'status'     => $this->status?->value,
            'started_at' => optional($this->started_at)->toDateString(),
            'ended_at'   => optional($this->ended_at)->toDateString(),
            'leave_type' => $this->whenLoaded('leaveType', fn () => $this->leaveType?->name),
            'employee'   => $this->whenLoaded('employee', fn () => [
                'id'        => $this->employee?->id,
                'id_no'     => $this->employee?->id_no,
                'full_name' => $this->employee?->full_name,
            ]),
            'approvals'  => $this->whenLoaded('approvals', fn () => $this->approvals->map(fn ($a) => [
                'status'   => $a->status?->value,
                'remarks'  => $a->remarks,
                'acted_at' => optional($a->acted_at)->toIso8601String(),
                'approver' => $a->approver?->full_name,
            ])),
        ];
    }
}
