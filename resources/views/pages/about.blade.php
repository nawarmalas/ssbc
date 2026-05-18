@extends('layouts.app')

@section('title', __('about.hero.heading') . ' — ' . __('common.site_name'))

@section('content')
@php $locale = app()->getLocale(); @endphp

@include('partials.page-hero', [
    'eyebrow' => __('about.hero.eyebrow'),
    'heading' => __('about.hero.heading'),
    'body'    => __('home.overview.body'),
])

{{-- Council Overview --}}
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="flex justify-center mb-14">
            <img
                src="{{ asset('images/logos/logo-light.png') }}"
                alt="{{ __('common.site_name') }}"
                class="h-20 md:h-24 w-auto"
                width="800" height="346"
                loading="lazy">
        </div>
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ __('home.overview.eyebrow') }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green max-w-3xl leading-tight">
            {{ __('home.overview.heading') }}
        </h2>
        <p class="mt-6 max-w-3xl text-ssbc-dark/80 leading-relaxed">{{ __('home.overview.body') }}</p>
    </div>
</section>

{{-- Mission / Vision / Values --}}
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

{{-- Three Implementation Phases --}}
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ __('about.phases.eyebrow') }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green max-w-3xl leading-tight">
            {{ __('about.phases.heading') }}
        </h2>
        <p class="mt-6 max-w-3xl text-ssbc-dark/80 leading-relaxed">{{ __('about.phases.body') }}</p>

        <div class="mt-12 grid md:grid-cols-3 gap-8">
            @foreach((array) __('about.phases.items') as $i => $phase)
                <div class="border-t-2 border-ssbc-gold pt-6 bg-white p-6">
                    <p class="ssbc-eyebrow mb-3">{{ $phase['label'] }}</p>
                    <h3 class="text-xl font-display font-semibold text-ssbc-green mb-3">
                        {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}. {{ $phase['title'] }}
                    </h3>
                    <p class="text-sm text-ssbc-dark/75 leading-relaxed">{{ $phase['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Join CTA --}}
<section class="bg-ssbc-green text-white">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">{{ __('home.cta.eyebrow') }}</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-white max-w-2xl leading-tight">
            {{ __('home.cta.heading') }}
        </h2>
        <p class="mt-6 max-w-2xl text-ssbc-sage leading-relaxed">{{ __('home.cta.body') }}</p>
        <div class="mt-10">
            <a href="{{ route('join.create', ['locale' => $locale]) }}" class="ssbc-btn-outline">
                {{ __('home.cta.button') }}
            </a>
        </div>
    </div>
</section>

@endsection
