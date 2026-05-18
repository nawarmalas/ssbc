@extends('layouts.admin')

@section('title', 'Submission #' . $submission->id)

@section('content')
<a href="{{ route('admin.submissions.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">← Back to Submissions</a>

<div class="mt-4 w-12 h-px bg-ssbc-gold mb-4"></div>
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-display font-bold text-ssbc-green">{{ $submission->display_name ?? 'Submission #' . $submission->id }}</h1>
        <p class="text-sm text-ssbc-sage">Submitted {{ $submission->submitted_at->format('d M Y H:i') }} UTC · IP: {{ $submission->ip_address }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.submissions.pdf', $submission) }}" class="ssbc-btn-outline-dark text-sm">Download PDF</a>
        <form method="POST" action="{{ route('admin.submissions.destroy', $submission) }}"
              onsubmit="return confirm('Delete this submission permanently?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:text-red-800 border border-red-300 px-3 py-2">Delete</button>
        </form>
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
                                    @foreach($submission->uploadsFor($field->id, $r) as $upload)
                                        <dd class="text-sm">
                                            <a href="{{ $upload->url() }}" target="_blank" download
                                               class="ssbc-link-gold">{{ $upload->file_name }}</a>
                                            <span class="text-ssbc-sage text-xs ml-1">({{ round($upload->file_size / 1024) }} KB)</span>
                                        </dd>
                                    @endforeach
                                @else
                                    <dd class="text-sm text-ssbc-dark whitespace-pre-wrap">{{ $submission->answerFor($field->id, $r) ?? '—' }}</dd>
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
        <form method="POST" action="{{ route('admin.submissions.update', $submission) }}" class="ssbc-admin-card p-5">
            @csrf @method('PATCH')
            <h3 class="font-display font-bold text-ssbc-green mb-4">Status</h3>
            <select name="status" class="ssbc-input mb-4 text-sm">
                @foreach(['pending','under_review','approved','rejected'] as $s)
                    <option value="{{ $s }}" @selected($submission->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <h3 class="font-display font-bold text-ssbc-green mb-2">Admin Notes</h3>
            <textarea name="admin_notes" rows="5" class="ssbc-input text-sm mb-4">{{ $submission->admin_notes }}</textarea>
            <button type="submit" class="ssbc-btn-primary text-sm w-full">Save</button>
        </form>
    </div>
</div>
@endsection
