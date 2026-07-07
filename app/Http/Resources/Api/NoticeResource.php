<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'published_at' => optional($this->published_at)->toDateString(),
            'expired_at'   => optional($this->expired_at)->toDateString(),
            'department'   => $this->whenLoaded('department', fn () => $this->department?->name),
        ];
    }
}
