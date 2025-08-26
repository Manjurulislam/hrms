<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $scheduleId = $this->route('department_schedule')?->id;
        $validDays  = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        return [
            'department_id'   => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('status', true),
                Rule::unique('department_schedules', 'department_id')->ignore($scheduleId)
            ],
            'work_days'       => [
                'required',
                'array',
                'min:1'
            ],
            'work_days.*'     => [
                'string',
                Rule::in($validDays)
            ],
            'work_start_time' => [
                'required',
                'date_format:Y-m-d H:i:s'
            ],
            'work_end_time'   => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:work_start_time'
            ],
        ];
    }

    /**
     * Get the validation rules for the input data before transformation.
     */
    public function getValidatorInstance()
    {
        // Override to validate time format before transformation
        $validator = parent::getValidatorInstance();

        $validator->sometimes('work_start_time', 'date_format:H:i', function ($input) {
            return is_string($input->work_start_time) && strlen($input->work_start_time) <= 5;
        });

        $validator->sometimes('work_end_time', 'date_format:H:i', function ($input) {
            return is_string($input->work_end_time) && strlen($input->work_end_time) <= 5;
        });

        return $validator;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'department_id'   => 'department',
            'work_days'       => 'work days',
            'work_days.*'     => 'work day',
            'work_start_time' => 'work start time',
            'work_end_time'   => 'work end time',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'department_id.required'      => 'Please select a department.',
            'department_id.exists'        => 'Selected department is invalid or inactive.',
            'department_id.unique'        => 'This department already has a schedule assigned.',
            'work_days.required'          => 'Please select at least one work day.',
            'work_days.min'               => 'Please select at least one work day.',
            'work_days.*.in'              => 'Invalid work day selected.',
            'work_start_time.required'    => 'Work start time is required.',
            'work_start_time.date_format' => 'Please enter a valid start time (HH:MM format).',
            'work_end_time.required'      => 'Work end time is required.',
            'work_end_time.date_format'   => 'Please enter a valid end time (HH:MM format).',
            'work_end_time.after'         => 'Work end time must be after start time.',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    public function passedValidation()
    {
        // Additional business logic validation can be added here
        $workDays  = $this->input('work_days', []);
        $startTime = $this->input('work_start_time');
        $endTime   = $this->input('work_end_time');

        // You can add custom validation logic here if needed
        // For example, checking if the schedule makes business sense
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert time strings to datetime objects for storage
        if ($this->has('work_start_time') && $this->work_start_time) {
            $this->merge([
                'work_start_time' => Carbon::createFromFormat('H:i', $this->work_start_time)->format('Y-m-d H:i:s')
            ]);
        }

        if ($this->has('work_end_time') && $this->work_end_time) {
            $this->merge([
                'work_end_time' => Carbon::createFromFormat('H:i', $this->work_end_time)->format('Y-m-d H:i:s')
            ]);
        }
    }
}
