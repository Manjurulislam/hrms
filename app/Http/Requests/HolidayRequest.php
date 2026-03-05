<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $holidayId = $this->route('holiday')?->id;

        return [
            'name'        => [
                'required', 'string', 'max:255',
                Rule::unique('holidays', 'name')
                    ->where('company_id', $this->input('company_id'))
                    ->where('start_date', $this->input('start_date'))
                    ->ignore($holidayId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'company_id'  => ['required', 'exists:companies,id'],
            'status'      => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'        => 'holiday name',
            'description' => 'description',
            'start_date'  => 'start date',
            'end_date'    => 'end date',
            'company_id'  => 'company',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Holiday name is required.',
            'name.unique'             => 'This holiday already exists for the selected company on this date.',
            'start_date.required'     => 'Start date is required.',
            'end_date.required'       => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'company_id.required'     => 'Please select a company.',
            'company_id.exists'       => 'Selected company is invalid or inactive.',
        ];
    }
}
