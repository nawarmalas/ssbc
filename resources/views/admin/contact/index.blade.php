@extends('layouts.admin')

@section('title', __('admin.contact'))
@section('page_title', __('admin.contact'))

@section('content')
    <div class="ssbc-admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="ssbc-admin-thead">
                <tr>
                    <th class="text-left px-4 py-3">{{ __('admin.name') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.email_field') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.date') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $sub)
                    <tr class="ssbc-admin-row">
                        <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $sub->name }}</td>
                        <td class="px-4 py-3 text-ssbc-dark/80">{{ $sub->email }}</td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $sub->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="ssbc-status-badge ssbc-status-{{ $sub->status }}">{{ __('admin.status_'.$sub->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.contact.show', $sub) }}" class="text-xs uppercase tracking-wider text-ssbc-green hover:text-ssbc-gold">{{ __('admin.view') }} →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-ssbc-sage">{{ __('admin.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
