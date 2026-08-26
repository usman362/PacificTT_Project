<?php

namespace App\Mail;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnrollmentConfirmed extends Mailable
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
            subject: 'Enrollment confirmed — ' . $this->enrollment->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enrollment-confirmed',
            with: [
                'enrollment' => $this->enrollment->loadMissing(['program', 'classSession']),
                'payment'    => $this->payment,
            ],
        );
    }
}
