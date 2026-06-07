<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SubmissionsExport;
use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\FormSection;
use App\Models\FormSubmission;
use Mpdf\Mpdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = FormSubmission::with('formDefinition')->orderByDesc('submitted_at');

        if ($request->filled('form_id')) {
            $query->where('form_id', $request->input('form_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('submitted_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('submitted_at', '<=', $request->input('to'));
        }

        $submissions = $query->paginate(30)->withQueryString();

        $forms = FormDefinition::orderBy('title_en')->get();

        return view('admin.submissions.index', compact('submissions', 'forms'));
    }

    public function show(FormSubmission $submission)
    {
        $submission->load(['answers', 'uploads', 'formDefinition']);
        $sections = FormSection::with('allFields')
            ->where('form_id', $submission->form_id)
            ->orderBy('order_index')
            ->get();

        return view('admin.submissions.show', compact('submission', 'sections'));
    }

    public function update(Request $request, FormSubmission $submission)
    {
        $data = $request->validate([
            'status'      => ['sometimes', 'in:pending,under_review,approved,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update($data);

        return redirect()->route('admin.submissions.show', $submission)
            ->with('status', 'Submission updated.');
    }

    public function destroy(FormSubmission $submission)
    {
        $submission->delete();

        return redirect()->route('admin.submissions.index')
            ->with('status', 'Submission deleted.');
    }

    public function pdf(FormSubmission $submission)
    {
        $submission->load(['answers', 'uploads', 'formDefinition']);
        $sections = FormSection::with('allFields')
            ->where('form_id', $submission->form_id)
            ->orderBy('order_index')
            ->get();

        $html = view('admin.submissions.pdf', compact('submission', 'sections'))->render();

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 20,
            'margin_bottom' => 20,
        ]);
        $mpdf->WriteHTML($html);

        $filename = $this->pdfFilename($submission, $sections);

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function pdfFilename(FormSubmission $submission, $sections): string
    {
        $englishNameField = $sections
            ->flatMap(fn ($s) => $s->allFields)
            ->first(fn ($f) => $f->field_type === 'text'
                && str_contains(strtolower($f->label_en ?? ''), 'english'));

        if ($englishNameField) {
            $raw = $submission->answerFor($englishNameField->id, 0);
            if ($raw && trim($raw) !== '') {
                $slug = preg_replace('/[^a-zA-Z0-9\s\-]/', '', trim($raw));
                $slug = preg_replace('/\s+/', '-', $slug);
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
                if ($slug !== '') {
                    return "{$slug}-Application.pdf";
                }
            }
        }

        return "ssbc-submission-{$submission->id}.pdf";
    }

    public function export(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');
        $formId = $request->input('form_id');

        return Excel::download(
            new SubmissionsExport($from, $to, $formId),
            'ssbc-submissions-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
