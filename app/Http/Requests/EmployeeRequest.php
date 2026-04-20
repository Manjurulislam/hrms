<?php

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use App\Enums\EmpStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee?->id;
        $isUpdate = (bool) $employeeId;

        return [
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['nullable', 'string', 'max:100'],
            'email'             => [
                'required', 'email', 'max:255',
                Rule::unique('employees')->ignore($employeeId),
                Rule::unique('users')->ignore($employee?->user?->id),
            ],
            'phone'             => ['nullable', 'string', 'max:20', Rule::unique('employees')->ignore($employeeId)],
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
            'department_id'     => [
                'required',
                Rule::exists('departments', 'id')->where('company_id', $this->input('company_id')),
            ],
            'designation_id'    => ['nullable', 'exists:designations,id'],
            'manager_id'        => [
                'nullable',
                'exists:employees,id',
                $isUpdate ? Rule::notIn([$employeeId]) : null,
            ],
            'emp_status'        => ['required', new Enum(EmpStatus::class)],
            'status'            => ['boolean'],
            'date_of_birth'     => ['nullable', 'date', 'before:today'],
            'joining_date'      => ['nullable', 'date'],
            'password'          => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles'             => ['nullable', 'array'],
            'roles.*'           => ['integer', 'exists:roles,id'],
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

    public function messages(): array
    {
        return [
            'first_name.required'  => 'First name is required.',
            'email.required'       => 'Email address is required.',
            'email.email'          => 'Please enter a valid email address.',
            'email.unique'         => 'This email address is already registered.',
            'phone.unique'         => 'This phone number is already registered.',
            'nid.unique'           => 'This national ID is already registered.',
            'company_id.required'  => 'Please select a company.',
            'department_id.exists' => 'The selected department does not belong to the selected company.',
            'manager_id.not_in'    => 'An employee cannot be their own manager.',
        ];
    }
}
