<?php

namespace App\Http\Requests;

use App\Enums\Gender;
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
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ];

        if ($employee) {
            $rules['first_name']     = ['required', 'string', 'max:100'];
            $rules['last_name']      = ['nullable', 'string', 'max:100'];
            $rules['phone']          = ['nullable', 'string', 'max:20'];
            $rules['gender']         = ['nullable', new Enum(Gender::class)];
            $rules['date_of_birth']  = ['nullable', 'date', 'before:today'];
            $rules['address']        = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Please enter your name.',
            'email.required'      => 'Please enter your email address.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already taken.',
            'first_name.required' => 'Please enter your first name.',
        ];
    }
}
