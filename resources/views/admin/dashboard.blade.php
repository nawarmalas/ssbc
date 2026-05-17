@extends('layouts.admin')

@section('title', __('admin.dashboard') . ' — ' . __('admin.title'))

@section('content')
    <div class="w-12 h-px bg-ssbc-gold mb-4"></div>
    <h1 class="text-2xl font-display font-bold text-ssbc-green mb-8">{{ __('admin.overview') }}</h1>

    @php
        $cards = [
            ['label' => __('admin.published_posts'),  'value' => $stats['published_posts'],  'href' => route('admin.news.index')],
            ['label' => __('admin.new_join'),         'value' => $stats['new_join'],         'href' => route('admin.join.index')],
            ['label' => __('admin.new_contact'),      'value' => $stats['new_contact'],      'href' => route('admin.contact.index')],
            ['label' => __('admin.new_membership'),   'value' => $stats['new_membership'],   'href' => route('admin.membership.index')],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($cards as $card)
            <a href="{{ $card['href'] }}" class="ssbc-admin-card p-6 hover:border-ssbc-gold transition-colors block">
                <p class="ssbc-eyebrow mb-4">{{ $card['label'] }}</p>
                <p class="text-4xl font-display font-bold text-ssbc-green">{{ $card['value'] }}</p>
            </a>
        @endforeach
    </div>
@endsection
