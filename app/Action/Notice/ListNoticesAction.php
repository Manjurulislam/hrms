<?php

namespace App\Action\Notice;

use App\Http\Resources\Api\NoticeResource;
use App\Models\Employee;
use App\Models\Notice;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read: notices visible to the employee (newest first, paginated).
 */
class ListNoticesAction
{
    public function execute(Employee $employee, int $perPage = 10): AnonymousResourceCollection
    {
        $notices = Notice::visibleTo($employee)
            ->with('department:id,name')
            ->orderByDesc('published_at')
            ->paginate($perPage);

        return NoticeResource::collection($notices);
    }
}
