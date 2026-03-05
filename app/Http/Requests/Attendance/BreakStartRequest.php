<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceSession;
use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BreakStartRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'break_type' => 'required|in:lunch,tea,personal,prayer,other',
            'reason' => 'nullable|required_if:break_type,other|string|max:255',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $employee = $this->getEmployee();
            if (!$employee) return;

            $this->validateHasActiveSession($validator, $employee);
            $this->validateNoActiveBreak($validator, $employee);
            $this->validateMaxBreaks($validator, $employee);
        });
    }

    private function validateHasActiveSession($validator, $employee): void
    {
        $activeSession = $this->findActiveSession($employee);

        if (!$activeSession) {
            $validator->errors()->add('session', 'No active session found. Please check in first.');
            return;
        }

        $this->merge(['active_session' => $activeSession]);
    }

    private function validateNoActiveBreak($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        if ($this->findActiveBreak($employee)) {
            $validator->errors()->add('break', 'You already have an active break. Please end it first.');
        }
    }

    private function validateMaxBreaks($validator, $employee): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        $maxBreaks = config('attendance.max_breaks_per_day', 5);

        if ($this->getTodayBreakCount($employee) >= $maxBreaks) {
            $validator->errors()->add('break', "Maximum {$maxBreaks} breaks allowed per day.");
        }
    }

    public function messages(): array
    {
        return [
            'break_type.required' => 'Please specify the type of break.',
            'break_type.in' => 'Invalid break type selected.',
            'reason.required_if' => 'Please provide a reason for other type of break.',
            'reason.max' => 'Reason is too long (maximum 255 characters).',
        ];
    }

    public function getSanitizedData(): array
    {
        return [
            'break_type' => $this->input('break_type'),
            'reason' => $this->input('reason'),
        ];
    }

    public function getActiveSession(): ?AttendanceSession
    {
        return $this->input('active_session');
    }
}
