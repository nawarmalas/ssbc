@extends('layouts.app')

@section('title', __('news.hero.heading') . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => __('news.hero.eyebrow'),
    'heading' => __('news.hero.heading'),
])

<section class="bg-white">
    <div class="ssbc-container py-20">
        @if($posts->isEmpty())
            <p class="text-ssbc-dark/60">{{ __('news.empty') }}</p>
        @else
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($posts as $post)
                    @include('partials.news-card', ['post' => $post])
                @endforeach
            </div>

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</section>

@endsection
