<?php

namespace App\Services\Backend;

use App\Models\Department;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class DepartmentService
{
    use PaginateQuery, QueryParams;

    public function __construct(
        protected readonly SharedService $shared
    ) {}

    public function list(Request $request): array
    {
        $query = Department::query()
            ->with('company:id,name')
            ->orderBy('name');

        $query = $this->departmentQuery($query, $request);

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update($data);

        return $department;
    }

    public function delete(Department $department): bool
    {
        return $department->delete();
    }

    public function toggle(Department $department): bool
    {
        $department->update(['status' => !$department->status]);

        return $department->status;
    }

    public function formData(?Department $department = null): array
    {
        $data = [
            'companies' => $this->shared->companies(),
        ];

        if ($department) {
            $department->load('company:id,name');
            $data['item'] = $department;
        }

        return $data;
    }
}
