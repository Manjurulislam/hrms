<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LeaveRequestCancelledMail extends Mailable
{
    public function __construct(
        public LeaveRequest $leaveRequest,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Request Cancelled',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave.cancelled',
            with: [
                'recipientName' => $this->recipientName,
                'employeeName'  => $this->leaveRequest->employee->full_name,
                'leaveType'     => $this->leaveRequest->leaveType->name,
                'startDate'     => $this->leaveRequest->started_at->format('d M Y'),
                'endDate'       => $this->leaveRequest->ended_at->format('d M Y'),
                'totalDays'     => $this->leaveRequest->total_days,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
