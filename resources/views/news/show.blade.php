@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', $post->title($locale) . ' — ' . __('common.site_name'))
@section('meta_description', $post->excerpt($locale) ?? __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => $post->category ?: __('news.hero.eyebrow'),
    'heading' => $post->title($locale),
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <article class="max-w-3xl mx-auto">
            <div class="flex items-center gap-3 mb-4 text-sm text-ssbc-gold uppercase tracking-wider font-semibold">
                @if($post->published_at)
                    <span>{{ $post->published_at->copy()->timezone(config('app.admin_timezone'))->format('d F Y') }}</span>
                @endif
                @if($post->category)
                    <span class="text-ssbc-sage">·</span>
                    <span class="text-ssbc-sage">{{ $post->category }}</span>
                @endif
            </div>

            <div class="w-full h-px bg-ssbc-gold/30 mb-8"></div>

            @if($post->featuredImageUrl())
                <div class="aspect-video bg-ssbc-beige mb-10 overflow-hidden">
                    <img src="{{ $post->featuredImageUrl() }}" alt="" class="w-full h-full object-cover">
                </div>
            @endif

            <div class="prose prose-lg max-w-none prose-headings:font-display prose-headings:text-ssbc-green prose-a:text-ssbc-gold">
                {!! $post->content($locale) !!}
            </div>

            <div class="my-12 w-full h-px bg-ssbc-gold/30"></div>

            <a href="{{ route('news.index', ['locale' => $locale]) }}" class="ssbc-link-gold text-sm">
                {{ __('news.back_to_news') }}
            </a>
        </article>
    </div>
</section>

@endsection
