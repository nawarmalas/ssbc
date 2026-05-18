@extends('layouts.admin')

@section('title', 'Submissions')
@section('page_title', 'Submissions')

@section('content')
<div class="flex items-center justify-end mb-6">
    <a href="{{ route('admin.submissions.export', request()->only('from','to')) }}"
       class="ssbc-admin-btn-primary">Export Excel ↓</a>
</div>

{{-- Date filter --}}
<form method="GET" class="flex flex-wrap gap-3 items-end mb-6">
    <div>
        <label class="ssbc-admin-label">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="ssbc-admin-input">
    </div>
    <div>
        <label class="ssbc-admin-label">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="ssbc-admin-input">
    </div>
    <button type="submit" class="ssbc-admin-btn-primary">Filter</button>
    @if(request('from') || request('to'))
        <a href="{{ route('admin.submissions.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green ml-2">Clear</a>
    @endif
</form>

<div class="ssbc-admin-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="ssbc-admin-thead">
            <tr>
                <th class="text-left px-4 py-3">Date</th>
                <th class="text-left px-4 py-3">Applicant</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $sub)
                <tr class="ssbc-admin-row">
                    <td class="px-4 py-3 text-ssbc-dark/70 whitespace-nowrap">{{ $sub->submitted_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $sub->display_name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="ssbc-status-badge ssbc-status-{{ $sub->status }}">{{ ucfirst(str_replace('_', ' ', $sub->status)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.submissions.show', $sub) }}" class="text-xs uppercase tracking-wider text-ssbc-green hover:text-ssbc-gold">View →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-ssbc-sage">No submissions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $submissions->links() }}</div>
@endsection
