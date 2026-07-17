@php
    $locale = app()->getLocale();
    // Heading level is contextual: h3 under a section h2 (home), h2 when the
    // card follows the page h1 directly (news index) — avoids skipped levels.
    $headingTag = $headingTag ?? 'h3';
    // First card on the news index is the likely LCP element — load it
    // eagerly; everything else stays lazy.
    $eager = $eager ?? false;
    $cardDate = $post->published_at
        ? \App\Support\NewsDate::format(
            $post->published_at->copy()->timezone(config('app.admin_timezone')),
            $locale,
            true
          )
        : null;
@endphp
<a href="{{ route('news.show', ['locale' => $locale, 'slug' => $post->slug]) }}"
   class="group block border border-ssbc-green/10 bg-white hover:border-ssbc-gold transition-colors">

    @if($post->featuredImageUrl())
        <div class="aspect-video bg-ssbc-beige overflow-hidden">
            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title($locale) }}"
                 class="w-full h-full object-cover"
                 @if($eager) loading="eager" fetchpriority="high" @else loading="lazy" @endif decoding="async">
        </div>
    @else
        <div class="aspect-video bg-ssbc-beige flex items-center justify-center">
            <span class="text-ssbc-sage text-xs uppercase tracking-widest">SSBC</span>
        </div>
    @endif

    <div class="p-6">
        <div class="flex items-center gap-3 mb-3">
            @if($cardDate)
                <span class="inline-block bg-ssbc-gold/15 text-ssbc-gold-deep text-xs px-2 py-1 font-semibold uppercase tracking-wider">
                    {{ $cardDate }}
                </span>
            @endif
            @if($post->category)
                <span class="text-xs text-ssbc-sage-deep uppercase tracking-wider">{{ $post->category }}</span>
            @endif
        </div>

        <{{ $headingTag }} class="text-lg font-display font-semibold text-ssbc-green leading-tight mb-2 group-hover:text-ssbc-dark">
            {{ $post->title($locale) }}
        </{{ $headingTag }}>

        @if($post->excerpt($locale))
            <p class="text-sm text-ssbc-dark/70 leading-relaxed line-clamp-3">{{ $post->excerpt($locale) }}</p>
        @endif

        <p class="mt-4 text-sm font-semibold text-ssbc-gold-deep">{{ __('common.read_more') }} →</p>
    </div>
</a>
