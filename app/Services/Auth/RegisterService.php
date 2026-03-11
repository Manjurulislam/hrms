<?php

namespace App\Services\Auth;

use App\Enums\Gender;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterService
{
    public function formData(): array
    {
        return [
            'companies'     => Company::where('status', true)->select('id', 'name')->get(),
            'departments'   => Department::where('status', true)->select('id', 'name', 'company_id')->get(),
            'genderOptions' => Gender::toOptions(),
        ];
    }

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $employee = $this->createEmployee($data);
            $user = $this->createUser($employee, $data);
            $this->assignEmployeeRole($user);

            return $user;
        });
    }

    private function createEmployee(array $data): Employee
    {
        return Employee::create([
            'first_name'    => data_get($data, 'first_name'),
            'last_name'     => data_get($data, 'last_name'),
            'id_no'         => data_get($data, 'id_no'),
            'email'         => data_get($data, 'email'),
            'phone'         => data_get($data, 'phone'),
            'gender'        => data_get($data, 'gender'),
            'date_of_birth' => data_get($data, 'date_of_birth'),
            'company_id'    => data_get($data, 'company_id'),
            'department_id' => data_get($data, 'department_id'),
            'emp_status'    => 'probation',
        ]);
    }

    private function createUser(Employee $employee, array $data): User
    {
        return User::create([
            'name'        => $employee->full_name,
            'email'       => data_get($data, 'email'),
            'password'    => data_get($data, 'password'),
            'employee_id' => $employee->id,
        ]);
    }

    private function assignEmployeeRole(User $user): void
    {
        $employeeRole = Role::where('slug', 'employee')->first();

        if ($employeeRole) {
            $user->roles()->attach($employeeRole);
        }
    }
}
