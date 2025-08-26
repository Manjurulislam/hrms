<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($companyId)
            ],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'status'  => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name'    => 'company name',
            'code'    => 'company code',
            'email'   => 'email address',
            'phone'   => 'phone number',
            'address' => 'company address',
            'website' => 'website URL',
            'status'  => 'status',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required'  => 'Company name is required.',
            'code.required'  => 'Company code is required.',
            'code.unique'    => 'This company code is already taken.',
            'email.required' => 'Email address is required.',
            'email.email'    => 'Please enter a valid email address.',
            'email.unique'   => 'This email address is already registered.',
            'website.url'    => 'Please enter a valid website URL.',
        ];
    }
}
