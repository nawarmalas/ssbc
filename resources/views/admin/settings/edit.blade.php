@extends('layouts.admin')

@section('title', __('admin.site_customization'))
@section('page_title', __('admin.site_customization'))

@php
    use Illuminate\Support\Facades\Lang;

    $home = $settings->home_content ?? [];
    $about = $settings->about_content ?? [];
    // After a validation failure, old('_tab') tells us which form was being
    // submitted so we restore that tab. Falls back to the success-flash hint,
    // then to the default landing tab.
    $openTab = old('_tab', session('open_tab', 'home'));
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-6 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div x-data="{ tab: '{{ $openTab }}' }" class="max-w-5xl">

        {{-- Tab nav --}}
        <div class="flex flex-wrap gap-1 border-b border-gray-200 mb-6">
            @php
                $tabs = [
                    'home'    => 'Homepage',
                    'about'   => 'About Page',
                    'contact' => 'Contact & Footer',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <button type="button"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-ssbc-green text-white' : 'text-ssbc-sage hover:text-ssbc-green'"
                        class="px-5 py-2 text-sm font-semibold uppercase tracking-wider transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- =================== Tab: Contact & Footer =================== --}}
        <div x-show="tab === 'contact'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.update') }}" novalidate class="ssbc-admin-card p-6 space-y-6">
                @csrf @method('PATCH')
                <input type="hidden" name="_tab" value="contact">

                @php
                    $emailsPrefill = old('contact_emails') !== null
                        ? array_values((array) old('contact_emails'))
                        : ($settings->emails() ?: ['']);
                    $phonesPrefill = old('contact_phones') !== null
                        ? array_values((array) old('contact_phones'))
                        : ($settings->phones() ?: ['']);
                @endphp

                <div class="grid md:grid-cols-2 gap-6">
                    {{-- Emails --}}
                    <div x-data="{ items: @js($emailsPrefill) }">
                        <label class="ssbc-admin-label">{{ __('admin.contact_emails') }}</label>
                        <template x-for="(item, i) in items" :key="i">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="email" x-model="items[i]"
                                       :name="`contact_emails[${i}]`"
                                       class="ssbc-admin-input flex-1"
                                       placeholder="info@ssbc.org">
                                <button type="button" x-show="items.length > 1" @click="items.splice(i, 1)"
                                        class="text-red-600 hover:text-red-800 text-xs uppercase tracking-wider px-2"
                                        aria-label="Remove email">&times;</button>
                            </div>
                        </template>
                        <button type="button" @click="items.push('')"
                                class="text-sm text-ssbc-green hover:text-ssbc-gold mt-1">
                            + {{ __('admin.add_email') }}
                        </button>
                        @error('contact_emails')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        @foreach ($errors->get('contact_emails.*') as $msgs)
                            @foreach ($msgs as $m)<p class="text-red-500 text-xs mt-1">{{ $m }}</p>@endforeach
                        @endforeach
                    </div>

                    {{-- Phones --}}
                    <div x-data="{ items: @js($phonesPrefill) }">
                        <label class="ssbc-admin-label">{{ __('admin.contact_phones') }}</label>
                        <template x-for="(item, i) in items" :key="i">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="text" x-model="items[i]"
                                       :name="`contact_phones[${i}]`"
                                       class="ssbc-admin-input flex-1"
                                       placeholder="+966 50 000 0000">
                                <button type="button" x-show="items.length > 1" @click="items.splice(i, 1)"
                                        class="text-red-600 hover:text-red-800 text-xs uppercase tracking-wider px-2"
                                        aria-label="Remove phone">&times;</button>
                            </div>
                        </template>
                        <button type="button" @click="items.push('')"
                                class="text-sm text-ssbc-green hover:text-ssbc-gold mt-1">
                            + {{ __('admin.add_phone') }}
                        </button>
                        @error('contact_phones')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        @foreach ($errors->get('contact_phones.*') as $msgs)
                            @foreach ($msgs as $m)<p class="text-red-500 text-xs mt-1">{{ $m }}</p>@endforeach
                        @endforeach
                    </div>
                </div>

                <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
                    <div class="md:pr-6">
                        <label class="ssbc-admin-label" for="address_en">{{ __('admin.address_en') }}</label>
                        <textarea id="address_en" name="address_en" rows="3" required class="ssbc-admin-input">{{ old('address_en', $settings->address_en) }}</textarea>
                    </div>
                    <div class="md:pl-6 mt-4 md:mt-0">
                        <label class="ssbc-admin-label" for="address_ar">{{ __('admin.address_ar') }}</label>
                        <textarea id="address_ar" name="address_ar" rows="3" required class="ssbc-admin-input" dir="rtl" lang="ar">{{ old('address_ar', $settings->address_ar) }}</textarea>
                    </div>
                </div>

                {{-- Social links --}}
                <div>
                    <h3 class="font-display font-bold text-ssbc-green text-base mb-1">{{ __('admin.social_links') }}</h3>
                    <p class="text-xs text-ssbc-sage mb-4">Empty fields are hidden from the public site.</p>
                    @php
                        $savedSocials = old('social_links', $settings->social_links ?? []);
                        $placeholders = [
                            'linkedin'  => 'https://www.linkedin.com/company/...',
                            'x'         => 'https://x.com/...',
                            'instagram' => 'https://www.instagram.com/...',
                            'facebook'  => 'https://www.facebook.com/...',
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach (App\Models\SiteSetting::SOCIAL_PLATFORMS as $platform => $label)
                            <div class="flex items-start gap-3">
                                <span class="text-ssbc-green mt-2 shrink-0">
                                    @include('partials.social-icon', ['key' => $platform, 'class' => 'h-5 w-5'])
                                </span>
                                <div class="flex-1">
                                    <label class="ssbc-admin-label" for="social_{{ $platform }}">{{ $label }}</label>
                                    <input id="social_{{ $platform }}" type="url"
                                           name="social_links[{{ $platform }}]"
                                           value="{{ $savedSocials[$platform] ?? '' }}"
                                           class="ssbc-admin-input"
                                           placeholder="{{ $placeholders[$platform] }}">
                                    @error('social_links.'.$platform)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid md:grid-cols-2 md:divide-x divide-gray-200">
                    <div class="md:pr-6">
                        <label class="ssbc-admin-label" for="footer_desc_en">{{ __('admin.footer_desc_en') }}</label>
                        <textarea id="footer_desc_en" name="footer_desc_en" rows="4" class="ssbc-admin-input">{{ old('footer_desc_en', $settings->footer_desc_en) }}</textarea>
                    </div>
                    <div class="md:pl-6 mt-4 md:mt-0">
                        <label class="ssbc-admin-label" for="footer_desc_ar">{{ __('admin.footer_desc_ar') }}</label>
                        <textarea id="footer_desc_ar" name="footer_desc_ar" rows="4" class="ssbc-admin-input" dir="rtl" lang="ar">{{ old('footer_desc_ar', $settings->footer_desc_ar) }}</textarea>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <button type="submit" class="ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
                </div>
            </form>
        </div>

        {{-- =================== Tab: Homepage =================== --}}
        <div x-show="tab === 'home'" x-cloak>

            {{-- Hero image upload --}}
            <div class="ssbc-admin-card p-6 mb-6">
                <h3 class="font-display font-bold text-ssbc-green mb-1">Hero background image</h3>
                <p class="text-sm text-ssbc-sage mb-4">Replaces the homepage hero photo. Recommended: at least 1920×1080, JPG/PNG/WebP, under 4 MB.</p>

                @if ($settings->hero_image_path)
                    <div class="flex items-center gap-4 mb-4">
                        <img src="{{ $settings->heroImageUrl() }}" alt="Current hero" class="h-20 w-32 object-cover border border-gray-200">
                        @include('partials.admin.confirm-delete', [
                            'action'       => route('admin.settings.hero.destroy'),
                            'title'        => 'Remove hero image?',
                            'message'      => 'The homepage will fall back to the default hero photo. You can upload a new image at any time.',
                            'button'       => 'Remove image',
                            'class'        => 'text-sm text-red-600 hover:text-red-800',
                            'confirmLabel' => 'Remove',
                        ])
                    </div>
                @else
                    <p class="text-xs text-ssbc-sage mb-4">Currently using the default hero image.</p>
                @endif

                <form method="POST" action="{{ route('admin.settings.hero.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="file" name="hero_image" accept=".jpg,.jpeg,.png,.webp" required class="text-sm" data-webp-auto>
                    <button type="submit" class="ssbc-admin-btn-primary">Upload</button>
                </form>
            </div>

            {{-- Copy editor --}}
            <form method="POST" action="{{ route('admin.settings.home.update') }}" class="ssbc-admin-card p-6 space-y-8">
                @csrf @method('PATCH')
                <input type="hidden" name="_tab" value="home">

                <p class="text-sm text-ssbc-sage">
                    Empty fields fall back to the built-in default. Edits apply per language.
                </p>

                @foreach ($homeSchema as $section => $fields)
                    <div>
                        <h3 class="font-display font-bold text-ssbc-green text-lg mb-4">{{ $section }}</h3>
                        @if ($section === 'Strategic Pillars')
                            <p class="text-sm text-ssbc-sage mb-4">
                                These fields control only the section heading text. The pillar cards
                                themselves are managed under <strong>Dashboard &rarr; Sectors</strong>.
                            </p>
                        @endif
                        <div class="space-y-5">
                            @foreach ($fields as $key => $meta)
                                @include('admin.settings._field', [
                                    'key'        => $key,
                                    'meta'       => $meta,
                                    'bag'        => $home,
                                    'langPrefix' => 'home',
                                ])
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="border-t border-gray-200 pt-6">
                    <button type="submit" class="ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
                </div>
            </form>
        </div>

        {{-- =================== Tab: About =================== --}}
        <div x-show="tab === 'about'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.about.update') }}" class="ssbc-admin-card p-6 space-y-8">
                @csrf @method('PATCH')
                <input type="hidden" name="_tab" value="about">

                <p class="text-sm text-ssbc-sage">
                    Empty fields fall back to the built-in default. The About hero body uses the Homepage Overview body.
                </p>

                @foreach ($aboutSchema as $section => $fields)
                    <div>
                        <h3 class="font-display font-bold text-ssbc-green text-lg mb-4">{{ $section }}</h3>
                        <div class="space-y-5">
                            @foreach ($fields as $key => $meta)
                                @include('admin.settings._field', [
                                    'key'        => $key,
                                    'meta'       => $meta,
                                    'bag'        => $about,
                                    'langPrefix' => 'about',
                                ])
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="border-t border-gray-200 pt-6">
                    <button type="submit" class="ssbc-admin-btn-primary">{{ __('admin.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
