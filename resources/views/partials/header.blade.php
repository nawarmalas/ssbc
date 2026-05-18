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

<header
    x-data="{ open: false, scrolled: false }"
    x-init="scrolled = window.scrollY > 20"
    @scroll.window="scrolled = window.scrollY > 20"
    :class="scrolled
        ? 'bg-white border-b border-ssbc-green/15 shadow-sm'
        : 'bg-ssbc-green border-b border-transparent'"
    class="sticky top-0 z-40 transition-all duration-300">

    <div class="ssbc-container">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ route('home', ['locale' => $locale]) }}"
               class="flex flex-col items-start shrink-0"
               aria-label="{{ __('common.site_name') }}">
                <img
                    src="{{ asset('images/logos/logo-light.png') }}"
                    alt="{{ __('common.site_name') }}"
                    class="h-10 sm:h-11 w-auto"
                    width="800" height="346"
                    loading="eager">
                <span class="block w-full h-px mt-0.5 transition-colors duration-300"
                      :class="scrolled ? 'bg-ssbc-gold/70' : 'bg-ssbc-gold/40'"></span>
            </a>

            {{-- Desktop Nav --}}
            <nav class="hidden md:flex items-center gap-8" aria-label="{{ __('nav.menu') }}">
                @foreach([
                    ['route' => 'home',          'label' => 'nav.home'],
                    ['route' => 'about',         'label' => 'nav.about'],
                    ['route' => 'news.index',    'label' => 'nav.news'],
                    ['route' => 'join.create',   'label' => 'nav.join'],
                    ['route' => 'contact.create','label' => 'nav.contact'],
                ] as $link)
                <a href="{{ route($link['route'], ['locale' => $locale]) }}"
                   class="text-sm transition-colors duration-300"
                   :class="scrolled ? 'text-ssbc-dark hover:text-ssbc-green' : 'text-white hover:text-ssbc-gold'">
                    {{ __($link['label']) }}
                </a>
                @endforeach
            </nav>

            {{-- Language Switcher (desktop) --}}
            <div class="hidden md:flex items-center gap-2 text-sm">
                @if($locale === 'en')
                    <span class="text-ssbc-gold font-semibold">EN</span>
                @else
                    <a href="/en"
                       class="transition-colors duration-300"
                       :class="scrolled ? 'text-ssbc-sage hover:text-ssbc-green' : 'text-white/70 hover:text-white'">EN</a>
                @endif
                <span class="transition-colors duration-300"
                      :class="scrolled ? 'text-ssbc-sage/50' : 'text-white/30'">|</span>
                @if($locale === 'ar')
                    <span class="text-ssbc-gold font-semibold">AR</span>
                @else
                    <a href="{{ $altUrl }}"
                       class="transition-colors duration-300"
                       :class="scrolled ? 'text-ssbc-sage hover:text-ssbc-green' : 'text-white/70 hover:text-white'">AR</a>
                @endif
            </div>

            {{-- Mobile Hamburger --}}
            <button class="md:hidden transition-colors duration-300"
                    :class="scrolled ? 'text-ssbc-green' : 'text-white'"
                    @click="open = !open"
                    aria-label="{{ __('nav.menu') }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="1.5"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="open" x-cloak
             class="md:hidden pb-4 pt-2 space-y-3 border-t"
             :class="scrolled ? 'border-ssbc-green/10' : 'border-white/20'">
            @foreach([
                ['route' => 'home',          'label' => 'nav.home'],
                ['route' => 'about',         'label' => 'nav.about'],
                ['route' => 'news.index',    'label' => 'nav.news'],
                ['route' => 'join.create',   'label' => 'nav.join'],
                ['route' => 'contact.create','label' => 'nav.contact'],
            ] as $link)
            <a href="{{ route($link['route'], ['locale' => $locale]) }}"
               class="block text-sm transition-colors duration-300"
               :class="scrolled ? 'text-ssbc-dark' : 'text-white'">{{ __($link['label']) }}</a>
            @endforeach
            <div class="pt-3 flex gap-3 text-sm border-t"
                 :class="scrolled ? 'border-ssbc-green/10' : 'border-white/20'">
                @if($locale === 'en')
                    <span class="text-ssbc-gold font-semibold">EN</span>
                @else
                    <a href="/en"
                       class="transition-colors duration-300"
                       :class="scrolled ? 'text-ssbc-sage' : 'text-white/70'">EN</a>
                @endif
                @if($locale === 'ar')
                    <span class="text-ssbc-gold font-semibold">AR</span>
                @else
                    <a href="/ar"
                       class="transition-colors duration-300"
                       :class="scrolled ? 'text-ssbc-sage' : 'text-white/70'">AR</a>
                @endif
            </div>
        </div>
    </div>
</header>
