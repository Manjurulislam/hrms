<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255', Rule::unique('companies')->ignore($companyId)],
            'phone'             => ['nullable', 'string', 'max:20'],
            'address'           => ['nullable', 'string', 'max:500'],
            'website'           => ['nullable', 'url', 'max:255'],
            'office_start_time' => ['nullable', 'date_format:H:i'],
            'office_end_time'   => ['nullable', 'date_format:H:i'],
            'office_ip'         => ['nullable', 'string', 'max:45'],
            'status'            => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'              => 'company name',
            'email'             => 'email address',
            'phone'             => 'phone number',
            'address'           => 'company address',
            'website'           => 'website URL',
            'office_start_time' => 'office start time',
            'office_end_time'   => 'office end time',
            'office_ip'         => 'office IP',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Company name is required.',
            'email.required' => 'Email address is required.',
            'email.email'    => 'Please enter a valid email address.',
            'email.unique'   => 'This email address is already registered.',
            'website.url'    => 'Please enter a valid website URL.',
        ];
    }
}
