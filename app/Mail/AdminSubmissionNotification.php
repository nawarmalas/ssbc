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
        $this->submission->loadMissing('formDefinition');
        $label = $this->submission->formDefinition?->title_en ?? 'Form';
        $name = $this->submission->display_name ?: '#'.$this->submission->id;

        return new Envelope(subject: "New SSBC {$label} Submission - {$name}");
    }

    public function content(): Content
    {
        $form = FormService::getActiveForm($this->submission->form_id);
        $this->submission->load(['answers', 'uploads', 'formDefinition']);

        return new Content(
            view: 'mail.admin-notification',
            with: ['form' => $form, 'submission' => $this->submission],
        );
    }
}
