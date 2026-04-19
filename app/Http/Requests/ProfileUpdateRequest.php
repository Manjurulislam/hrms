<?php

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user     = auth()->user();
        $employee = $user->employee;

        $rules = [
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
                Rule::unique('employees')->ignore($employee?->id),
            ],
        ];

        if ($employee) {
            $rules['first_name']        = ['required', 'string', 'max:100'];
            $rules['last_name']         = ['nullable', 'string', 'max:100'];
            $rules['phone']             = ['nullable', 'string', 'max:20', Rule::unique('employees')->ignore($employee->id)];
            $rules['sec_phone']         = ['nullable', 'string', 'max:20'];
            $rules['nid']               = ['nullable', 'string', 'max:20', Rule::unique('employees')->ignore($employee->id)];
            $rules['gender']            = ['nullable', new Enum(Gender::class)];
            $rules['date_of_birth']     = ['nullable', 'date', 'before:today'];
            $rules['blood_group']       = ['nullable', new Enum(BloodGroup::class)];
            $rules['marital_status']    = ['nullable', new Enum(MaritalStatus::class)];
            $rules['emergency_contact'] = ['nullable', 'string', 'max:20'];
            $rules['bank_account']      = ['nullable', 'string', 'max:50'];
            $rules['address']           = ['nullable', 'string', 'max:500'];
            $rules['designation_id']    = ['nullable', 'integer', Rule::exists('designations', 'id')->where('company_id', $employee->company_id)->where('status', true)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.required'      => 'Please enter your email address.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already taken.',
            'first_name.required' => 'Please enter your first name.',
            'phone.unique'        => 'This phone number is already registered.',
            'nid.unique'          => 'This national ID is already registered.',
        ];
    }
}
