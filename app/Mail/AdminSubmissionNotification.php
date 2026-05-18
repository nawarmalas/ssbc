<?php

namespace App\Mail;

use App\Models\FormSubmission;
use App\Services\FormService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New SSBC Membership Application — ' . $this->submission->display_name,
        );
    }

    public function content(): Content
    {
        $form = FormService::getActiveForm('join-us');
        $this->submission->load(['answers', 'uploads']);

        return new Content(
            view: 'mail.admin-notification',
            with: ['form' => $form, 'submission' => $this->submission],
        );
    }
}
