<?php

namespace App\Mail;

use App\Models\InstructorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstructorOnboardingCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public InstructorInvitation $invite,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Pacific Trade Tech verification code');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.instructor-code');
    }
}
