<?php

namespace App\Services\Backend;

use App\Enums\BloodGroup;
use App\Enums\DesignationLevel;
use App\Enums\EmpStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    use PaginateQuery, QueryParams;

    public function __construct(
        protected readonly SharedService $shared
    ) {}

    // ──────────────────────────────────────────────
    // CRUD
    // ──────────────────────────────────────────────

    public function list(Request $request): array
    {
        $query = Employee::query()
            ->with(['department:id,name', 'designation:id,title', 'manager:id,first_name,last_name', 'user:id,employee_id,email,status', 'media'])
            ->orderBy('first_name')
            ->orderBy('last_name');

        $query = $this->employeeQuery($query, $request);

        return $this->transformEmployees($query, $request->integer('per_page', 10));
    }

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $password = data_get($data, 'password');
            $employee = Employee::create(collect($data)->except('password')->toArray());

            if (filled($password)) {
                $this->createUser($employee, $password);
            }

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $password = data_get($data, 'password');
            $roleIds  = data_get($data, 'roles', []);

            $employee->update(collect($data)->except(['password', 'roles'])->toArray());

            if (filled($password)) {
                $this->updateUserPassword($employee, $password);
            }

            $this->syncUser($employee);
            $this->syncUserRoles($employee, $roleIds);

            return $employee;
        });
    }

    public function delete(Employee $employee): bool
    {
        return DB::transaction(function () use ($employee) {
            $employee->user?->delete();

            return $employee->delete();
        });
    }

    public function toggle(Employee $employee): bool
    {
        return DB::transaction(function () use ($employee) {
            $employee->update(['status' => !$employee->status]);
            $employee->user?->update(['status' => $employee->status]);

            return $employee->status;
        });
    }

    // ──────────────────────────────────────────────
    // Form Data
    // ──────────────────────────────────────────────

    public function formData(?Employee $employee = null): array
    {
        $data = [
            'companies'            => $this->shared->companies(),
            'departments'          => $this->shared->departments(),
            'designations'         => $this->shared->designations(
                excludeLevels: [DesignationLevel::TopExecutive->value],
            ),
            'employees'            => $this->shared->employees($employee?->id),
            'genderOptions'        => Gender::toOptions(),
            'bloodGroupOptions'    => BloodGroup::toOptions(),
            'maritalStatusOptions' => MaritalStatus::toOptions(),
            'empStatusOptions'     => EmpStatus::toOptions(),
            'roles'                => $this->getAssignableRoles(),
        ];

        if ($employee) {
            $employee->load(['department:id,name,company_id', 'designation:id,title', 'manager:id,first_name,last_name', 'user:id,employee_id,email,status']);
            $data['item']          = $employee;
            $data['selectedRoles'] = $this->getSelectedRoleIds($employee);
        }

        return $data;
    }

    // ──────────────────────────────────────────────
    // User Account
    // ──────────────────────────────────────────────

    protected function createUser(Employee $employee, string $password): void
    {
        $user = $employee->user()->create([
            'name'     => $employee->full_name,
            'email'    => $employee->email,
            'password' => $password,
            'status'   => $employee->status,
        ]);

        $this->assignEmployeeRole($user);
    }

    protected function syncUser(Employee $employee): void
    {
        $employee->user?->update([
            'name'   => $employee->full_name,
            'email'  => $employee->email,
            'status' => $employee->status,
        ]);
    }

    protected function updateUserPassword(Employee $employee, string $password): void
    {
        $employee->user?->update(['password' => $password]);
    }

    protected function assignEmployeeRole($user): void
    {
        $employeeRole = Role::where('slug', 'employee')->first();

        if ($employeeRole) {
            $user->roles()->attach($employeeRole);
        }
    }

    // Assignable roles on the Employee edit page — excludes super_admin (system) and employee (auto-assigned).
    protected function getAssignableRoles()
    {
        return Role::select('id', 'name', 'slug')
            ->whereNotIn('slug', ['super_admin', 'employee'])
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    protected function getSelectedRoleIds(Employee $employee): array
    {
        if (!$employee->user) {
            return [];
        }

        return $employee->user->roles()
            ->whereNotIn('slug', ['super_admin', 'employee'])
            ->pluck('roles.id')
            ->toArray();
    }

    // Sync extra roles on the linked user while always preserving the base employee role
    // and never allowing super_admin to be granted via this path.
    protected function syncUserRoles(Employee $employee, array $roleIds): void
    {
        if (!$employee->user) {
            return;
        }

        $employeeRoleId   = Role::where('slug', 'employee')->value('id');
        $superAdminRoleId = Role::where('slug', 'super_admin')->value('id');

        $finalIds = collect($roleIds)
            ->reject(fn($id) => (int) $id === (int) $superAdminRoleId)
            ->when($employeeRoleId, fn($c) => $c->push($employeeRoleId))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $employee->user->roles()->sync($finalIds);
    }
}
