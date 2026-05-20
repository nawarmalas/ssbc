{{-- resources/views/pages/partials/sectors.blade.php --}}
@php
    $locale = app()->getLocale();
    $site = App\Models\SiteSetting::current();
@endphp

@if($sectors->isNotEmpty())
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">
            {{ $site->homeContent($locale, 'sectors.eyebrow', 'Economic Sectors') }}
        </p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green max-w-3xl leading-tight">
            {{ $site->homeContent($locale, 'sectors.heading', 'القطاعات الاقتصادية') }}
        </h2>
        <p class="mt-6 max-w-3xl text-ssbc-dark/75 leading-relaxed">
            {{ $site->homeContent($locale, 'sectors.body', 'يغطي المجلس خمسة عشر قطاعا اقتصاديا استراتيجيا تشكل محاور الشراكة بين سوريا والمملكة العربية السعودية.') }}
        </p>

        <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-10">
            @foreach($sectors as $sector)
                <div class="ssbc-pillar-card">
                    <h3 class="text-lg font-display font-semibold text-ssbc-green mb-2">
                        {{ $sector->name() }}
                    </h3>
                    <p class="text-sm text-ssbc-dark/75 leading-relaxed">
                        {{ $sector->description() }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
