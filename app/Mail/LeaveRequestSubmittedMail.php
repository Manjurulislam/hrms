<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LeaveRequestSubmittedMail extends Mailable
{
    public function __construct(
        public LeaveRequest $leaveRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Request Submitted',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave.submitted',
            with: [
                'employeeName' => $this->leaveRequest->employee->full_name,
                'leaveType'    => $this->leaveRequest->leaveType->name,
                'startDate'    => $this->leaveRequest->started_at->format('d M Y'),
                'endDate'      => $this->leaveRequest->ended_at->format('d M Y'),
                'totalDays'    => $this->leaveRequest->total_days,
                'title'        => $this->leaveRequest->title,
                'notes'        => $this->leaveRequest->notes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
