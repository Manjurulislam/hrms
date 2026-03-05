<?php

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use App\Enums\EmpStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['nullable', 'string', 'max:100'],
            'email'             => ['required', 'email', 'max:255', Rule::unique('employees')->ignore($employeeId)],
            'phone'             => ['nullable', 'string', 'max:20'],
            'sec_phone'         => ['nullable', 'string', 'max:20'],
            'nid'               => ['nullable', 'string', 'max:20', Rule::unique('employees')->ignore($employeeId)],
            'gender'            => ['nullable', new Enum(Gender::class)],
            'qualification'     => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'blood_group'       => ['nullable', new Enum(BloodGroup::class)],
            'marital_status'    => ['nullable', new Enum(MaritalStatus::class)],
            'bank_account'      => ['nullable', 'string', 'max:50'],
            'address'           => ['nullable', 'string', 'max:500'],
            'company_id'        => ['required', 'exists:companies,id'],
            'department_id'     => ['required', 'exists:departments,id'],
            'designation_id'    => ['nullable', 'exists:designations,id'],
            'manager_id'        => ['nullable', 'exists:employees,id'],
            'emp_status'        => ['required', new Enum(EmpStatus::class)],
            'status'            => ['boolean'],
            'date_of_birth'     => ['nullable', 'date', 'before:today'],
            'joining_date'      => ['nullable', 'date'],
            'password'          => ['nullable', 'string', 'min:6'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name'        => 'first name',
            'last_name'         => 'last name',
            'sec_phone'         => 'secondary phone',
            'nid'               => 'national ID',
            'emergency_contact' => 'emergency contact',
            'blood_group'       => 'blood group',
            'marital_status'    => 'marital status',
            'bank_account'      => 'bank account',
            'company_id'        => 'company',
            'department_id'     => 'department',
            'designation_id'    => 'designation',
            'manager_id'        => 'manager',
            'emp_status'        => 'employment status',
            'date_of_birth'     => 'date of birth',
            'joining_date'      => 'joining date',
        ];
    }
}
