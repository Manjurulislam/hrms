<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
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
        ];
    }
}
