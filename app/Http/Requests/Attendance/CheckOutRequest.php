<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has an employee profile
        return $this->user() && $this->user()->employee;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'location' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'long' => 'nullable|numeric|between:-180,180',
            'note' => 'nullable|string|max:500',
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

                // Check if there's an active session to check out from
                $activeSession = AttendanceSession::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->where('status', 'active')
                    ->first();

                if (!$activeSession) {
                    $validator->errors()->add('session', 'No active check-in found. Please check in first.');
                    return;
                }

                // Check minimum session duration
                $minimumDuration = config('attendance.minimum_session_duration', 1); // Default 1 minute
                $sessionDuration = $activeSession->check_in_time->diffInMinutes(now());

                if ($sessionDuration < $minimumDuration) {
                    $validator->errors()->add(
                        'session',
                        "Session too short. Minimum {$minimumDuration} minute(s) required. Current duration: {$sessionDuration} minute(s)."
                    );
                }

                // Store active session for later use in controller
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
            'lat.between' => 'Latitude must be between -90 and 90 degrees.',
            'long.between' => 'Longitude must be between -180 and 180 degrees.',
            'location.max' => 'Location description is too long.',
            'note.max' => 'Note is too long (maximum 500 characters).',
        ];
    }

    /**
     * Get sanitized data for processing
     */
    public function getSanitizedData(): array
    {
        return [
            'location' => $this->input('location', 'office'),
            'lat' => $this->input('lat'),
            'long' => $this->input('long'),
            'note' => $this->input('note'),
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