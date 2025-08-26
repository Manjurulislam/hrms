<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayRequest extends FormRequest
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
        $holidayId = $this->route('holiday')?->id;

        return [
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('holidays', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->where('day_at', $this->input('day_at'))
                    ->ignore($holidayId)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'day_at'      => [
                'required',
                'date',
                'after_or_equal:today'
            ],
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
            'name'        => 'holiday name',
            'description' => 'description',
            'day_at'      => 'holiday date',
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
            'name.required'         => 'Holiday name is required.',
            'name.unique'           => 'This holiday name already exists for the selected company on this date.',
            'description.max'       => 'Description cannot exceed 1000 characters.',
            'day_at.required'       => 'Holiday date is required.',
            'day_at.date'           => 'Please enter a valid date.',
            'day_at.after_or_equal' => 'Holiday date cannot be in the past.',
            'company_id.required'   => 'Please select a company.',
            'company_id.exists'     => 'Selected company is invalid or inactive.',
        ];
    }
}
