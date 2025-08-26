<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId   = $this->route('user')?->id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password'    => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed'
            ],
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('status', true),
                Rule::unique('users', 'employee_id')->ignore($userId)
            ],
            'status'      => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name'        => 'name',
            'email'       => 'email address',
            'password'    => 'password',
            'employee_id' => 'employee',
            'status'      => 'status',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required'        => 'Name is required.',
            'email.required'       => 'Email address is required.',
            'email.email'          => 'Please enter a valid email address.',
            'email.unique'         => 'This email address is already registered.',
            'password.required'    => 'Password is required.',
            'password.min'         => 'Password must be at least 8 characters.',
            'password.confirmed'   => 'Password confirmation does not match.',
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists'   => 'Selected employee is invalid or inactive.',
            'employee_id.unique'   => 'This employee already has a user account.',
        ];
    }
}
