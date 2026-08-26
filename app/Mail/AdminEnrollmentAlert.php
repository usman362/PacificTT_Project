<?php

namespace App\Mail;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminEnrollmentAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Enrollment $enrollment,
        public Payment $payment,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New enrollment — ' . $this->enrollment->name . ' (' . $this->enrollment->reference . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-enrollment-alert',
            with: [
                'enrollment' => $this->enrollment->loadMissing(['program', 'classSession', 'waiver']),
                'payment'    => $this->payment,
            ],
        );
    }
}
