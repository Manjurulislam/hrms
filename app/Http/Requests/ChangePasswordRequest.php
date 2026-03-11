<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => 'Please enter your current password.',
            'current_password.current_password'  => 'The current password is incorrect.',
            'password.required'                  => 'Please enter a new password.',
            'password.confirmed'                 => 'Password confirmation does not match.',
        ];
    }
}
