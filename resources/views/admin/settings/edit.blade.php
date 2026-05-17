@extends('layouts.admin')

@section('title', __('admin.settings'))

@section('content')
    <div class="w-12 h-px bg-ssbc-gold mb-4"></div>
    <h1 class="text-2xl font-display font-bold text-ssbc-green mb-8">{{ __('admin.settings') }}</h1>

    @if ($errors->any())
        <div class="mb-6 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="ssbc-admin-card p-6 space-y-6 max-w-3xl">
        @csrf @method('PATCH')

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="ssbc-label" for="contact_email">{{ __('admin.contact_email') }}</label>
                <input id="contact_email" name="contact_email" type="email" required class="ssbc-input"
                       value="{{ old('contact_email', $settings->contact_email) }}">
            </div>
            <div>
                <label class="ssbc-label" for="contact_phone">{{ __('admin.contact_phone') }}</label>
                <input id="contact_phone" name="contact_phone" type="text" required class="ssbc-input"
                       value="{{ old('contact_phone', $settings->contact_phone) }}">
            </div>

            <div>
                <label class="ssbc-label" for="address_en">{{ __('admin.address_en') }}</label>
                <textarea id="address_en" name="address_en" rows="3" required class="ssbc-input">{{ old('address_en', $settings->address_en) }}</textarea>
            </div>
            <div>
                <label class="ssbc-label" for="address_ar">{{ __('admin.address_ar') }}</label>
                <textarea id="address_ar" name="address_ar" rows="3" required class="ssbc-input" dir="rtl" lang="ar">{{ old('address_ar', $settings->address_ar) }}</textarea>
            </div>

            <div>
                <label class="ssbc-label" for="linkedin_url">{{ __('admin.linkedin_url') }}</label>
                <input id="linkedin_url" name="linkedin_url" type="url" class="ssbc-input"
                       value="{{ old('linkedin_url', $settings->linkedin_url) }}">
            </div>
            <div>
                <label class="ssbc-label" for="twitter_url">{{ __('admin.twitter_url') }}</label>
                <input id="twitter_url" name="twitter_url" type="url" class="ssbc-input"
                       value="{{ old('twitter_url', $settings->twitter_url) }}">
            </div>

            <div>
                <label class="ssbc-label" for="footer_desc_en">{{ __('admin.footer_desc_en') }}</label>
                <textarea id="footer_desc_en" name="footer_desc_en" rows="4" class="ssbc-input">{{ old('footer_desc_en', $settings->footer_desc_en) }}</textarea>
            </div>
            <div>
                <label class="ssbc-label" for="footer_desc_ar">{{ __('admin.footer_desc_ar') }}</label>
                <textarea id="footer_desc_ar" name="footer_desc_ar" rows="4" class="ssbc-input" dir="rtl" lang="ar">{{ old('footer_desc_ar', $settings->footer_desc_ar) }}</textarea>
            </div>
        </div>

        <div class="border-t border-ssbc-green/15 pt-6">
            <button type="submit" class="ssbc-btn-primary">{{ __('admin.save') }}</button>
        </div>
    </form>
@endsection
