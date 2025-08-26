<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
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
        $leaveTypeId = $this->route('leaveType')?->id;

        return [
            'name'       => [
                'required',
                'string',
                'max:255',
                Rule::unique('leave_types', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($leaveTypeId)
            ],
            'days'       => [
                'required',
                'integer',
                'min:1',
                'max:365'
            ],
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where('status', true)
            ],
            'status'     => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name'       => 'leave type name',
            'days'       => 'number of days',
            'company_id' => 'company',
            'status'     => 'status',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required'       => 'Leave type name is required.',
            'name.unique'         => 'This leave type name already exists for the selected company.',
            'days.required'       => 'Number of days is required.',
            'days.integer'        => 'Number of days must be a whole number.',
            'days.min'            => 'Number of days must be at least 1.',
            'days.max'            => 'Number of days cannot exceed 365.',
            'company_id.required' => 'Please select a company.',
            'company_id.exists'   => 'Selected company is invalid or inactive.',
        ];
    }
}
