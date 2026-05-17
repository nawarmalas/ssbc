@php
    $locale = app()->getLocale();
    $altLocale = $locale === 'ar' ? 'en' : 'ar';
    $currentRoute = request()->route() ? request()->route()->getName() : null;
    $currentRouteParams = request()->route() ? request()->route()->parameters() : [];

    $altUrl = '/'.$altLocale;
    if ($currentRoute) {
        try {
            $altUrl = route($currentRoute, array_merge($currentRouteParams, ['locale' => $altLocale]));
        } catch (\Throwable $e) {
            $altUrl = '/'.$altLocale;
        }
    }
@endphp
<header class="sticky top-0 z-40 bg-white border-b border-ssbc-green/15"
        x-data="{ open: false }">
    <div class="ssbc-container">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('home', ['locale' => $locale]) }}" class="flex items-center gap-2">
                <span class="text-ssbc-green font-display font-bold text-lg tracking-tight">{{ __('common.site_short') }}</span>
                <span class="w-1 h-1 rounded-full bg-ssbc-gold"></span>
            </a>

            <nav class="hidden md:flex items-center gap-8">
                <a href="{{ route('home', ['locale' => $locale]) }}" class="text-sm text-ssbc-dark hover:text-ssbc-green">{{ __('nav.home') }}</a>
                <a href="{{ route('about', ['locale' => $locale]) }}" class="text-sm text-ssbc-dark hover:text-ssbc-green">{{ __('nav.about') }}</a>
                <a href="{{ route('news.index', ['locale' => $locale]) }}" class="text-sm text-ssbc-dark hover:text-ssbc-green">{{ __('nav.news') }}</a>
                <a href="{{ route('join.create', ['locale' => $locale]) }}" class="text-sm text-ssbc-dark hover:text-ssbc-green">{{ __('nav.join') }}</a>
                <a href="{{ route('contact.create', ['locale' => $locale]) }}" class="text-sm text-ssbc-dark hover:text-ssbc-green">{{ __('nav.contact') }}</a>
            </nav>

            <div class="hidden md:flex items-center gap-2 text-sm">
                <a href="{{ $locale === 'en' ? request()->url() : '/en' }}"
                   class="{{ $locale === 'en' ? 'text-ssbc-gold font-semibold' : 'text-ssbc-sage hover:text-ssbc-green' }}">EN</a>
                <span class="text-ssbc-sage/50">|</span>
                <a href="{{ $altUrl }}"
                   class="{{ $locale === 'ar' ? 'text-ssbc-gold font-semibold' : 'text-ssbc-sage hover:text-ssbc-green' }}">AR</a>
            </div>

            <button class="md:hidden text-ssbc-green" @click="open = !open" aria-label="{{ __('nav.menu') }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="1.5"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <div x-show="open" x-cloak class="md:hidden pb-4 space-y-3">
            <a href="{{ route('home', ['locale' => $locale]) }}" class="block text-sm text-ssbc-dark">{{ __('nav.home') }}</a>
            <a href="{{ route('about', ['locale' => $locale]) }}" class="block text-sm text-ssbc-dark">{{ __('nav.about') }}</a>
            <a href="{{ route('news.index', ['locale' => $locale]) }}" class="block text-sm text-ssbc-dark">{{ __('nav.news') }}</a>
            <a href="{{ route('join.create', ['locale' => $locale]) }}" class="block text-sm text-ssbc-dark">{{ __('nav.join') }}</a>
            <a href="{{ route('contact.create', ['locale' => $locale]) }}" class="block text-sm text-ssbc-dark">{{ __('nav.contact') }}</a>
            <div class="pt-3 border-t border-ssbc-green/10 flex gap-3 text-sm">
                <a href="/en" class="{{ $locale === 'en' ? 'text-ssbc-gold font-semibold' : 'text-ssbc-sage' }}">EN</a>
                <a href="/ar" class="{{ $locale === 'ar' ? 'text-ssbc-gold font-semibold' : 'text-ssbc-sage' }}">AR</a>
            </div>
        </div>
    </div>
</header>
