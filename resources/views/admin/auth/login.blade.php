<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('admin.login') }} — {{ __('admin.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ssbc-light flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logos/logo-two-tone.png') }}"
                 alt="{{ __('common.site_name') }}"
                 class="h-52 w-auto mx-auto"
                 loading="eager">
            <p class="mt-3 ssbc-eyebrow">{{ __('admin.login_heading') }}</p>
        </div>

        <div class="border border-ssbc-green/15 bg-white p-8">
            <div class="w-12 h-px bg-ssbc-gold mb-6"></div>

            @if ($errors->any())
                <div class="mb-4 border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="ssbc-label" for="email">{{ __('admin.email') }}</label>
                    <input id="email" name="email" type="email" required autofocus class="ssbc-input" value="{{ old('email') }}">
                </div>
                <div>
                    <label class="ssbc-label" for="password">{{ __('admin.password') }}</label>
                    <input id="password" name="password" type="password" required class="ssbc-input">
                </div>
                <label class="flex items-center gap-2 text-sm text-ssbc-dark">
                    <input type="checkbox" name="remember" value="1" class="rounded-none border-ssbc-green/40 text-ssbc-gold focus:ring-ssbc-gold">
                    {{ __('admin.remember') }}
                </label>

                <button type="submit" class="w-full ssbc-btn-primary">{{ __('admin.login') }}</button>
            </form>
        </div>
    </div>

</body>
</html>
