<?php

namespace App\Services\Backend;

use App\Models\Role;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class RoleService
{
    use PaginateQuery, QueryParams;

    public function list(Request $request): array
    {
        $query = Role::query()->orderBy('name');
        $query = $this->roleQuery($query, $request);

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role;
    }

    public function delete(Role $role): bool
    {
        return $role->delete();
    }

    public function toggle(Role $role): bool
    {
        $role->update(['status' => !$role->status]);

        return $role->status;
    }

    public function formData(?Role $role = null): array
    {
        $data = [];

        if ($role) {
            $data['item'] = $role;
        }

        return $data;
    }
}
