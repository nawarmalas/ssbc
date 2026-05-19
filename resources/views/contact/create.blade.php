@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', __('contact.hero.heading') . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => __('contact.hero.eyebrow'),
    'heading' => __('contact.hero.heading'),
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <div class="grid md:grid-cols-5 gap-12">

            {{-- Contact info --}}
            <div class="md:col-span-2">
                <div class="w-12 h-px bg-ssbc-gold mb-6"></div>
                <h2 class="text-2xl font-display font-bold text-ssbc-green mb-6">
                    {{ __('contact.info_heading') }}
                </h2>

                <dl class="space-y-6 text-sm">
                    @if(count($emails = $siteSettings->emails()))
                        <div>
                            <dt class="ssbc-eyebrow mb-1">{{ __('contact.email_label') }}</dt>
                            <dd class="space-y-1">
                                @foreach($emails as $email)
                                    <div><a href="mailto:{{ $email }}" class="text-ssbc-dark hover:text-ssbc-gold">{{ $email }}</a></div>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                    @if(count($phones = $siteSettings->phones()))
                        <div>
                            <dt class="ssbc-eyebrow mb-1">{{ __('contact.phone_label') }}</dt>
                            <dd class="text-ssbc-dark space-y-1">
                                @foreach($phones as $phone)
                                    <div>{{ $phone }}</div>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                    @if($siteSettings->address($locale))
                        <div>
                            <dt class="ssbc-eyebrow mb-1">{{ __('contact.address_label') }}</dt>
                            <dd class="text-ssbc-dark whitespace-pre-line">{{ $siteSettings->address($locale) }}</dd>
                        </div>
                    @endif
                    @php $socials = $siteSettings->socials(); @endphp
                    @if (! empty($socials))
                        <div>
                            <dt class="ssbc-eyebrow mb-2">{{ __('contact.follow_us_label') }}</dt>
                            <dd class="flex gap-3">
                                @foreach ($socials as $s)
                                    <a href="{{ $s['url'] }}" target="_blank" rel="noopener"
                                       aria-label="{{ $s['label'] }}"
                                       class="text-ssbc-green hover:text-ssbc-gold transition-colors">
                                        @include('partials.social-icon', ['key' => $s['key'], 'class' => 'h-6 w-6'])
                                    </a>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Form --}}
            <div class="md:col-span-3">
                <div class="w-12 h-px bg-ssbc-gold mb-6"></div>
                <h2 class="text-2xl font-display font-bold text-ssbc-green mb-6">
                    {{ __('contact.form_heading') }}
                </h2>

                @if ($errors->any())
                    <div class="mb-6 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store', ['locale' => $locale]) }}" class="space-y-5">
                    @csrf

                    {{-- Honeypot --}}
                    <div class="hidden" aria-hidden="true">
                        <label>Website</label>
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label class="ssbc-label" for="c_name">{{ __('contact.name') }}</label>
                        <input id="c_name" name="name" type="text" required class="ssbc-input" value="{{ old('name') }}">
                    </div>
                    <div>
                        <label class="ssbc-label" for="c_email">{{ __('contact.email') }}</label>
                        <input id="c_email" name="email" type="email" required class="ssbc-input" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label class="ssbc-label" for="c_phone">{{ __('contact.phone') }}</label>
                        <input id="c_phone" name="phone" type="tel" class="ssbc-input" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label class="ssbc-label" for="c_message">{{ __('contact.message') }}</label>
                        <textarea id="c_message" name="message" rows="6" required class="ssbc-input">{{ old('message') }}</textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="ssbc-btn-primary">{{ __('contact.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
