<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkingDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $workingDayId = $this->route('workingDay')?->id;
        $companyId = $this->route('company')?->id;

        return [
            'day_of_week' => [
                'required', 'integer', 'min:0', 'max:6',
                Rule::unique('company_working_days')
                    ->where('company_id', $companyId)
                    ->ignore($workingDayId),
            ],
            'day_label'   => ['required', 'string', 'max:10'],
            'is_working'  => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'day_of_week' => 'day of week',
            'day_label'   => 'day label',
            'is_working'  => 'working status',
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.required' => 'Please select a day.',
            'day_of_week.unique'   => 'This day already exists for this company.',
            'day_label.required'   => 'Day label is required.',
        ];
    }
}
