<?php

namespace App\Http\Requests\Api;

use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApiCheckInRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'long'     => ['nullable', 'numeric', 'between:-180,180'],
            'note'     => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Mobile check-in enforces the check-in window (no check-in after office hours,
     * allowed from check_in_open until office_end) and the daily max-sessions guard.
     * No office-network gate — mobile employees may check in from anywhere.
     * lat/long are stored, never used to gate.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employee = $this->getEmployee();

            if (! $employee) {
                return;
            }

            // No check-in outside the check-in window (check_in_open → office_end).
            if (! $this->isWithinCheckInWindow($employee)) {
                $range = $this->getCheckInWindowRange($employee);
                $validator->errors()->add('session', "You can only check in between {$range['start']} and {$range['end']}.");

                return;
            }

            $max = $this->companySetting($employee->company, 'max_sessions');

            if ($this->getTodaySessionCount($employee) >= $max) {
                $validator->errors()->add('session', "Maximum {$max} sessions allowed per day.");
            }
        });
    }

    public function getSanitizedData(): array
    {
        return [
            'location' => $this->input('location', 'office'),
            'lat'      => $this->input('lat'),
            'long'     => $this->input('long'),
            'note'     => $this->input('note'),
        ];
    }
}
