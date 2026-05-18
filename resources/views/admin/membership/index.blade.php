@extends('layouts.admin')

@section('title', __('admin.membership'))
@section('page_title', __('admin.membership'))

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
                @forelse($applications as $app)
                    <tr class="ssbc-admin-row">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ssbc-dark">{{ $app->full_name_en }}</p>
                            <p class="text-xs text-ssbc-sage" dir="rtl" lang="ar">{{ $app->full_name_ar }}</p>
                        </td>
                        <td class="px-4 py-3 text-ssbc-dark/80">{{ $app->email }}</td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $app->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="ssbc-status-badge ssbc-status-{{ $app->status }}">{{ __('admin.status_'.$app->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.membership.show', $app) }}" class="text-xs uppercase tracking-wider text-ssbc-green hover:text-ssbc-gold">{{ __('admin.view') }} →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-ssbc-sage">{{ __('admin.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
