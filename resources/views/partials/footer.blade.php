@php $locale = app()->getLocale(); @endphp
<footer class="bg-ssbc-green text-white mt-auto">
    <div class="w-full h-px bg-ssbc-gold"></div>
    <div class="ssbc-container py-14">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
            <div class="md:col-span-2">
                <a href="{{ route('home', ['locale' => $locale]) }}"
                   class="inline-block mb-1"
                   aria-label="{{ __('common.site_name') }}">
                    <img
                        src="{{ asset('images/logos/logo-one-tone.png') }}"
                        alt="{{ __('common.site_name') }}"
                        class="h-16 md:h-20 w-auto"
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
                <ul class="space-y-3 text-sm text-white/80">
                    @foreach($siteSettings->emails() as $email)
                        <li><a href="mailto:{{ $email }}" class="hover:text-ssbc-gold">{{ $email }}</a></li>
                    @endforeach
                    @foreach($siteSettings->phones() as $phone)
                        <li>{{ $phone }}</li>
                    @endforeach
                    @if($siteSettings->address($locale))
                        <li class="whitespace-pre-line">{{ $siteSettings->address($locale) }}</li>
                    @endif
                </ul>

                @php $socials = $siteSettings->socials(); @endphp
                @if (! empty($socials))
                    <div class="mt-5 flex gap-3">
                        @foreach ($socials as $s)
                            <a href="{{ $s['url'] }}" target="_blank" rel="noopener"
                               aria-label="{{ $s['label'] }}"
                               class="text-white/80 hover:text-ssbc-gold transition-colors">
                                @include('partials.social-icon', ['key' => $s['key'], 'class' => 'h-6 w-6'])
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-white/10 text-xs text-ssbc-sage">
            {{ __('common.copyright', ['year' => now()->year]) }}
        </div>
    </div>
</footer>
