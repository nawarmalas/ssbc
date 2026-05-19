<?php

namespace App\Http\Controllers;

use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\FormUpload;
use App\Mail\AdminSubmissionNotification;
use App\Mail\ApplicantConfirmation;
use App\Services\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class JoinController extends Controller
{
    public function create(string $locale, bool $preview = false)
    {
        $form = FormService::getActiveForm('join-us');
        return view('join.create', compact('form', 'preview'));
    }

    public function store(Request $request, string $locale)
    {
        $form = FormService::getActiveForm('join-us');
        $repeats = $request->input('_repeats', []);

        // Normalise phone fields — strip whitespace before the regex check.
        $answers = (array) $request->input('answers', []);
        foreach ($form as $section) {
            foreach ($section->fields as $field) {
                if ($field->field_type !== 'tel') continue;
                if (! isset($answers[$field->id])) continue;
                foreach ($answers[$field->id] as $r => $val) {
                    if (is_string($val) && $val !== '') {
                        $answers[$field->id][$r] = preg_replace('/\s+/', '', $val);
                    }
                }
            }
        }
        $request->merge(['answers' => $answers]);

        // Build validation rules dynamically
        $rules = ['_repeats' => 'array'];
        $today = now()->toDateString();
        $dobMax = now()->subYears(18)->toDateString();
        $phoneRegex = 'regex:/^\+[1-9]\d{7,14}$/';

        foreach ($form as $section) {
            $count = $section->is_repeatable ? max(1, (int) ($repeats[$section->id] ?? 1)) : 1;

            foreach ($section->fields as $field) {
                $labelLower = strtolower($field->label_en ?? '');
                $isDob = str_contains($labelLower, 'birth') || str_contains($labelLower, 'dob');

                for ($r = 0; $r < $count; $r++) {
                    $key = "answers.{$field->id}.{$r}";

                    if ($field->field_type === 'file') {
                        $fileKey = "files.{$field->id}.{$r}";
                        $mimes = $field->acceptedMimes();
                        $max   = $field->maxFileSizeKb();
                        $rules[$fileKey] = array_values(array_filter([
                            $field->is_required && $r === 0 ? 'required' : 'nullable',
                            'file',
                            "mimes:{$mimes}",
                            "max:{$max}",
                        ]));
                        continue;
                    }

                    $fieldRules = [$field->is_required && $r === 0 ? 'required' : 'nullable'];

                    switch ($field->field_type) {
                        case 'email':
                            $fieldRules[] = 'email';
                            break;
                        case 'url':
                            $fieldRules[] = 'url';
                            break;
                        case 'tel':
                            $fieldRules[] = $phoneRegex;
                            break;
                        case 'number':
                            $fieldRules[] = 'integer';
                            $min = $field->validation_rules['min'] ?? null;
                            $max = $field->validation_rules['max'] ?? null;
                            if ($min !== null) $fieldRules[] = "min:{$min}";
                            if ($max !== null) $fieldRules[] = "max:{$max}";
                            break;
                        case 'date':
                            $fieldRules[] = 'date';
                            $fieldRules[] = $isDob ? "before:{$dobMax}" : "before_or_equal:{$today}";
                            break;
                    }

                    $rules[$key] = $fieldRules;
                }
            }
        }

        $request->validate($rules, [
            'answers.*.*.regex' => 'Please enter a phone number with country code, e.g. +966 50 000 0000.',
            'answers.*.*.before' => 'You must be at least 18 years old.',
            'answers.*.*.before_or_equal' => 'Date cannot be in the future.',
        ]);

        // Resolve display_name from first text field of first section
        $firstSection = $form->first();
        $nameFieldId  = $firstSection?->fields->where('field_type', 'text')->first()?->id;
        $displayName  = $nameFieldId ? $request->input("answers.{$nameFieldId}.0") : null;

        $submission = FormSubmission::create([
            'form_id'      => 'join-us',
            'display_name' => strip_tags((string) $displayName),
            'ip_address'   => $request->ip(),
            'submitted_at' => now(),
        ]);

        // Persist text answers
        $answers = $request->input('answers', []);
        $answerRows = [];

        foreach ($answers as $fieldId => $repeatValues) {
            foreach ($repeatValues as $repeatIndex => $value) {
                if ($value === null || $value === '') continue;
                $answerRows[] = [
                    'submission_id' => $submission->id,
                    'field_id'      => (int) $fieldId,
                    'repeat_index'  => (int) $repeatIndex,
                    'answer_value'  => strip_tags(is_array($value) ? json_encode($value) : (string) $value),
                    'created_at'    => now(),
                ];
            }
        }

        if ($answerRows) {
            FormAnswer::insert($answerRows);
        }

        // Persist file uploads under submissions/{submission_id}/
        $rawFiles = $request->file('files', []);
        if (empty($rawFiles)) {
            Log::warning('join.store: no files in request', [
                'submission_id'    => $submission->id,
                'content_type'     => $request->header('content-type'),
                'content_length'   => $request->header('content-length'),
                'post_max_size'    => ini_get('post_max_size'),
                'upload_max_size'  => ini_get('upload_max_filesize'),
                'has_files_key'    => isset($_FILES['files']),
                'all_files_keys'   => array_keys($request->allFiles()),
            ]);
        }

        foreach ($rawFiles as $fieldId => $repeatFiles) {
            foreach ($repeatFiles as $repeatIndex => $file) {
                if (! $file) {
                    Log::warning('join.store: null file entry', [
                        'submission_id' => $submission->id,
                        'field_id'      => $fieldId,
                        'repeat_index'  => $repeatIndex,
                    ]);
                    continue;
                }
                if (! $file->isValid()) {
                    Log::warning('join.store: invalid file', [
                        'submission_id' => $submission->id,
                        'field_id'      => $fieldId,
                        'repeat_index'  => $repeatIndex,
                        'error_code'    => $file->getError(),
                        'error_message' => $file->getErrorMessage(),
                        'name'          => $file->getClientOriginalName(),
                    ]);
                    continue;
                }
                try {
                    $path = $file->store("submissions/{$submission->id}", 'public');
                    if (! $path) {
                        Log::error('join.store: $file->store returned false', [
                            'submission_id' => $submission->id,
                            'field_id'      => $fieldId,
                        ]);
                        continue;
                    }
                    FormUpload::create([
                        'submission_id' => $submission->id,
                        'field_id'      => (int) $fieldId,
                        'repeat_index'  => (int) $repeatIndex,
                        'file_path'     => $path,
                        'file_name'     => $file->getClientOriginalName(),
                        'file_size'     => $file->getSize(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('join.store: failed to persist upload', [
                        'submission_id' => $submission->id,
                        'field_id'      => $fieldId,
                        'repeat_index'  => $repeatIndex,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }
        }

        // Notify admin
        Mail::to('info@ssbc.org')->queue(new AdminSubmissionNotification($submission));

        // Find applicant email answer
        $emailFieldId = $form->flatMap->fields->where('field_type', 'email')->first()?->id;
        $applicantEmail = $emailFieldId
            ? FormAnswer::where('submission_id', $submission->id)->where('field_id', $emailFieldId)->value('answer_value')
            : null;

        if ($applicantEmail && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
            Mail::to($applicantEmail)->queue(new ApplicantConfirmation($submission));
        }

        // Google Sheets (fire-and-forget)
        $scriptUrl = config('services.google_script_url');
        if ($scriptUrl) {
            try {
                Http::timeout(5)->post($scriptUrl, [
                    'display_name'  => $submission->display_name,
                    'submission_id' => $submission->id,
                    'submitted_at'  => $submission->submitted_at->toISOString(),
                ]);
            } catch (\Throwable) {
                // silent
            }
        }

        return redirect()->route('join.thanks', ['locale' => $locale]);
    }

    public function thanks(string $locale)
    {
        return view('join.thanks');
    }
}
