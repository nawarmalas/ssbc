@extends('layouts.admin')

@section('title', __('admin.contact'))

@section('content')
    <div class="w-12 h-px bg-ssbc-gold mb-4"></div>
    <h1 class="text-2xl font-display font-bold text-ssbc-green mb-8">{{ __('admin.contact') }}</h1>

    <div class="ssbc-admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-ssbc-light text-ssbc-green/80 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3">{{ __('admin.name') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.email_field') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.date') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ssbc-green/10">
                @forelse($submissions as $sub)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $sub->name }}</td>
                        <td class="px-4 py-3 text-ssbc-dark/80">{{ $sub->email }}</td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $sub->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="ssbc-status-badge ssbc-status-{{ $sub->status }}">{{ __('admin.status_'.$sub->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.contact.show', $sub) }}" class="ssbc-link-gold">{{ __('admin.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-ssbc-sage">{{ __('admin.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
