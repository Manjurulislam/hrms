<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceSession;
use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckOutRequest extends FormRequest
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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $employee = $this->getEmployee();
            if (!$employee) return;

            $this->validateHasActiveSession($validator, $employee);
            $this->validateMinimumDuration($validator, $employee);
        });
    }

    private function validateHasActiveSession($validator, $employee): void
    {
        $activeSession = $this->findActiveSession($employee);

        if (!$activeSession) {
            $validator->errors()->add('session', 'No active check-in found. Please check in first.');
            return;
        }

        $this->merge(['active_session' => $activeSession]);
    }

    private function validateMinimumDuration($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        $activeSession = $this->input('active_session');
        $sessionSeconds = (int) abs($activeSession->check_in_time->diffInSeconds(now()));

        if ($sessionSeconds < 60) {
            $remaining = 60 - $sessionSeconds;
            $validator->errors()->add(
                'session',
                "Session too short. Please wait {$remaining} more second(s) before ending work."
            );
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
        ];
    }

    public function getActiveSession(): ?AttendanceSession
    {
        return $this->input('active_session');
    }
}
