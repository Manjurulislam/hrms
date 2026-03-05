<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceBreak;
use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BreakEndRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'note' => 'nullable|string|max:255',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $employee = $this->getEmployee();
            if (!$employee) return;

            $this->validateHasActiveBreak($validator, $employee);
            $this->validateMinimumBreakDuration($validator);
        });
    }

    private function validateHasActiveBreak($validator, $employee): void
    {
        $activeBreak = $this->findActiveBreak($employee);

        if (!$activeBreak) {
            $validator->errors()->add('break', 'No active break found.');
            return;
        }

        $this->merge(['active_break' => $activeBreak]);
    }

    private function validateMinimumBreakDuration($validator): void
    {
        if ($validator->errors()->isNotEmpty()) return;

        $activeBreak = $this->input('active_break');
        $breakSeconds = (int) abs($activeBreak->break_start->diffInSeconds(now()));

        if ($breakSeconds < 60) {
            $remaining = 60 - $breakSeconds;
            $validator->errors()->add(
                'break',
                "Break too short. Please wait {$remaining} more second(s)."
            );
        }
    }

    public function messages(): array
    {
        return [
            'note.max' => 'Note is too long (maximum 255 characters).',
        ];
    }

    public function getActiveBreak(): ?AttendanceBreak
    {
        return $this->input('active_break');
    }
}
