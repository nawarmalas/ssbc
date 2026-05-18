@extends('layouts.admin')

@section('title', 'Submissions')

@section('content')
<div class="w-12 h-px bg-ssbc-gold mb-4"></div>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-display font-bold text-ssbc-green">Submissions</h1>
    <a href="{{ route('admin.submissions.export', request()->only('from','to')) }}"
       class="ssbc-btn-outline-dark text-sm">Export Excel ↓</a>
</div>

{{-- Date filter --}}
<form method="GET" class="flex gap-3 items-end mb-6">
    <div>
        <label class="ssbc-label text-xs">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="ssbc-input text-sm">
    </div>
    <div>
        <label class="ssbc-label text-xs">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="ssbc-input text-sm">
    </div>
    <button type="submit" class="ssbc-btn-primary text-sm">Filter</button>
    @if(request('from') || request('to'))
        <a href="{{ route('admin.submissions.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">Clear</a>
    @endif
</form>

<div class="ssbc-admin-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-ssbc-light text-ssbc-green/80 text-xs uppercase tracking-wider">
            <tr>
                <th class="text-left px-4 py-3">Date</th>
                <th class="text-left px-4 py-3">Applicant</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ssbc-green/10">
            @forelse($submissions as $sub)
                <tr>
                    <td class="px-4 py-3 text-ssbc-dark/70 whitespace-nowrap">{{ $sub->submitted_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $sub->display_name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="ssbc-status-badge ssbc-status-{{ $sub->status }}">{{ ucfirst(str_replace('_', ' ', $sub->status)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.submissions.show', $sub) }}" class="ssbc-link-gold text-sm">View</a>
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
