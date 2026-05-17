@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', __('join.thanks_heading') . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => __('join.hero.eyebrow'),
    'heading' => __('join.thanks_heading'),
])

<section class="bg-white">
    <div class="ssbc-container py-20">
        <div class="max-w-2xl">
            <p class="text-ssbc-dark/80 leading-relaxed">{{ __('join.thanks_body') }}</p>
            <div class="mt-10">
                <a href="{{ route('home', ['locale' => $locale]) }}" class="ssbc-btn-outline-dark">
                    ← {{ __('join.return_home') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
