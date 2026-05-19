@extends('layouts.admin')

@section('title', __('admin.dashboard') . ' — ' . __('admin.title'))
@section('page_title', __('admin.overview'))

@section('content')
    @php
        $cards = [
            ['label' => __('admin.published_posts'),     'value' => $stats['published_posts'],     'href' => route('admin.news.index')],
            ['label' => __('admin.pending_submissions'), 'value' => $stats['pending_submissions'], 'href' => route('admin.submissions.index')],
            ['label' => __('admin.new_contact'),         'value' => $stats['new_contact'],         'href' => route('admin.contact.index')],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
        @foreach($cards as $card)
            <a href="{{ $card['href'] }}" class="ssbc-admin-stat-card">
                <p class="ssbc-admin-stat-label">{{ $card['label'] }}</p>
                <p class="ssbc-admin-stat-value">{{ $card['value'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="ssbc-admin-card">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-ssbc-sage">Recent Activity</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="ssbc-admin-thead">
                    <tr>
                        <th class="text-left px-4 py-3">Type</th>
                        <th class="text-left px-4 py-3">Name</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $row)
                        <tr class="ssbc-admin-row">
                            <td class="px-4 py-3">
                                <span class="ssbc-status-badge bg-ssbc-beige/60 text-ssbc-green">{{ $row['type_label'] }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $row['name'] ?: '—' }}</td>
                            <td class="px-4 py-3 text-ssbc-dark/70 whitespace-nowrap">{{ $row['date']?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="ssbc-status-badge ssbc-status-{{ $row['status'] }}">{{ __('admin.status_'.$row['status']) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ $row['url'] }}" class="text-xs uppercase tracking-wider text-ssbc-green hover:text-ssbc-gold">View →</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ssbc-sage">No recent activity.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
