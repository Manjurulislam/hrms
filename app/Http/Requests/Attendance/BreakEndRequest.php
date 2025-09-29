<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceBreak;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BreakEndRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->employee;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'note' => 'nullable|string|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->user() && $this->user()->employee) {
                $employee = $this->user()->employee;

                // Find active break
                $activeBreak = AttendanceBreak::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->where('status', 'active')
                    ->first();

                if (!$activeBreak) {
                    $validator->errors()->add('break', 'No active break found.');
                    return;
                }

                // Check minimum break duration
                $minimumDuration = config('attendance.minimum_break_duration', 1); // Default 1 minute
                $breakDuration = $activeBreak->break_start->diffInMinutes(now());

                if ($breakDuration < $minimumDuration) {
                    $validator->errors()->add(
                        'break',
                        "Break too short. Minimum {$minimumDuration} minute(s) required. Current duration: {$breakDuration} minute(s)."
                    );
                }

                // Check maximum break duration
                $maximumDuration = config('attendance.maximum_break_duration', 120); // Default 2 hours
                if ($breakDuration > $maximumDuration) {
                    $validator->errors()->add(
                        'break',
                        "Break exceeded maximum duration of {$maximumDuration} minutes."
                    );
                }

                // Store active break for use in controller
                $this->merge(['active_break' => $activeBreak]);
            }
        });
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'note.max' => 'Note is too long (maximum 255 characters).',
        ];
    }

    /**
     * Get the active break (set during validation)
     */
    public function getActiveBreak(): ?AttendanceBreak
    {
        return $this->input('active_break');
    }
}