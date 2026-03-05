<?php

namespace App\Services\Backend;

use App\Models\Designation;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class DesignationService
{
    use PaginateQuery, QueryParams;

    public function __construct(
        protected readonly SharedService $shared
    ) {}

    public function list(Request $request): array
    {
        $query = Designation::query()
            ->with(['company:id,name', 'parent:id,title'])
            ->orderBy('level')
            ->orderBy('title');

        $query = $this->designationQuery($query, $request);

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): Designation
    {
        return Designation::create($data);
    }

    public function update(Designation $designation, array $data): Designation
    {
        $designation->update($data);

        return $designation;
    }

    public function delete(Designation $designation): bool
    {
        return $designation->delete();
    }

    public function toggle(Designation $designation): bool
    {
        $designation->update(['status' => !$designation->status]);

        return $designation->status;
    }

    public function formData(?Designation $designation = null): array
    {
        $data = [
            'companies'          => $this->shared->companies(),
            'parentDesignations' => $this->shared->designations($designation?->id),
        ];

        if ($designation) {
            $designation->load(['company:id,name', 'parent:id,title']);
            $data['item'] = $designation;
        }

        return $data;
    }
}
