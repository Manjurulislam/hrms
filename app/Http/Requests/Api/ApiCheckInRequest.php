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
     * Mobile check-in keeps ONLY the daily max-sessions guard — no office-hours
     * or office-network gates. lat/long are stored, never used to gate.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employee = $this->getEmployee();

            if (! $employee) {
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
