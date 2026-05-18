<?php

namespace App\Exports;

use App\Models\FormSection;
use App\Models\FormSubmission;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubmissionsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private ?string $from = null,
        private ?string $to = null,
    ) {}

    public function collection(): Collection
    {
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
            ->orderBy('order_index')
            ->get();

        $query = FormSubmission::where('form_id', 'join-us')
            ->with(['answers'])
            ->orderBy('submitted_at');

        if ($this->from) $query->whereDate('submitted_at', '>=', $this->from);
        if ($this->to)   $query->whereDate('submitted_at', '<=', $this->to);

        $maxRepeats = 5;

        return $query->get()->map(function (FormSubmission $sub) use ($sections, $maxRepeats) {
            $row = [
                $sub->id,
                $sub->submitted_at->format('Y-m-d H:i'),
                $sub->status,
                $sub->display_name,
                $sub->ip_address,
            ];

            foreach ($sections as $section) {
                $repeats = $section->is_repeatable ? $maxRepeats : 1;
                foreach ($section->allFields as $field) {
                    if ($field->field_type === 'file') continue;
                    for ($r = 0; $r < $repeats; $r++) {
                        $row[] = $sub->answerFor($field->id, $r) ?? '';
                    }
                }
            }

            return $row;
        });
    }

    public function headings(): array
    {
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
            ->orderBy('order_index')
            ->get();

        $maxRepeats = 5;
        $headers = ['ID', 'Submitted At', 'Status', 'Display Name', 'IP Address'];

        foreach ($sections as $section) {
            $repeats = $section->is_repeatable ? $maxRepeats : 1;
            foreach ($section->allFields as $field) {
                if ($field->field_type === 'file') continue;
                for ($r = 0; $r < $repeats; $r++) {
                    $suffix = $repeats > 1 ? ' (' . ($r + 1) . ')' : '';
                    $headers[] = $field->label_en . $suffix;
                }
            }
        }

        return $headers;
    }
}
