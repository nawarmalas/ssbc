@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', __('join.thanks_heading') . ' — ' . __('common.site_name'))

@section('content')

{{-- Green confirmation header --}}
<section class="bg-ssbc-green text-white">
    <div class="ssbc-container py-20 text-center">
        <p class="ssbc-eyebrow text-ssbc-gold mb-4">{{ __('join.hero.eyebrow') }}</p>
        <h1 class="text-3xl md:text-4xl font-display font-bold">{{ __('join.thanks_heading') }}</h1>
        <div class="w-12 h-px bg-ssbc-gold mx-auto mt-6"></div>
    </div>
</section>

<section class="bg-white">
    <div class="ssbc-container py-16">
        <div class="max-w-2xl mx-auto text-center">
            <p class="text-ssbc-dark/80 leading-relaxed">{{ __('join.thanks_body') }}</p>
            <p class="text-ssbc-dark/80 leading-relaxed mt-4" dir="rtl" lang="ar">{{ __('join.thanks_body_ar') }}</p>

            <div class="mt-10">
                <a href="{{ route('home', ['locale' => $locale]) }}" class="ssbc-btn-outline-dark">
                    ← {{ __('join.return_home') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
