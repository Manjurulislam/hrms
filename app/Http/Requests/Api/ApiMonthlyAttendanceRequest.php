<?php

namespace App\Http\Requests\Api;

use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;

class ApiMonthlyAttendanceRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
        ];
    }

    public function year(): int
    {
        return (int) explode('-', $this->input('month'))[0];
    }

    public function month(): int
    {
        return (int) explode('-', $this->input('month'))[1];
    }
}
