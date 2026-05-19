@extends('layouts.admin')

@section('title', 'Submission #' . $submission->id)
@section('page_title', $submission->display_name ?? 'Submission #' . $submission->id)

@section('content')
<a href="{{ route('admin.submissions.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">← Back to Submissions</a>

<div class="mt-4 flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-display font-bold text-ssbc-green">{{ $submission->display_name ?? 'Submission #' . $submission->id }}</h1>
        <p class="text-sm text-ssbc-sage mt-1">Submitted {{ $submission->submitted_at->format('d M Y H:i') }} UTC · IP: {{ $submission->ip_address }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.submissions.pdf', $submission) }}" class="ssbc-admin-btn-primary">Download PDF</a>
        @include('partials.admin.confirm-delete', [
            'action'  => route('admin.submissions.destroy', $submission),
            'title'   => 'Delete submission?',
            'message' => 'This permanently removes the submission and any uploaded files. This cannot be undone.',
            'button'  => __('admin.delete'),
        ])
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    {{-- Answers --}}
    <div class="lg:col-span-2 space-y-6">
        @foreach($sections as $section)
            @php
                $maxRepeat = $submission->answers->where('repeat_index', '>', 0)->pluck('repeat_index')->max() ?? 0;
                $count = $section->is_repeatable ? $maxRepeat + 1 : 1;
            @endphp
            @for($r = 0; $r < $count; $r++)
                <div class="ssbc-admin-card p-5">
                    <h3 class="font-display font-bold text-ssbc-green mb-4">
                        {{ $section->title_en }}{{ $count > 1 ? ' ' . ($r + 1) : '' }}
                    </h3>
                    <dl class="grid sm:grid-cols-2 gap-4">
                        @foreach($section->allFields as $field)
                            @if($field->field_type === 'declaration') @continue @endif
                            <div @if($field->field_type === 'textarea') class="sm:col-span-2" @endif>
                                <dt class="ssbc-eyebrow mb-1">{{ $field->label_en }}</dt>
                                @if($field->field_type === 'file')
                                    @php $uploadsList = $submission->uploadsFor($field->id, $r); @endphp
                                    @if($uploadsList->isEmpty())
                                        <dd class="text-sm text-ssbc-sage italic">Not provided</dd>
                                    @else
                                        @foreach($uploadsList as $upload)
                                            <dd class="text-sm mb-1">
                                                <a href="{{ asset('storage/' . $upload->file_path) }}" target="_blank" download class="ssbc-file-link">
                                                    <span aria-hidden="true">📄</span>
                                                    <span>{{ $upload->file_name }}</span>
                                                    <span class="text-ssbc-sage text-xs">({{ round($upload->file_size / 1024) }} KB)</span>
                                                </a>
                                            </dd>
                                        @endforeach
                                    @endif
                                @else
                                    <dd class="text-sm text-ssbc-dark whitespace-pre-wrap">{{ $field->formatAnswer($submission->answerFor($field->id, $r)) }}</dd>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endfor
        @endforeach
    </div>

    {{-- Sidebar: status + notes --}}
    <div class="space-y-4">
        <form method="POST" action="{{ route('admin.submissions.update', $submission) }}" class="ssbc-admin-card p-6">
            @csrf @method('PATCH')
            <label class="ssbc-admin-label">Status</label>
            <select name="status" class="ssbc-admin-input mb-4">
                @foreach(['pending','under_review','approved','rejected'] as $s)
                    <option value="{{ $s }}" @selected($submission->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <label class="ssbc-admin-label">Admin Notes</label>
            <textarea name="admin_notes" rows="6" class="ssbc-admin-input mb-4">{{ $submission->admin_notes }}</textarea>
            <button type="submit" class="ssbc-admin-btn-primary w-full">Save</button>
        </form>
    </div>
</div>
@endsection
