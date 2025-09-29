<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    use Importable;

    protected mixed $company;
    protected mixed $department;
    private int     $importedCount = 0;
    private array   $failedRows    = [];
    private array   $errors        = [];

    public function __construct($company, $department)
    {
        $this->company    = $company;
        $this->department = $department;
    }

    public function model(array $row)
    {
        try {
            DB::beginTransaction();

            $name  = data_get($row, 'name');
            $email = data_get($row, 'email');

            // Basic validation
            if (empty($name) || empty($email)) {
                throw new Exception('Name and email are required');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            // Check for duplicates
            if (Employee::where('email', $email)->exists()) {
                throw new Exception('Email already exists in employees');
            }

            if (User::where('email', $email)->exists()) {
                throw new Exception('Email already exists in users');
            }

            // Create Employee
            $emData = [
                'first_name'    => $name,
                'email'         => $email,
                'company_id'    => $this->company,
                'department_id' => $this->department,
            ];

            $employee = Employee::create($emData);
            // Create User
            $userData = [
                'name'        => $name,
                'email'       => $email,
                'password'    => Hash::make('password'),
                'employee_id' => $employee->id,
            ];

            User::create($userData);

            $this->importedCount++;

            DB::commit();

            return $employee;
        } catch (Exception $e) {
            DB::rollBack();
            $this->errors[] = [
                'row'   => $this->getCurrentRowNumber(),
                'data'  => $row,
                'error' => $e->getMessage()
            ];
            return null;
        }
    }

    private function getCurrentRowNumber(): int
    {
        // This is an approximation since Laravel Excel 3.1 doesn't provide direct row numbers
        return $this->importedCount + count($this->errors) + 2; // +2 for header and 1-based indexing
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    // Laravel Excel 3.1 validation

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('employees', 'email'),
                Rule::unique('users', 'email')
            ],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required'  => 'Employee name is required',
            'email.required' => 'Employee email is required',
            'email.email'    => 'Please provide a valid email address',
            'email.unique'   => 'This email address is already registered',
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
