<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['required', 'string'],
            'company_id'    => ['required', 'exists:companies,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'published_at'  => ['required', 'date'],
            'expired_at'    => ['nullable', 'date', 'after_or_equal:published_at'],
            'status'        => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'         => 'notice title',
            'description'   => 'description',
            'company_id'    => 'company',
            'department_id' => 'department',
            'published_at'  => 'publish date',
            'expired_at'    => 'expiry date',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'            => 'Notice title is required.',
            'description.required'      => 'Description is required.',
            'company_id.required'       => 'Please select a company.',
            'company_id.exists'         => 'Selected company is invalid.',
            'published_at.required'     => 'Publish date is required.',
            'expired_at.after_or_equal' => 'Expiry date must be on or after the publish date.',
        ];
    }
}
