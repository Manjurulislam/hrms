<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'id_no'       => $this->id_no,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'company'     => $this->whenLoaded('company', fn () => $this->company?->name),
            'department'  => $this->whenLoaded('department', fn () => $this->department?->name),
            'designation' => $this->whenLoaded('designation', fn () => $this->designation?->title),
        ];
    }
}
