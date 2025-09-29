<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckInRequest extends FormRequest
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
            'session_type' => 'nullable|in:regular,overtime,break_return',
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

                // Check if there's already an active session
                $activeSession = AttendanceSession::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->where('status', 'active')
                    ->first();

                if ($activeSession) {
                    $validator->errors()->add('session', 'You already have an active session. Please check out first.');
                }

                // Check minimum gap between sessions (configurable)
                $lastSession = AttendanceSession::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->where('status', 'completed')
                    ->latest('check_out_time')
                    ->first();

                if ($lastSession && $lastSession->check_out_time) {
                    $minutesSinceLastCheckout = $lastSession->check_out_time->diffInMinutes(now());
                    $minimumGap = config('attendance.minimum_session_gap', 5); // Default 5 minutes

                    if ($minutesSinceLastCheckout < $minimumGap) {
                        $validator->errors()->add(
                            'session',
                            "Please wait at least {$minimumGap} minutes between sessions. {$minutesSinceLastCheckout} minutes have passed since last checkout."
                        );
                    }
                }

                // Check maximum sessions per day
                $sessionsToday = AttendanceSession::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', today())
                    ->count();

                $maxSessions = config('attendance.max_sessions_per_day', 10); // Default 10 sessions

                if ($sessionsToday >= $maxSessions) {
                    $validator->errors()->add(
                        'session',
                        "Maximum {$maxSessions} sessions allowed per day."
                    );
                }
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
            'session_type' => $this->input('session_type', 'regular'),
        ];
    }
}