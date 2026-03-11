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
            'office_start'    => ['nullable', 'date_format:H:i'],
            'office_end'      => ['nullable', 'date_format:H:i'],
            'office_ip'       => ['nullable', 'string', 'max:45'],
            'work_hours'      => ['nullable', 'integer', 'min:1', 'max:24'],
            'half_day_hours'  => ['nullable', 'integer', 'min:1', 'max:12'],
            'late_grace'      => ['nullable', 'integer', 'min:0', 'max:120'],
            'early_grace'     => ['nullable', 'integer', 'min:0', 'max:120'],
            'max_sessions'    => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_session_gap' => ['nullable', 'integer', 'min:0', 'max:60'],
            'max_breaks'      => ['nullable', 'integer', 'min:1', 'max:50'],
            'auto_close'      => ['boolean'],
            'auto_close_at'   => ['nullable', 'date_format:H:i'],
            'track_ip'        => ['boolean'],
            'track_location'  => ['boolean'],
            'status'          => ['boolean'],
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
            'office_start'    => 'office start time',
            'office_end'      => 'office end time',
            'office_ip'       => 'office IP',
            'work_hours'      => 'working hours',
            'half_day_hours'  => 'half day hours',
            'late_grace'      => 'late grace period',
            'early_grace'     => 'early leave grace',
            'max_sessions'    => 'max sessions per day',
            'min_session_gap' => 'min session gap',
            'max_breaks'      => 'max breaks per day',
            'auto_close_at'   => 'auto close time',
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
