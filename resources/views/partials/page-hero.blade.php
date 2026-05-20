@php
    $padding      = $padding      ?? 'py-20 lg:py-28';
    $headingClass = $headingClass ?? 'max-w-3xl';
    $bodyClass    = $bodyClass    ?? 'max-w-2xl';
@endphp
<section class="bg-ssbc-green text-white">
    <div class="ssbc-container {{ $padding }}">
        @if(!empty($eyebrow))
            <p class="ssbc-eyebrow mb-4">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-4xl lg:text-5xl font-display font-bold leading-tight {{ $headingClass }}">
            {{ $heading }}
        </h1>
        <div class="mt-6 w-12 h-px bg-ssbc-gold"></div>
        @if(!empty($body))
            <p class="mt-4 {{ $bodyClass }} text-ssbc-sage leading-relaxed">{{ $body }}</p>
        @endif
    </div>
</section>
