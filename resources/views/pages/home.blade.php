@extends('layouts.app')

@section('title', __('common.site_name'))

@section('content')
@php $locale = app()->getLocale(); @endphp

{{-- 1. Hero — signing ceremony photo with dark green overlay --}}
<section class="relative min-h-[480px] lg:min-h-[640px] flex items-center text-white overflow-hidden">

    {{-- Background photo --}}
    <div class="absolute inset-0 z-0">
        <img
            src="{{ asset('images/logos/hero-bg.jpeg') }}"
            alt=""
            class="w-full h-full object-cover object-center"
            aria-hidden="true">
        {{-- Dark green overlay keeps the brand colour and ensures text contrast --}}
        <div class="absolute inset-0 bg-ssbc-green/85"></div>
    </div>

    {{-- Content --}}
    <div class="relative z-10 w-full">
        <div class="ssbc-container py-24 lg:py-36">
            <p class="ssbc-eyebrow mb-6">{{ __('home.hero.eyebrow') }}</p>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold leading-[1.1] max-w-4xl">
                {{ __('home.hero.headline') }}
            </h1>

            <p class="mt-6 text-lg text-ssbc-sage font-display max-w-3xl" dir="rtl" lang="ar">
                {{ __('home.hero.tagline') }}
            </p>

            <div class="mt-8 w-12 h-px bg-ssbc-gold"></div>

            <p class="mt-6 max-w-2xl text-white/85 leading-relaxed text-base">
                {{ __('home.hero.body') }}
            </p>

            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ route('about', ['locale' => $locale]) }}" class="ssbc-btn-primary">
                    {{ __('home.hero.cta_primary') }}
                </a>
                <a href="{{ route('contact.create', ['locale' => $locale]) }}" class="ssbc-btn-outline">
                    {{ __('home.hero.cta_secondary') }}
                </a>
            </div>
        </div>
    </div>

    <span class="absolute bottom-3 right-4 text-white/30 text-xs z-10">SSBC, 2026</span>
</section>

{{-- 2. Council Overview --}}
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ __('home.overview.eyebrow') }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green max-w-3xl leading-tight">
            {{ __('home.overview.heading') }}
        </h2>
        <p class="mt-6 max-w-3xl text-ssbc-dark/80 leading-relaxed">
            {{ __('home.overview.body') }}
        </p>
    </div>
</section>

{{-- 3. Mission · Vision · Values --}}
<section class="bg-ssbc-green text-white">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ __('home.mvv.eyebrow') }}</p>

        <div class="mt-10 grid md:grid-cols-2 gap-10">
            <div class="border-l-2 border-ssbc-gold pl-6">
                <p class="ssbc-eyebrow mb-3">{{ __('home.mvv.mission_label') }}</p>
                <p class="text-white/90 leading-relaxed">{{ __('home.mvv.mission') }}</p>
            </div>
            <div class="border-l-2 border-ssbc-gold pl-6">
                <p class="ssbc-eyebrow mb-3">{{ __('home.mvv.vision_label') }}</p>
                <p class="text-white/90 leading-relaxed">{{ __('home.mvv.vision') }}</p>
            </div>
        </div>

        <div class="my-14 w-full h-px bg-ssbc-gold/30"></div>

        <p class="ssbc-eyebrow mb-6">{{ __('home.mvv.values_label') }}</p>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            @foreach((array) __('home.mvv.values') as $value)
                <div class="ssbc-value-card">
                    <p class="text-sm text-white/85 leading-relaxed">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- 4. Strategic Pillars --}}
<section class="bg-white">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ __('home.pillars.eyebrow') }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green max-w-3xl leading-tight">
            {{ __('home.pillars.heading') }}
        </h2>
        <p class="mt-6 max-w-3xl text-ssbc-dark/75 leading-relaxed">
            {{ __('home.pillars.body') }}
        </p>

        <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-10">
            @foreach((array) __('home.pillars.items') as $item)
                <div class="ssbc-pillar-card">
                    <h3 class="text-lg font-display font-semibold text-ssbc-green mb-2">{{ $item['title'] }}</h3>
                    <p class="text-sm text-ssbc-dark/75 leading-relaxed">{{ $item['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- 5. Latest News --}}
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="flex items-end justify-between mb-10 gap-4">
            <div>
                <div class="ssbc-rule"></div>
                <p class="ssbc-eyebrow mb-3">{{ __('home.news.eyebrow') }}</p>
                <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green leading-tight">
                    {{ __('home.news.heading') }}
                </h2>
            </div>
            <a href="{{ route('news.index', ['locale' => $locale]) }}" class="ssbc-link-gold text-sm shrink-0">
                {{ __('home.news.view_all') }} →
            </a>
        </div>

        @if($posts->isEmpty())
            <p class="text-ssbc-dark/60">{{ __('home.news.empty') }}</p>
        @else
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($posts as $post)
                    @include('partials.news-card', ['post' => $post])
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- 6. Join CTA Banner --}}
<section class="bg-ssbc-green text-white">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ __('home.cta.eyebrow') }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-white max-w-2xl leading-tight">
            {{ __('home.cta.heading') }}
        </h2>
        <p class="mt-6 max-w-2xl text-ssbc-sage leading-relaxed">
            {{ __('home.cta.body') }}
        </p>
        <div class="mt-10">
            <a href="{{ route('join.create', ['locale' => $locale]) }}" class="ssbc-btn-outline">
                {{ __('home.cta.button') }}
            </a>
        </div>
    </div>
</section>

@endsection
