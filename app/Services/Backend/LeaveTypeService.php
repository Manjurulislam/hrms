<?php

namespace App\Services\Backend;

use App\Models\LeaveType;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class LeaveTypeService
{
    use PaginateQuery, QueryParams;

    public function __construct(
        protected readonly SharedService $shared
    ) {}

    public function list(Request $request): array
    {
        $query = LeaveType::query()
            ->with('company:id,name')
            ->orderBy('name');

        $query = $this->leaveTypeQuery($query, $request);

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): LeaveType
    {
        return LeaveType::create($data);
    }

    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);

        return $leaveType;
    }

    public function delete(LeaveType $leaveType): bool
    {
        return $leaveType->delete();
    }

    public function toggle(LeaveType $leaveType): bool
    {
        $leaveType->update(['status' => !$leaveType->status]);

        return $leaveType->status;
    }

    public function formData(?LeaveType $leaveType = null): array
    {
        $data = [
            'companies' => $this->shared->companies(),
        ];

        if ($leaveType) {
            $leaveType->load('company:id,name');
            $data['item'] = $leaveType;
        }

        return $data;
    }
}
