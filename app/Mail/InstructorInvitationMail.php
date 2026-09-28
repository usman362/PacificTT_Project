<?php

namespace App\Mail;

use App\Models\InstructorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The single-use onboarding link. The 6-digit code follows when it is opened. */
class InstructorInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InstructorInvitation $invite) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Pacific Trade Tech instructor onboarding');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.instructor-invitation', with: [
            'link' => route('instructor.onboarding.show', ['token' => $this->invite->token]),
            'rate' => '$'.number_format($this->invite->daily_rate_cents / 100, 0),
        ]);
    }
}
