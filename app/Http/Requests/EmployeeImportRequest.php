<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file'          => ['required', 'mimes:xlsx,xls,csv', 'max:2048'],
            'company_id'    => ['required', 'exists:companies,id'],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('company_id', $this->input('company_id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'          => 'Please select a file to import.',
            'file.mimes'             => 'File must be an Excel (.xlsx, .xls) or CSV file.',
            'file.max'               => 'File size must not exceed 2MB.',
            'company_id.required'    => 'Please select a company.',
            'department_id.required' => 'Please select a department.',
            'department_id.exists'   => 'The selected department does not belong to the selected company.',
        ];
    }
}
