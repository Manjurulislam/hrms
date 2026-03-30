<?php

namespace App\Http\Requests\Auth;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class EmployeeRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['nullable', 'string', 'max:100'],
            'id_no'         => ['nullable', 'string', 'max:50', Rule::unique('employees', 'id_no')],
            'email'         => ['required', 'email', 'max:255', Rule::unique('employees', 'email')],
            'phone'         => ['required', 'string', 'max:20', Rule::unique('employees', 'phone')],
            'gender'        => ['required', new Enum(Gender::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'company_id'    => ['required', Rule::exists('companies', 'id')->where('status', true)],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')
                    ->where('company_id', $this->input('company_id'))
                    ->where('status', true),
            ],
            'designation_id' => [
                'nullable',
                Rule::exists('designations', 'id')
                    ->where('company_id', $this->input('company_id'))
                    ->where('status', true),
            ],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name'     => 'first name',
            'last_name'      => 'last name',
            'id_no'          => 'employee ID',
            'phone'          => 'phone number',
            'date_of_birth'  => 'date of birth',
            'company_id'     => 'company',
            'department_id'  => 'department',
            'designation_id' => 'designation',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'    => 'Please enter your first name.',
            'id_no.unique'           => 'This employee ID is already taken.',
            'email.required'         => 'Please enter your email address.',
            'email.email'            => 'Please enter a valid email address.',
            'email.unique'           => 'This email is already registered.',
            'phone.required'         => 'Please enter your phone number.',
            'phone.unique'           => 'This phone number is already registered.',
            'gender.required'        => 'Please select your gender.',
            'company_id.required'    => 'Please select a company.',
            'company_id.exists'      => 'The selected company is invalid or inactive.',
            'department_id.required' => 'Please select a department.',
            'department_id.exists'   => 'The selected department does not belong to the selected company.',
            'designation_id.exists'  => 'The selected designation does not belong to the selected company.',
            'password.required'      => 'Please enter a password.',
            'password.confirmed'     => 'Passwords do not match.',
        ];
    }
}
