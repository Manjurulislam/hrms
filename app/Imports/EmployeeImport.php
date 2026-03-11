<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private int $importedCount = 0;
    private ?Role $employeeRole;

    public function __construct(
        protected readonly mixed $company,
        protected readonly mixed $department,
    ) {
        $this->employeeRole = Role::where('slug', 'employee')->first();
    }

    public function model(array $row): ?Employee
    {
        $name  = data_get($row, 'name');
        $email = data_get($row, 'email');

        $employee = Employee::create([
            'first_name'    => $name,
            'email'         => $email,
            'company_id'    => $this->company,
            'department_id' => $this->department,
        ]);

        $user = User::create([
            'name'        => $name,
            'email'       => $email,
            'password'    => 'Employee@123',
            'employee_id' => $employee->id,
            'status'      => true,
        ]);

        if ($this->employeeRole) {
            $user->roles()->attach($this->employeeRole);
        }

        $this->importedCount++;

        return $employee;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('employees', 'email'),
                Rule::unique('users', 'email'),
            ],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required'  => 'Employee name is required.',
            'email.required' => 'Employee email is required.',
            'email.email'    => 'Please provide a valid email address.',
            'email.unique'   => 'This email address is already registered.',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
