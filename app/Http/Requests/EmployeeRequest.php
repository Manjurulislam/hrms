<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'id_no'             => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'id_no')->ignore($employeeId)
            ],
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'email'             => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId)
            ],
            'phone'             => ['required', 'string', 'max:20'],
            'sec_phone'         => ['nullable', 'string', 'max:20'],
            'nid'               => ['nullable', 'string', 'max:14', Rule::unique('employees', 'nid')->ignore($employeeId)],
            'gender'            => ['required', Rule::in(['male', 'female', 'other'])],
            'qualification'     => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'blood_group'       => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'marital_status'    => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'bank_account'      => ['nullable', 'string', 'max:50'],
            'address'           => ['nullable', 'string', 'max:500'],
            'company_id'        => ['required', 'integer', 'exists:companies,id'],
            'department_id'     => ['required', 'integer', 'exists:departments,id'],
            'designations'      => ['required', "array"],
            'status'            => ['boolean'],
            'date_of_birth'     => ['required', 'date', 'before:today'],
            'joining_date'      => ['required', 'date'],
            'probation_end_at'  => ['nullable', 'date', 'after:joining_date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'id_no'             => 'employee ID',
            'first_name'        => 'first name',
            'last_name'         => 'last name',
            'email'             => 'email address',
            'phone'             => 'phone number',
            'sec_phone'         => 'secondary phone',
            'nid'               => 'national ID',
            'gender'            => 'gender',
            'qualification'     => 'qualification',
            'emergency_contact' => 'emergency contact',
            'blood_group'       => 'blood group',
            'marital_status'    => 'marital status',
            'bank_account'      => 'bank account',
            'address'           => 'address',
            'department_id'     => 'department',
            'status'            => 'status',
            'date_of_birth'     => 'date of birth',
            'joining_date'      => 'joining date',
            'probation_end_at'  => 'probation end date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'id_no.required'         => 'Employee ID is required.',
            'id_no.unique'           => 'This employee ID is already taken.',
            'first_name.required'    => 'First name is required.',
            'last_name.required'     => 'Last name is required.',
            'email.required'         => 'Email address is required.',
            'email.email'            => 'Please enter a valid email address.',
            'email.unique'           => 'This email address is already registered.',
            'phone.required'         => 'Phone number is required.',
            'nid.unique'             => 'This national ID is already registered.',
            'gender.required'        => 'Please select a gender.',
            'gender.in'              => 'Please select a valid gender option.',
            'blood_group.in'         => 'Please select a valid blood group.',
            'marital_status.in'      => 'Please select a valid marital status.',
            'department_id.required' => 'Please select a department.',
            'department_id.exists'   => 'Selected department is invalid or inactive.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before'   => 'Date of birth must be in the past.',
            'joining_date.required'  => 'Joining date is required.',
            'probation_end_at.after' => 'Probation end date must be after joining date.',
        ];
    }
}
