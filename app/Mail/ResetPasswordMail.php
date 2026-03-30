<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    public function __construct(
        public string $url,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.reset-password',
            with: [
                'url' => $this->url,
                'name' => $this->name,
                'expireMinutes' => config('auth.passwords.users.expire', 60),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
