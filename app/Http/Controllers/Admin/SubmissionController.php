<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SubmissionsExport;
use App\Http\Controllers\Controller;
use App\Models\FormSection;
use App\Models\FormSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = FormSubmission::where('form_id', 'join-us')
            ->orderByDesc('submitted_at');

        if ($request->filled('from')) {
            $query->whereDate('submitted_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('submitted_at', '<=', $request->input('to'));
        }

        $submissions = $query->paginate(30)->withQueryString();

        return view('admin.submissions.index', compact('submissions'));
    }

    public function show(FormSubmission $submission)
    {
        $submission->load(['answers', 'uploads']);
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
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
        $submission->load(['answers', 'uploads']);
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
            ->orderBy('order_index')
            ->get();

        $pdf = Pdf::loadView('admin.submissions.pdf', compact('submission', 'sections'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("ssbc-submission-{$submission->id}.pdf");
    }

    public function export(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        return Excel::download(
            new SubmissionsExport($from, $to),
            'ssbc-submissions-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
