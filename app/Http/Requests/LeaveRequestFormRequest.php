<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'started_at'    => ['required', 'date'],
            'ended_at'      => ['required', 'date', 'after_or_equal:started_at'],
            'title'         => ['required', 'string', 'max:255'],
            'notes'         => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required'    => 'Please select a leave type.',
            'leave_type_id.exists'      => 'The selected leave type is invalid.',
            'started_at.required'       => 'Start date is required.',
            'ended_at.required'         => 'End date is required.',
            'ended_at.after_or_equal'   => 'End date must be on or after the start date.',
            'title.required'            => 'Please enter a title for your leave request.',
            'notes.required'            => 'Please provide a reason for your leave request.',
        ];
    }
}
