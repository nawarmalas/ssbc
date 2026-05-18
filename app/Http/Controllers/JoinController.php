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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        // Build validation rules dynamically
        $rules = ['_repeats' => 'array'];

        foreach ($form as $section) {
            $count = $section->is_repeatable ? max(1, (int) ($repeats[$section->id] ?? 1)) : 1;

            foreach ($section->fields as $field) {
                for ($r = 0; $r < $count; $r++) {
                    $key = "answers.{$field->id}.{$r}";

                    if ($field->field_type === 'file') {
                        $fileKey = "files.{$field->id}.{$r}";
                        $mimes = $field->acceptedMimes();
                        $max   = $field->maxFileSizeKb();
                        $rules[$fileKey] = array_filter([
                            $field->is_required && $r === 0 ? 'required' : 'nullable',
                            'file',
                            "mimes:{$mimes}",
                            "max:{$max}",
                        ]);
                    } else {
                        $rules[$key] = $field->is_required && $r === 0 ? 'required' : 'nullable';
                        if ($field->field_type === 'email') $rules[$key] .= '|email';
                        if ($field->field_type === 'url')   $rules[$key] .= '|url';
                    }
                }
            }
        }

        $request->validate($rules);

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

        // Persist file uploads
        $uuid = (string) Str::uuid();

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $fieldId => $repeatFiles) {
                foreach ($repeatFiles as $repeatIndex => $file) {
                    if (! $file || ! $file->isValid()) continue;
                    $path = $file->store("submissions/{$uuid}", 'public');
                    FormUpload::create([
                        'submission_id' => $submission->id,
                        'field_id'      => (int) $fieldId,
                        'repeat_index'  => (int) $repeatIndex,
                        'file_path'     => $path,
                        'file_name'     => $file->getClientOriginalName(),
                        'file_size'     => $file->getSize(),
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
