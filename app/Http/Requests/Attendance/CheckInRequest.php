<?php

namespace App\Http\Requests\Attendance;

use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckInRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $employee = $this->getEmployee();
            if (!$employee) return;

            $this->validateOfficeHours($validator, $employee);
            $this->validateOfficeHoursNotCompleted($validator, $employee);
            $this->validateNoActiveSession($validator, $employee);
            // $this->validateSessionGap($validator, $employee);
            $this->validateMaxSessions($validator, $employee);
        });
    }

    private function validateOfficeHours($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        if (!$this->isWithinOfficeHours($employee)) {
            $range = $this->getOfficeTimeRange($employee);
            $validator->errors()->add(
                'session',
                "You can only start work during office hours ({$range['start']} - {$range['end']})."
            );
        }
    }

    private function validateOfficeHoursNotCompleted($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        if ($this->hasCompletedOfficeHours($employee)) {
            $totalMinutes = $this->getTotalOfficeMinutes($employee);
            $hours = floor($totalMinutes / 60);
            $mins = $totalMinutes % 60;
            $validator->errors()->add(
                'session',
                "You have already completed your office hours ({$hours}h {$mins}m) for today."
            );
        }
    }

    private function validateNoActiveSession($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        if ($this->findActiveSession($employee)) {
            $validator->errors()->add('session', 'You already have an active session. Please check out first.');
        }
    }

    private function validateSessionGap($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        $lastSession = $this->findLastCompletedSession($employee);
        if (!$lastSession?->check_out_time) return;

        $minutesSince = (int) abs($lastSession->check_out_time->diffInMinutes(now()));
        $minimumGap = $this->companySetting($employee->company, 'min_session_gap');

        if ($minutesSince < $minimumGap) {
            $validator->errors()->add(
                'session',
                "Please wait at least {$minimumGap} minutes between sessions. {$minutesSince} minutes have passed since last checkout."
            );
        }
    }

    private function validateMaxSessions($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        $maxSessions = $this->companySetting($employee->company, 'max_sessions');

        if ($this->getTodaySessionCount($employee) >= $maxSessions) {
            $validator->errors()->add('session', "Maximum {$maxSessions} sessions allowed per day.");
        }
    }

    public function messages(): array
    {
        return [
            'lat.between' => 'Latitude must be between -90 and 90 degrees.',
            'long.between' => 'Longitude must be between -180 and 180 degrees.',
            'location.max' => 'Location description is too long.',
            'note.max' => 'Note is too long (maximum 500 characters).',
        ];
    }

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
