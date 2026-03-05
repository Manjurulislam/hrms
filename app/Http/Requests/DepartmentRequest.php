<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name'        => [
                'required', 'string', 'max:255',
                Rule::unique('departments', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($departmentId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'company_id'  => ['required', 'exists:companies,id'],
            'status'      => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'        => 'department name',
            'description' => 'description',
            'company_id'  => 'company',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Department name is required.',
            'name.unique'         => 'This department name already exists in the selected company.',
            'description.max'     => 'Description cannot exceed 1000 characters.',
            'company_id.required' => 'Please select a company.',
            'company_id.exists'   => 'Selected company is invalid or inactive.',
        ];
    }
}
