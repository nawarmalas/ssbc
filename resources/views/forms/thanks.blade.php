@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $title = $formDefinition?->title($locale) ?? __('join.hero.heading');
@endphp

@section('title', $title . ' - ' . __('common.site_name'))

@section('content')
@include('partials.page-hero', [
    'eyebrow' => __('join.hero.eyebrow'),
    'heading' => __('join.thanks_heading'),
    'body' => $title,
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <div class="max-w-2xl mx-auto text-center">
            <p class="text-ssbc-dark/80 leading-relaxed">{{ __('join.thanks_body') }}</p>
        </div>
    </div>
</section>
@endsection
