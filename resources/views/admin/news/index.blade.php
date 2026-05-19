@extends('layouts.admin')

@section('title', __('admin.news') . ' — ' . __('admin.title'))
@section('page_title', __('admin.news'))

@section('content')
    <div class="flex items-center justify-end mb-6">
        <a href="{{ route('admin.news.create') }}" class="ssbc-admin-btn-primary">+ {{ __('admin.create') }}</a>
    </div>

    <div class="ssbc-admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="ssbc-admin-thead">
                <tr>
                    <th class="text-left px-4 py-3">{{ __('admin.news_title_en') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.news_category') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.status') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.news_published_at') }}</th>
                    <th class="text-right px-4 py-3">{{ __('admin.edit') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    <tr class="ssbc-admin-row">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ssbc-dark">{{ $post->title_en }}</p>
                            <p class="text-xs text-ssbc-sage" dir="rtl" lang="ar">{{ $post->title_ar }}</p>
                        </td>
                        <td class="px-4 py-3 text-ssbc-dark/70">{{ $post->category ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="ssbc-status-badge ssbc-status-{{ $post->status }}">
                                {{ __('admin.status_'.$post->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-ssbc-dark/70">
                            {{ $post->published_at ? $post->published_at->copy()->timezone(config('app.admin_timezone'))->format('d M Y, g:i A') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.news.edit', $post) }}" class="ssbc-link-gold">{{ __('admin.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-ssbc-sage">{{ __('admin.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
