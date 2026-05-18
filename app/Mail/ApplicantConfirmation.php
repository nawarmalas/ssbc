<?php

namespace App\Mail;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicantConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SSBC Membership Application — Received');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.applicant-confirmation',
            with: ['submission' => $this->submission],
        );
    }
}
