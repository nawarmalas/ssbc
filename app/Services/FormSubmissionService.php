<?php

namespace App\Services;

use App\Mail\AdminSubmissionNotification;
use App\Mail\ApplicantConfirmation;
use App\Models\FormAnswer;
use App\Models\FormSubmission;
use App\Models\FormUpload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FormSubmissionService
{
    public function store(Request $request, string $formId, bool $sendApplicantConfirmation = false): FormSubmission
    {
        $form = FormService::getActiveForm($formId);

        $this->normalisePhoneAnswers($request, $form);
        $request->validate($this->rulesFor($request, $form), [
            'answers.*.*.regex' => 'Please enter a phone number with country code, e.g. +966 50 000 0000.',
            'answers.*.*.before' => 'You must be at least 18 years old.',
            'answers.*.*.before_or_equal' => 'Date cannot be in the future.',
        ]);

        $submission = FormSubmission::create([
            'form_id' => $formId,
            'display_name' => strip_tags((string) $this->displayName($request, $form)),
            'ip_address' => $request->ip(),
            'submitted_at' => now(),
        ]);

        $this->storeAnswers($request, $submission);
        $this->storeUploads($request, $submission);

        Mail::to('info@ssbc.org')->queue(new AdminSubmissionNotification($submission));

        if ($sendApplicantConfirmation) {
            $this->sendApplicantConfirmation($form, $submission);
        }

        $this->notifyGoogleSheet($submission);

        return $submission;
    }

    private function normalisePhoneAnswers(Request $request, Collection $form): void
    {
        $answers = (array) $request->input('answers', []);

        foreach ($form as $section) {
            foreach ($section->fields as $field) {
                if ($field->field_type !== 'tel' || ! isset($answers[$field->id])) {
                    continue;
                }

                foreach ($answers[$field->id] as $repeat => $value) {
                    if (is_string($value) && $value !== '') {
                        $answers[$field->id][$repeat] = preg_replace('/\s+/', '', $value);
                    }
                }
            }
        }

        $request->merge(['answers' => $answers]);
    }

    private function rulesFor(Request $request, Collection $form): array
    {
        $rules = ['_repeats' => 'array'];
        $repeats = $request->input('_repeats', []);
        $today = now()->toDateString();
        $dobMax = now()->subYears(18)->toDateString();
        $phoneRegex = 'regex:/^\+[1-9]\d{7,14}$/';

        foreach ($form as $section) {
            $count = $section->is_repeatable ? max(1, (int) ($repeats[$section->id] ?? 1)) : 1;

            foreach ($section->fields as $field) {
                $labelLower = strtolower($field->label_en ?? '');
                $isDob = str_contains($labelLower, 'birth') || str_contains($labelLower, 'dob');

                for ($repeat = 0; $repeat < $count; $repeat++) {
                    if ($field->field_type === 'file') {
                        $rules["files.{$field->id}.{$repeat}"] = array_values(array_filter([
                            $field->is_required && $repeat === 0 ? 'required' : 'nullable',
                            'file',
                            'mimes:'.$field->acceptedMimes(),
                            'max:'.$field->maxFileSizeKb(),
                        ]));
                        continue;
                    }

                    $fieldRules = [$field->is_required && $repeat === 0 ? 'required' : 'nullable'];

                    if ($field->field_type === 'email') {
                        $fieldRules[] = 'email';
                    } elseif ($field->field_type === 'url') {
                        $fieldRules[] = 'url';
                    } elseif ($field->field_type === 'tel') {
                        $fieldRules[] = $phoneRegex;
                    } elseif ($field->field_type === 'number') {
                        $fieldRules[] = 'integer';
                        if (($field->validation_rules['min'] ?? null) !== null) {
                            $fieldRules[] = 'min:'.$field->validation_rules['min'];
                        }
                        if (($field->validation_rules['max'] ?? null) !== null) {
                            $fieldRules[] = 'max:'.$field->validation_rules['max'];
                        }
                    } elseif ($field->field_type === 'date') {
                        $fieldRules[] = 'date';
                        $fieldRules[] = $isDob ? "before:{$dobMax}" : "before_or_equal:{$today}";
                    }

                    $rules["answers.{$field->id}.{$repeat}"] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    private function displayName(Request $request, Collection $form): ?string
    {
        $firstSection = $form->first();
        $nameFieldId = $firstSection?->fields->where('field_type', 'text')->first()?->id;

        return $nameFieldId ? $request->input("answers.{$nameFieldId}.0") : null;
    }

    private function storeAnswers(Request $request, FormSubmission $submission): void
    {
        $answerRows = [];

        foreach ($request->input('answers', []) as $fieldId => $repeatValues) {
            foreach ($repeatValues as $repeatIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $answerRows[] = [
                    'submission_id' => $submission->id,
                    'field_id' => (int) $fieldId,
                    'repeat_index' => (int) $repeatIndex,
                    'answer_value' => strip_tags(is_array($value) ? json_encode($value) : (string) $value),
                    'created_at' => now(),
                ];
            }
        }

        if ($answerRows) {
            FormAnswer::insert($answerRows);
        }
    }

    private function storeUploads(Request $request, FormSubmission $submission): void
    {
        $rawFiles = $request->file('files', []);

        if (empty($rawFiles)) {
            Log::warning('form_submission.store: no files in request', [
                'submission_id' => $submission->id,
                'form_id' => $submission->form_id,
                'content_type' => $request->header('content-type'),
                'content_length' => $request->header('content-length'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_size' => ini_get('upload_max_filesize'),
                'has_files_key' => isset($_FILES['files']),
                'all_files_keys' => array_keys($request->allFiles()),
            ]);
        }

        foreach ($rawFiles as $fieldId => $repeatFiles) {
            foreach ($repeatFiles as $repeatIndex => $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                try {
                    $path = $file->store("submissions/{$submission->id}", 'public');

                    if (! $path) {
                        continue;
                    }

                    FormUpload::create([
                        'submission_id' => $submission->id,
                        'field_id' => (int) $fieldId,
                        'repeat_index' => (int) $repeatIndex,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('form_submission.store: failed to persist upload', [
                        'submission_id' => $submission->id,
                        'field_id' => $fieldId,
                        'repeat_index' => $repeatIndex,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function sendApplicantConfirmation(Collection $form, FormSubmission $submission): void
    {
        $emailFieldId = $form->flatMap->fields->where('field_type', 'email')->first()?->id;
        $applicantEmail = $emailFieldId
            ? FormAnswer::where('submission_id', $submission->id)->where('field_id', $emailFieldId)->value('answer_value')
            : null;

        if ($applicantEmail && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
            Mail::to($applicantEmail)->queue(new ApplicantConfirmation($submission));
        }
    }

    private function notifyGoogleSheet(FormSubmission $submission): void
    {
        $scriptUrl = config('services.google_script_url');

        if (! $scriptUrl) {
            return;
        }

        try {
            Http::timeout(5)->post($scriptUrl, [
                'form_id' => $submission->form_id,
                'display_name' => $submission->display_name,
                'submission_id' => $submission->id,
                'submitted_at' => $submission->submitted_at->toISOString(),
            ]);
        } catch (\Throwable) {
            // Fire and forget.
        }
    }
}
