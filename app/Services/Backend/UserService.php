<?php

namespace App\Services\Backend;

use App\Models\Role;
use App\Models\User;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserService
{
    use PaginateQuery, QueryParams;

    private const EXCLUDED_ROLE_SLUGS = ['super_admin', 'employee'];

    public function list(Request $request): array
    {
        $query = User::query()
            ->with('roles')
            ->whereHas('roles', fn($q) => $q->whereNotIn('slug', self::EXCLUDED_ROLE_SLUGS))
            ->orderBy('name');

        $query = $this->userQuery($query, $request);

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = data_get($data, 'role');
            $user = User::create(collect($data)->except('role')->toArray());
            $user->roles()->sync($role);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $role = data_get($data, 'role');
            $userData = collect($data)->except('role', 'password')->toArray();
            $password = data_get($data, 'password');

            if (filled($password)) {
                $userData['password'] = $password;
            }

            $user->update($userData);
            $user->roles()->sync($role);

            return $user;
        });
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function toggle(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user->update(['status' => !$user->status]);
            $user->employee?->update(['status' => $user->status]);

            return $user->status;
        });
    }

    public function formData(?User $user = null): array
    {
        $data = [
            'roles' => $this->getAssignableRoles(),
        ];

        if ($user) {
            $data['item']         = $user;
            $data['selectedRole'] = $user->roles()->pluck('role_id')->toArray();
        }

        return $data;
    }

    private function getAssignableRoles()
    {
        return Role::select('id', 'name')
            ->whereNotIn('slug', self::EXCLUDED_ROLE_SLUGS)
            ->where('status', true)
            ->get();
    }
}
