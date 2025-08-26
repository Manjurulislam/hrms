<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
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
        $departmentId = $this->route('department')?->id;

        return [
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($departmentId)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'company_id'  => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where('status', true)
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
            'name'        => 'department name',
            'description' => 'description',
            'company_id'  => 'company',
            'status'      => 'status',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
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
