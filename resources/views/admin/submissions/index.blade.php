@extends('layouts.admin')

@section('title', 'Submissions')
@section('page_title', 'Submissions')

@php
    $statusLabels = [
        'pending'      => 'Pending',
        'under_review' => 'Under review',
        'approved'     => 'Approved',
        'rejected'     => 'Rejected',
    ];
@endphp

@section('content')
<div class="flex items-center justify-end mb-6">
    <a href="{{ route('admin.submissions.export', request()->only('from','to','form_id')) }}"
       class="ssbc-admin-btn-primary">Export Excel</a>
</div>

{{-- Status filter pills with live counts (scoped to the form/date filters) --}}
<div class="flex flex-wrap gap-2 mb-4">
    @php
        $pillBase   = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition-colors';
        $pillIdle   = 'border-ssbc-green/20 text-ssbc-dark/70 hover:border-ssbc-green hover:text-ssbc-green bg-white';
        $pillActive = 'border-ssbc-green bg-ssbc-green text-white';
    @endphp
    <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
       class="{{ $pillBase }} {{ $status === null ? $pillActive : $pillIdle }}">
        All <span class="opacity-70">({{ $statusCounts->sum() }})</span>
    </a>
    @foreach($statusLabels as $value => $label)
        <a href="{{ request()->fullUrlWithQuery(['status' => $value, 'page' => null]) }}"
           class="{{ $pillBase }} {{ $status === $value ? $pillActive : $pillIdle }}">
            {{ $label }} <span class="opacity-70">({{ $statusCounts[$value] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<form method="GET" class="flex flex-wrap gap-4 items-end mb-6">
    @if($status !== null)
        <input type="hidden" name="status" value="{{ $status }}">
    @endif
    <div class="w-full sm:w-80 lg:w-96">
        <label class="ssbc-admin-label">Form</label>
        <select name="form_id" class="ssbc-admin-input min-w-0" onchange="this.form.submit()">
            <option value="">All forms</option>
            @foreach($forms as $form)
                <option value="{{ $form->form_id }}" @selected(request('form_id') === $form->form_id)>
                    {{ $form->title_en }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="w-40">
        <label class="ssbc-admin-label">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="ssbc-admin-input">
    </div>
    <div class="w-40">
        <label class="ssbc-admin-label">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="ssbc-admin-input">
    </div>
    <div class="w-44">
        <label class="ssbc-admin-label">Sort</label>
        <select name="sort" class="ssbc-admin-input" onchange="this.form.submit()">
            <option value="newest" @selected($sort === 'newest')>Newest first</option>
            <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
            <option value="status" @selected(in_array($sort, ['status', 'status_desc']))>By status</option>
        </select>
    </div>
    <button type="submit" class="ssbc-admin-btn-primary">Filter</button>
    @if(request('from') || request('to') || request('form_id') || $status !== null || $sort !== 'newest')
        <a href="{{ route('admin.submissions.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green ml-2">Clear</a>
    @endif
</form>

<div class="ssbc-admin-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="ssbc-admin-thead">
            <tr>
                <th class="text-left px-4 py-3">Date</th>
                <th class="text-left px-4 py-3">Form</th>
                <th class="text-left px-4 py-3">Applicant</th>
                <th class="text-left px-4 py-3">
                    {{-- Click to sort by status; click again to flip direction --}}
                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'status' ? 'status_desc' : 'status', 'page' => null]) }}"
                       class="inline-flex items-center gap-1 hover:text-ssbc-gold"
                       title="Sort by status">
                        Status
                        @if($sort === 'status')
                            <span aria-hidden="true">&#9650;</span><span class="sr-only">(sorted ascending)</span>
                        @elseif($sort === 'status_desc')
                            <span aria-hidden="true">&#9660;</span><span class="sr-only">(sorted descending)</span>
                        @endif
                    </a>
                </th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $sub)
                <tr class="ssbc-admin-row">
                    <td class="px-4 py-3 text-ssbc-dark/70 whitespace-nowrap">{{ $sub->submitted_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-ssbc-dark">{{ $sub->formDefinition?->title_en ?? $sub->form_id }}</td>
                    <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $sub->display_name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="ssbc-status-badge ssbc-status-{{ $sub->status }}">{{ ucfirst(str_replace('_', ' ', $sub->status)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.submissions.show', $sub) }}" class="text-xs uppercase tracking-wider text-ssbc-green hover:text-ssbc-gold">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-ssbc-sage">
                        @if($status !== null)
                            No applications with this status.
                            <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
                               class="ml-1 font-semibold text-ssbc-green underline hover:text-ssbc-gold">Show all</a>
                        @elseif(request('form_id') || request('from') || request('to'))
                            No submissions match these filters.
                            <a href="{{ route('admin.submissions.index') }}"
                               class="ml-1 font-semibold text-ssbc-green underline hover:text-ssbc-gold">Show all</a>
                        @else
                            No submissions yet.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $submissions->links() }}</div>
@endsection
