<?php

namespace App\Services\Backend;

use App\Enums\BloodGroup;
use App\Enums\EmpStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Employee;
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
            $employee->update($data);
            $this->syncUser($employee);

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
        $employee->update(['status' => !$employee->status]);
        $employee->user?->update(['status' => $employee->status]);

        return $employee->status;
    }

    // ──────────────────────────────────────────────
    // Form Data
    // ──────────────────────────────────────────────

    public function formData(?Employee $employee = null): array
    {
        $data = [
            'companies'            => $this->shared->companies(),
            'departments'          => $this->shared->departments(),
            'designations'         => $this->shared->designations(),
            'employees'            => $this->shared->employees($employee?->id),
            'genderOptions'        => Gender::toOptions(),
            'bloodGroupOptions'    => BloodGroup::toOptions(),
            'maritalStatusOptions' => MaritalStatus::toOptions(),
            'empStatusOptions'     => EmpStatus::toOptions(),
        ];

        if ($employee) {
            $employee->load(['department:id,name,company_id', 'designation:id,title', 'manager:id,first_name,last_name', 'user:id,employee_id,email,status']);
            $data['item'] = $employee;
        }

        return $data;
    }

    // ──────────────────────────────────────────────
    // User Account
    // ──────────────────────────────────────────────

    protected function createUser(Employee $employee, string $password): void
    {
        $employee->user()->create([
            'name'     => $employee->full_name,
            'email'    => $employee->email,
            'password' => $password,
            'status'   => $employee->status,
        ]);
    }

    protected function syncUser(Employee $employee): void
    {
        $employee->user?->update([
            'name'   => $employee->full_name,
            'email'  => $employee->email,
            'status' => $employee->status,
        ]);
    }
}
