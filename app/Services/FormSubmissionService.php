<?php

namespace App\Services;

use App\Mail\AdminSubmissionNotification;
use App\Mail\ApplicantConfirmation;
use App\Models\FormAnswer;
use App\Models\FormField;
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

        $this->normaliseAnswers($request, $form);
        $request->validate(
            $this->rulesFor($request, $form),
            array_merge([
                'answers.*.*.regex'          => __('join.js.phone_server'),
                'answers.*.*.before'         => __('join.js.dob'),
                'answers.*.*.before_or_equal'=> __('join.js.future_date'),
                'answers.*.*.min'            => __('join.js.select_one'),
            ], $this->numberFieldMessages($form))
        );

        $submission = FormSubmission::create([
            'form_id'      => $formId,
            'display_name' => strip_tags((string) $this->displayName($request, $form)),
            'ip_address'   => $request->ip(),
            'submitted_at' => now(),
        ]);

        $this->storeAnswers($request, $submission, $form);
        $this->storeUploads($request, $submission);

        Mail::to('info@ssbc.org')->queue(new AdminSubmissionNotification($submission));

        if ($sendApplicantConfirmation) {
            $this->sendApplicantConfirmation($form, $submission);
        }

        $this->notifyGoogleSheet($submission);

        return $submission;
    }

    private function normaliseAnswers(Request $request, Collection $form): void
    {
        $answers = (array) $request->input('answers', []);

        foreach ($form as $section) {
            foreach ($section->fields as $field) {
                if (! isset($answers[$field->id])) {
                    continue;
                }

                // Phone: strip spaces, dashes, parentheses and dots so forgiving,
                // human-formatted input (e.g. "+963 (11) 222-3333") validates and
                // stores in a consistent shape.
                if ($field->field_type === 'tel') {
                    foreach ($answers[$field->id] as $repeat => $value) {
                        if (is_string($value) && $value !== '') {
                            $answers[$field->id][$repeat] = preg_replace('/[\s\-().]+/', '', $value);
                        }
                    }
                    continue;
                }

                // URL: users rarely type the scheme, so prepend "https://" when it
                // is missing (e.g. "linkedin.com/in/x" → "https://linkedin.com/in/x")
                // so the `url` rule passes instead of silently rejecting them.
                if ($field->field_type === 'url') {
                    foreach ($answers[$field->id] as $repeat => $value) {
                        if (is_string($value)) {
                            $trimmed = trim($value);
                            if ($trimmed !== '' && ! preg_match('#^https?://#i', $trimmed)) {
                                $trimmed = 'https://'.ltrim($trimmed, '/');
                            }
                            $answers[$field->id][$repeat] = $trimmed;
                        }
                    }
                    continue;
                }

                // Establishment year: expand a 2-digit entry ("96", "03") to the
                // intended 4-digit year so users are not silently blocked by the
                // 1900+ minimum. Mirrors the inline expansion shown in the form.
                if ($this->isYearField($field)) {
                    foreach ($answers[$field->id] as $repeat => $value) {
                        if (is_string($value) || is_int($value)) {
                            $answers[$field->id][$repeat] = $this->expandTwoDigitYear((string) $value);
                        }
                    }
                }
            }
        }

        $request->merge(['answers' => $answers]);
    }

    /**
     * A "year" field is a number field whose minimum is itself year-shaped
     * (>= 1000). This deliberately keys off the min rather than the label so
     * that ordinary counts like "Years of experience" are never expanded.
     */
    private function isYearField(FormField $field): bool
    {
        if ($field->field_type !== 'number') {
            return false;
        }

        $min = $field->validation_rules['min'] ?? null;

        return $min !== null && (int) $min >= 1000;
    }

    /**
     * Expand a 1- or 2-digit year to 4 digits.
     * 00–<current 2-digit year> → 2000s, otherwise → 1900s.
     * Anything that is not a bare 1–2 digit number is returned unchanged.
     */
    private function expandTwoDigitYear(string $value): string
    {
        $trimmed = trim($value);
        if (! preg_match('/^\d{1,2}$/', $trimmed)) {
            return $value;
        }

        $twoDigit     = (int) $trimmed;
        $currentPivot = (int) now()->format('y');

        $century = $twoDigit > $currentPivot ? 1900 : 2000;

        return (string) ($century + $twoDigit);
    }

    private function rulesFor(Request $request, Collection $form): array
    {
        $rules   = ['_repeats' => 'array'];
        $repeats = $request->input('_repeats', []);
        $answers = (array) $request->input('answers', []);
        $today   = now()->toDateString();
        $dobMax  = now()->subYears(18)->toDateString();
        // Forgiving phone rule: accept local numbers (no country code) as well as
        // international ones prefixed with "+" or "00". Formatting characters are
        // already stripped in normaliseAnswers(); we only reject input that is not
        // phone-shaped at all (letters, far too short/long).
        $phoneRegex = 'regex:/^(?:\+|00)?\d{6,15}$/';

        foreach ($form as $section) {
            $count = $section->is_repeatable ? max(1, (int) ($repeats[$section->id] ?? 1)) : 1;

            foreach ($section->fields as $field) {
                $labelLower = strtolower($field->label_en ?? '');
                $isDob      = str_contains($labelLower, 'birth') || str_contains($labelLower, 'dob');

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

                    // Skip hidden conditional fields
                    if (! $this->fieldIsVisible($field, $answers, $repeat, $form)) {
                        $rules["answers.{$field->id}.{$repeat}"] = ['nullable'];
                        continue;
                    }

                    // checkbox_group: require array with at least 1 item
                    if ($field->field_type === 'checkbox_group') {
                        $rules["answers.{$field->id}.{$repeat}"] = $field->is_required && $repeat === 0
                            ? ['required', 'array', 'min:1']
                            : ['nullable', 'array'];
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

    /**
     * Friendly, field-specific messages for number min/max rules.
     *
     * Without these, a number field's "min"/"max" failure falls through to the
     * wildcard answers.*.*.min message ("Please select at least one option."),
     * which is meant for checkbox groups and is baffling on a year field. Keying
     * the message to the exact attribute lets Laravel prefer it over the wildcard.
     */
    private function numberFieldMessages(Collection $form): array
    {
        $messages = [];

        foreach ($form as $section) {
            $maxRepeats = $section->is_repeatable ? max(1, (int) $section->max_repeats) : 1;

            foreach ($section->fields as $field) {
                if ($field->field_type !== 'number') {
                    continue;
                }

                $min     = $field->validation_rules['min'] ?? null;
                $max     = $field->validation_rules['max'] ?? null;
                $isYear  = $this->isYearField($field);

                for ($repeat = 0; $repeat < $maxRepeats; $repeat++) {
                    if ($min !== null) {
                        $messages["answers.{$field->id}.{$repeat}.min"] = $isYear
                            ? __('join.js.year_range', ['min' => $min, 'max' => $max])
                            : __('join.js.number_min', ['min' => $min]);
                    }
                    if ($max !== null) {
                        $messages["answers.{$field->id}.{$repeat}.max"] = $isYear
                            ? __('join.js.year_range', ['min' => $min, 'max' => $max])
                            : __('join.js.number_max', ['max' => $max]);
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Evaluate a field's conditional_logic against the submitted answers.
     * Returns true if the field should be shown (no logic = always visible).
     */
    private function fieldIsVisible(FormField $field, array $answers, int $repeat, Collection $form): bool
    {
        $logic = $field->conditional_logic ?? null;
        if (! $logic || empty($logic['conditions'])) {
            return true;
        }

        // Build code → field-id map from the form
        $codeToId = $form->flatMap->fields
            ->filter(fn($f) => $f->code !== null)
            ->mapWithKeys(fn($f) => [$f->code => $f->id])
            ->all();

        $results = [];
        foreach ($logic['conditions'] as $c) {
            $targetId = $codeToId[$c['field_code']] ?? null;
            $val      = $targetId !== null
                ? ($answers[$targetId][$repeat] ?? $answers[$targetId][0] ?? null)
                : null;

            $results[] = match ($c['op']) {
                'equals'     => $val === $c['value'],
                'not_equals' => $val !== $c['value'],
                'in'         => in_array($val, (array) $c['value'], true),
                'not_in'     => ! in_array($val, (array) $c['value'], true),
                'contains'   => is_array($val)
                                    ? in_array($c['value'], $val, true)
                                    : (is_string($val) && str_contains($val, (string) $c['value'])),
                default      => true,
            };
        }

        return ($logic['operator'] ?? 'AND') === 'OR'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    private function displayName(Request $request, Collection $form): ?string
    {
        $firstSection = $form->first();
        $nameFieldId  = $firstSection?->fields->where('field_type', 'text')->first()?->id;

        return $nameFieldId ? $request->input("answers.{$nameFieldId}.0") : null;
    }

    private function storeAnswers(Request $request, FormSubmission $submission, Collection $form): void
    {
        $fieldsById = $form->flatMap->fields->keyBy('id');
        $answers    = (array) $request->input('answers', []);
        $answerRows = [];

        foreach ($answers as $fieldId => $repeatValues) {
            $field = $fieldsById->get((int) $fieldId);
            if (! $field) continue;

            foreach ($repeatValues as $repeatIndex => $value) {
                if ($value === null || $value === '' || $value === []) continue;

                // Skip answers for hidden conditional fields
                if (! $this->fieldIsVisible($field, $answers, (int) $repeatIndex, $form)) {
                    continue;
                }

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
    }

    private function storeUploads(Request $request, FormSubmission $submission): void
    {
        $rawFiles = $request->file('files', []);

        if (empty($rawFiles)) {
            Log::warning('form_submission.store: no files in request', [
                'submission_id' => $submission->id,
                'form_id'       => $submission->form_id,
                'content_type'  => $request->header('content-type'),
                'content_length'=> $request->header('content-length'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_size'=> ini_get('upload_max_filesize'),
                'has_files_key' => isset($_FILES['files']),
                'all_files_keys'=> array_keys($request->allFiles()),
            ]);
        }

        foreach ($rawFiles as $fieldId => $repeatFiles) {
            foreach ($repeatFiles as $repeatIndex => $file) {
                if (! $file || ! $file->isValid()) continue;

                try {
                    $path = $file->store("submissions/{$submission->id}", 'public');
                    if (! $path) continue;

                    FormUpload::create([
                        'submission_id' => $submission->id,
                        'field_id'      => (int) $fieldId,
                        'repeat_index'  => (int) $repeatIndex,
                        'file_path'     => $path,
                        'file_name'     => $file->getClientOriginalName(),
                        'file_size'     => $file->getSize(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('form_submission.store: failed to persist upload', [
                        'submission_id' => $submission->id,
                        'field_id'      => $fieldId,
                        'repeat_index'  => $repeatIndex,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function sendApplicantConfirmation(Collection $form, FormSubmission $submission): void
    {
        $emailFieldId  = $form->flatMap->fields->where('field_type', 'email')->first()?->id;
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
        if (! $scriptUrl) return;

        try {
            Http::timeout(5)->post($scriptUrl, [
                'form_id'       => $submission->form_id,
                'display_name'  => $submission->display_name,
                'submission_id' => $submission->id,
                'submitted_at'  => $submission->submitted_at->toISOString(),
            ]);
        } catch (\Throwable) {
            // Fire and forget.
        }
    }
}
