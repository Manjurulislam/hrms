<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BreakStartRequest extends FormRequest
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
            'break_type' => 'required|in:lunch,tea,personal,prayer,other',
            'reason' => 'nullable|required_if:break_type,other|string|max:255',
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

                // Check if there's an active session
                $activeSession = AttendanceSession::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->where('status', 'active')
                    ->first();

                if (!$activeSession) {
                    $validator->errors()->add('session', 'No active session found. Please check in first.');
                    return;
                }

                // Check for existing active break
                $activeBreak = AttendanceBreak::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->where('status', 'active')
                    ->first();

                if ($activeBreak) {
                    $validator->errors()->add('break', 'You already have an active break. Please end it first.');
                    return;
                }

                // Check maximum breaks per day
                $breaksToday = AttendanceBreak::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->count();

                $maxBreaks = config('attendance.max_breaks_per_day', 5); // Default 5 breaks

                if ($breaksToday >= $maxBreaks) {
                    $validator->errors()->add('break', "Maximum {$maxBreaks} breaks allowed per day.");
                }

                // Store active session for use in controller
                $this->merge(['active_session' => $activeSession]);
            }
        });
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'break_type.required' => 'Please specify the type of break.',
            'break_type.in' => 'Invalid break type selected.',
            'reason.required_if' => 'Please provide a reason for other type of break.',
            'reason.max' => 'Reason is too long (maximum 255 characters).',
        ];
    }

    /**
     * Get sanitized data for processing
     */
    public function getSanitizedData(): array
    {
        return [
            'break_type' => $this->input('break_type'),
            'reason' => $this->input('reason'),
        ];
    }

    /**
     * Get the active session (set during validation)
     */
    public function getActiveSession(): ?AttendanceSession
    {
        return $this->input('active_session');
    }
}