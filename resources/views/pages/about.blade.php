@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $site = App\Models\SiteSetting::current();
@endphp

@section('title', $site->aboutContent($locale, 'hero.heading', __('about.hero.heading')) . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => $site->aboutContent($locale, 'hero.eyebrow', __('about.hero.eyebrow')),
    'heading' => $site->aboutContent($locale, 'hero.heading', __('about.hero.heading')),
    'body'    => $site->homeContent($locale, 'overview.body', __('home.overview.body')),
])

{{-- Council Overview --}}
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="flex justify-center mb-14">
            <img
                src="{{ asset('images/logos/logo-two-tone.png') }}"
                alt="{{ __('common.site_name') }}"
                width="720" height="347"
                class="h-20 md:h-24 w-auto"
                loading="lazy" decoding="async">
        </div>
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ $site->homeContent($locale, 'overview.eyebrow', __('home.overview.eyebrow')) }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green max-w-3xl leading-tight">
            {{ $site->homeContent($locale, 'overview.heading', __('home.overview.heading')) }}
        </h2>
        <p class="mt-6 max-w-3xl text-ssbc-dark/80 leading-relaxed">{{ $site->homeContent($locale, 'overview.body', __('home.overview.body')) }}</p>
    </div>
</section>

{{-- Mission / Vision / Values --}}
<section class="bg-ssbc-green text-white">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ $site->homeContent($locale, 'mvv.eyebrow', __('home.mvv.eyebrow')) }}</p>

        <div class="mt-10 grid md:grid-cols-2 gap-10">
            <div class="border-l-2 border-ssbc-gold pl-6">
                <p class="ssbc-eyebrow mb-3">{{ $site->homeContent($locale, 'mvv.mission_label', __('home.mvv.mission_label')) }}</p>
                <p class="text-white/90 leading-relaxed">{{ $site->homeContent($locale, 'mvv.mission', __('home.mvv.mission')) }}</p>
            </div>
            <div class="border-l-2 border-ssbc-gold pl-6">
                <p class="ssbc-eyebrow mb-3">{{ $site->homeContent($locale, 'mvv.vision_label', __('home.mvv.vision_label')) }}</p>
                <p class="text-white/90 leading-relaxed">{{ $site->homeContent($locale, 'mvv.vision', __('home.mvv.vision')) }}</p>
            </div>
        </div>

        <div class="my-14 w-full h-px bg-ssbc-gold/30"></div>

        <p class="ssbc-eyebrow mb-6">{{ $site->homeContent($locale, 'mvv.values_label', __('home.mvv.values_label')) }}</p>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            @foreach($site->homeList($locale, 'mvv.values', (array) __('home.mvv.values')) as $value)
                <div class="ssbc-value-card">
                    <p class="text-sm text-white/85 leading-relaxed">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Board Members --}}
@include('pages.partials.board-members')

{{-- Strategic Pillars --}}
@include('pages.partials.strategic-pillars')

{{-- Three Implementation Phases --}}
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ $site->aboutContent($locale, 'phases.eyebrow', __('about.phases.eyebrow')) }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green max-w-3xl leading-tight">
            {{ $site->aboutContent($locale, 'phases.heading', __('about.phases.heading')) }}
        </h2>
        <p class="mt-6 max-w-3xl text-ssbc-dark/80 leading-relaxed">{{ $site->aboutContent($locale, 'phases.body', __('about.phases.body')) }}</p>

        <div class="mt-12 grid md:grid-cols-3 gap-8">
            @foreach($site->aboutList($locale, 'phases.items', (array) __('about.phases.items')) as $i => $phase)
                <div class="border-t-2 border-ssbc-gold pt-6 bg-white p-6">
                    <p class="ssbc-eyebrow mb-3">{{ $phase['label'] ?? '' }}</p>
                    <h3 class="text-xl font-display font-semibold text-ssbc-green mb-3">
                        {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}. {{ $phase['title'] ?? '' }}
                    </h3>
                    <p class="text-sm text-ssbc-dark/75 leading-relaxed">{{ $phase['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Join CTA --}}
<section class="bg-ssbc-green text-white">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ $site->homeContent($locale, 'cta.eyebrow', __('home.cta.eyebrow')) }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-white max-w-2xl leading-tight">
            {{ $site->homeContent($locale, 'cta.heading', __('home.cta.heading')) }}
        </h2>
        <p class="mt-6 max-w-2xl text-ssbc-sage leading-relaxed">{{ $site->homeContent($locale, 'cta.body', __('home.cta.body')) }}</p>
        <div class="mt-10">
            <a href="{{ route('join.create', ['locale' => $locale]) }}" class="ssbc-btn-outline">
                {{ $site->homeContent($locale, 'cta.button', __('home.cta.button')) }}
            </a>
        </div>
    </div>
</section>

@endsection
