<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class DepartmentScheduleRequest extends FormRequest
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
        return [
            'work_start_time' => ['required', 'date_format:h:i A'],
            'work_end_time'   => ['required', 'date_format:h:i A', 'after_time:work_start_time'],
            'working_days'    => ['required', 'array', 'min:1'],
            'working_days.*'  => ['required', 'string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->addExtension('after_time', function ($attribute, $value, $parameters, $validator) {
            $startTimeField = $parameters[0];
            $startTime      = $validator->getData()[$startTimeField] ?? null;

            if (!$startTime || !$value) {
                return false;
            }

            try {
                $startCarbon = Carbon::createFromFormat('h:i A', $startTime);
                $endCarbon   = Carbon::createFromFormat('h:i A', $value);

                // Handle overnight shifts (e.g., 11:00 PM to 6:00 AM)
                if ($endCarbon->lessThan($startCarbon)) {
                    $endCarbon->addDay();
                }

                return $endCarbon->greaterThan($startCarbon);
            } catch (Exception $e) {
                return false;
            }
        });
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
            'working_days.required'       => 'Please select at least one working day.',
            'working_days.min'            => 'Please select at least one working day.',
            'working_days.array'          => 'Working days must be provided as a list.',
            'working_days.*.in'           => 'Invalid working day selected.',
            'working_days.*.required'     => 'Working day cannot be empty.',
            'working_days.*.string'       => 'Working day must be a valid text.',
            'work_start_time.required'    => 'Work start time is required.',
            'work_start_time.date_format' => 'Please enter a valid start time (e.g., 08:30 AM).',
            'work_end_time.required'      => 'Work end time is required.',
            'work_end_time.date_format'   => 'Please enter a valid end time (e.g., 05:30 PM).',
            'work_end_time.after_time'    => 'Work end time must be after start time.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'work_start_time' => 'start time',
            'work_end_time'   => 'end time',
            'working_days'    => 'working days',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure working_days is always an array
        if ($this->has('working_days') && !is_array($this->working_days)) {
            $this->merge([
                'working_days' => []
            ]);
        }
    }
}
