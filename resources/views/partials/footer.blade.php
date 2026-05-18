@php $locale = app()->getLocale(); @endphp
<footer class="bg-ssbc-green text-white mt-auto">
    <div class="w-full h-px bg-ssbc-gold"></div>
    <div class="ssbc-container py-14">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <div>
                <a href="{{ route('home', ['locale' => $locale]) }}"
                   class="inline-block mb-1"
                   aria-label="{{ __('common.site_name') }}">
                    <img
                        src="{{ asset('images/logos/logo-on-dark.jpeg') }}"
                        alt="{{ __('common.site_name') }}"
                        class="h-9 md:h-12 w-auto"
                        loading="lazy">
                </a>
                <div class="w-full h-px bg-ssbc-gold/50 mb-4"></div>
                <p class="text-sm text-ssbc-sage leading-relaxed">
                    {{ $siteSettings->footerDesc($locale) ?: __('common.site_name') }}
                </p>
            </div>

            <div>
                <h3 class="ssbc-eyebrow mb-4">{{ __('footer.nav_heading') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home', ['locale' => $locale]) }}" class="text-white/80 hover:text-ssbc-gold">{{ __('nav.home') }}</a></li>
                    <li><a href="{{ route('about', ['locale' => $locale]) }}" class="text-white/80 hover:text-ssbc-gold">{{ __('nav.about') }}</a></li>
                    <li><a href="{{ route('news.index', ['locale' => $locale]) }}" class="text-white/80 hover:text-ssbc-gold">{{ __('nav.news') }}</a></li>
                    <li><a href="{{ route('join.create', ['locale' => $locale]) }}" class="text-white/80 hover:text-ssbc-gold">{{ __('nav.join') }}</a></li>
                    <li><a href="{{ route('contact.create', ['locale' => $locale]) }}" class="text-white/80 hover:text-ssbc-gold">{{ __('nav.contact') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="ssbc-eyebrow mb-4">{{ __('footer.contact_heading') }}</h3>
                <ul class="space-y-2 text-sm text-white/80">
                    @if($siteSettings->contact_email)
                        <li><a href="mailto:{{ $siteSettings->contact_email }}" class="hover:text-ssbc-gold">{{ $siteSettings->contact_email }}</a></li>
                    @endif
                    @if($siteSettings->contact_phone)
                        <li>{{ $siteSettings->contact_phone }}</li>
                    @endif
                    @if($siteSettings->address($locale))
                        <li class="whitespace-pre-line">{{ $siteSettings->address($locale) }}</li>
                    @endif
                </ul>

                @if($siteSettings->linkedin_url || $siteSettings->twitter_url)
                <div class="mt-4 flex gap-4 text-sm">
                    @if($siteSettings->linkedin_url)
                        <a href="{{ $siteSettings->linkedin_url }}" class="text-white/80 hover:text-ssbc-gold" target="_blank" rel="noopener">LinkedIn</a>
                    @endif
                    @if($siteSettings->twitter_url)
                        <a href="{{ $siteSettings->twitter_url }}" class="text-white/80 hover:text-ssbc-gold" target="_blank" rel="noopener">X / Twitter</a>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-white/10 text-xs text-ssbc-sage">
            {{ __('common.copyright', ['year' => now()->year]) }}
        </div>
    </div>
</footer>
