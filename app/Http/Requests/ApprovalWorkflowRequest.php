<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'company_id'              => ['required', 'exists:companies,id'],
            'is_active'               => ['boolean'],
            'steps'                   => ['required', 'array', 'min:1'],
            'steps.*.approver_type'   => ['required', 'string', 'in:direct_manager,designation_level,specific_employee,department_head'],
            'steps.*.approver_value'  => ['nullable', 'integer'],
            'steps.*.is_mandatory'    => ['boolean'],
            'steps.*.condition_type'  => ['required', 'string', 'in:always,days_greater_than,days_less_than'],
            'steps.*.condition_value' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                   => 'Workflow name is required.',
            'company_id.required'             => 'Please select a company.',
            'company_id.exists'               => 'The selected company is invalid.',
            'steps.required'                  => 'At least one approval step is required.',
            'steps.min'                       => 'At least one approval step is required.',
            'steps.*.approver_type.required'  => 'Approver type is required for each step.',
            'steps.*.approver_type.in'        => 'Invalid approver type selected.',
            'steps.*.condition_type.required' => 'Condition type is required for each step.',
            'steps.*.condition_type.in'       => 'Invalid condition type selected.',
        ];
    }
}
