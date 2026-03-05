<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leaveType')?->id;

        return [
            'name'         => [
                'required', 'string', 'max:255',
                Rule::unique('leave_types', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($leaveTypeId),
            ],
            'max_per_year' => ['required', 'integer', 'min:1', 'max:365'],
            'company_id'   => ['required', 'exists:companies,id'],
            'status'       => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'         => 'leave type name',
            'max_per_year' => 'max days per year',
            'company_id'   => 'company',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Leave type name is required.',
            'name.unique'           => 'This leave type name already exists for the selected company.',
            'max_per_year.required' => 'Max days per year is required.',
            'max_per_year.integer'  => 'Max days per year must be a whole number.',
            'max_per_year.min'      => 'Max days per year must be at least 1.',
            'max_per_year.max'      => 'Max days per year cannot exceed 365.',
            'company_id.required'   => 'Please select a company.',
            'company_id.exists'     => 'Selected company is invalid or inactive.',
        ];
    }
}
