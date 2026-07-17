@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $blocks = $post->contentBlocks->where('locale', $locale);
    $formattedDate = $post->published_at
        ? \App\Support\NewsDate::format(
            $post->published_at->copy()->timezone(config('app.admin_timezone')),
            $locale
          )
        : null;
@endphp

@section('title', $post->title($locale) . ' — ' . __('common.site_name'))
@section('meta_description', $post->excerpt($locale) ?: __('seo.news_index.description'))
@if($post->featuredImageUrl())
    @section('og_image', App\Support\Seo::absoluteUrl($post->featuredImageUrl()))
@endif

@section('content')

@include('partials.page-hero', [
    'eyebrow' => $post->category ?: __('news.hero.eyebrow'),
    'heading' => $post->title($locale),
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <article class="max-w-3xl mx-auto">
            <div class="flex items-center gap-3 mb-4 text-sm text-ssbc-gold-deep uppercase tracking-wider font-semibold">
                @if($formattedDate)
                    <span>{{ $formattedDate }}</span>
                @endif
                @if($post->category)
                    <span class="text-ssbc-sage-deep">·</span>
                    <span class="text-ssbc-sage-deep">{{ $post->category }}</span>
                @endif
            </div>

            <div class="w-full h-px bg-ssbc-gold/30 mb-8"></div>

            @if($post->featuredImageUrl())
                <div class="aspect-video bg-ssbc-beige mb-10 overflow-hidden">
                    {{-- LCP image: load eagerly at high priority --}}
                    <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title($locale) }}"
                         class="w-full h-full object-cover"
                         loading="eager" fetchpriority="high" decoding="async">
                </div>
            @endif

            {{-- Content blocks (new articles) or legacy body (old articles) --}}
            @if($blocks->isNotEmpty())
                <div class="{{ $locale === 'ar' ? 'rtl' : '' }}">
                    @foreach($blocks as $block)
                        @if($block->type === 'text')
                            <div class="news-text-block article-content {{ $locale === 'ar' ? 'rtl' : '' }}">
                                {!! $block->content !!}
                            </div>
                        @elseif($block->type === 'image' && $block->image_path)
                            <figure class="news-image-block">
                                <a href="{{ Storage::url($block->image_path) }}"
                                   data-lightbox="article-gallery"
                                   data-title="{{ $block->{'caption_'.$locale} }}">
                                    @php $dim = \App\Support\ImageDimensions::forPublic($block->image_path); @endphp
                                    <img src="{{ Storage::url($block->image_path) }}"
                                         alt="{{ $block->{'caption_'.$locale} ?: $post->title($locale) }}"
                                         @if($dim) width="{{ $dim[0] }}" height="{{ $dim[1] }}" @endif
                                         loading="lazy" decoding="async">
                                </a>
                                @if($block->{'caption_'.$locale})
                                    <figcaption>{{ $block->{'caption_'.$locale} }}</figcaption>
                                @endif
                            </figure>
                        @endif
                    @endforeach
                </div>
            @else
                {{-- Legacy fallback for articles without blocks --}}
                <div class="prose prose-lg max-w-none prose-headings:font-display prose-headings:text-ssbc-green prose-a:text-ssbc-gold article-content {{ $locale === 'ar' ? 'rtl' : '' }}">
                    {!! $post->content($locale) !!}
                </div>
            @endif

            @if($post->images->isNotEmpty())
                <div class="mt-10">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($post->images as $img)
                            <a href="{{ $img->url() }}" target="_blank" rel="noopener"
                               class="block aspect-video overflow-hidden bg-ssbc-beige border border-ssbc-green/10 hover:border-ssbc-gold transition-colors">
                                <img src="{{ $img->url() }}" alt="{{ $post->title($locale) }} — {{ __('news.gallery_photo') }} {{ $loop->iteration }}"
                                     class="w-full h-full object-cover" loading="lazy" decoding="async">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="my-12 w-full h-px bg-ssbc-gold/30"></div>

            <a href="{{ route('news.index', ['locale' => $locale]) }}" class="ssbc-link-gold text-sm">
                {{ __('news.back_to_news') }}
            </a>
        </article>
    </div>
</section>

@endsection
