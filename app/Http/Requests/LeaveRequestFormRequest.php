<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => 'required|exists:leave_types,id',
            'started_at'    => 'required|date|after_or_equal:today',
            'ended_at'      => 'required|date|after_or_equal:started_at',
            'title'         => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
        ];
    }
}
