<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentWorkingDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }


    public function rules(): array
    {
        $workingDayId = $this->route('department_working_day')?->id;

        return [
            'day'                    => [
                'required',
                'string',
                'max:30',
                Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
                Rule::unique('department_working_days', 'day')->ignore($workingDayId)
            ],
            'department_schedule_id' => [
                'required',
                'integer',
                'exists:department_schedules,id'
            ],
            'status'                 => 'boolean'
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'day.required'                    => 'Please select a day.',
            'day.in'                          => 'Please select a valid day of the week.',
            'day.unique'                      => 'This day already exists.',
            'department_schedule_id.required' => 'Please select a department schedule.',
            'department_schedule_id.exists'   => 'The selected department schedule does not exist.',
            'status.boolean'                  => 'Status must be either active or inactive.'
        ];
    }
}
