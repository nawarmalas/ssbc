@extends('layouts.admin')

@section('title', __('admin.news') . ' — ' . __('admin.title'))

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="w-12 h-px bg-ssbc-gold mb-4"></div>
            <h1 class="text-2xl font-display font-bold text-ssbc-green">{{ __('admin.news') }}</h1>
        </div>
        <a href="{{ route('admin.news.create') }}" class="ssbc-btn-primary">+ {{ __('admin.create') }}</a>
    </div>

    <div class="ssbc-admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-ssbc-light text-ssbc-green/80 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3">{{ __('admin.news_title_en') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.news_category') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.status') }}</th>
                    <th class="text-left px-4 py-3">{{ __('admin.news_published_at') }}</th>
                    <th class="text-right px-4 py-3">{{ __('admin.edit') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ssbc-green/10">
                @forelse($posts as $post)
                    <tr>
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
                            {{ $post->published_at ? $post->published_at->format('d M Y') : '—' }}
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
