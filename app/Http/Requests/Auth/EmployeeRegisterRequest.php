<?php

namespace App\Http\Requests\Auth;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
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
            'first_name'  => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:255', 'unique:employees,email', 'unique:users,email'],
            'gender'      => ['nullable', new Enum(Gender::class)],
            'company_id'  => ['required', 'exists:companies,id'],
            'password'    => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'  => 'Please enter your full name.',
            'first_name.max'       => 'Name must not exceed 100 characters.',
            'email.required'       => 'Please enter your email address.',
            'email.email'          => 'Please enter a valid email address.',
            'email.unique'         => 'This email is already registered.',
            'gender.Illuminate\Validation\Rules\Enum' => 'Please select a valid gender.',
            'company_id.required'  => 'Please select a company.',
            'company_id.exists'    => 'The selected company is invalid.',
            'password.required'    => 'Please enter a password.',
            'password.confirmed'   => 'Passwords do not match.',
        ];
    }
}
