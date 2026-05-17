@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', __('contact.thanks_heading') . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => __('contact.hero.eyebrow'),
    'heading' => __('contact.thanks_heading'),
])

<section class="bg-white">
    <div class="ssbc-container py-20">
        <div class="max-w-2xl">
            <p class="text-ssbc-dark/80 leading-relaxed">{{ __('contact.thanks_body') }}</p>
            <div class="mt-10">
                <a href="{{ route('home', ['locale' => $locale]) }}" class="ssbc-btn-outline-dark">
                    ← {{ __('contact.return_home') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
