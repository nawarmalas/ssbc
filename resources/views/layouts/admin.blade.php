<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('admin.title'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-ssbc-light">

    <header class="bg-ssbc-green text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <span class="font-display font-bold text-lg">{{ __('admin.title') }}</span>
                    <span class="w-1 h-1 rounded-full bg-ssbc-gold"></span>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-white/80 hover:text-ssbc-gold">{{ __('admin.logout') }}</button>
                </form>
            </div>
        </div>

        <nav class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-6 overflow-x-auto">
                @php
                    $tabs = [
                        ['route' => 'admin.dashboard',       'label' => __('admin.dashboard')],
                        ['route' => 'admin.news.index',      'label' => __('admin.news')],
                        ['route' => 'admin.forms.builder',   'label' => 'Form Builder'],
                        ['route' => 'admin.submissions.index', 'label' => 'Submissions'],
                        ['route' => 'admin.join.index',      'label' => __('admin.join')],
                        ['route' => 'admin.contact.index',   'label' => __('admin.contact')],
                        ['route' => 'admin.membership.index','label' => __('admin.membership')],
                        ['route' => 'admin.settings.edit',   'label' => __('admin.settings')],
                    ];
                    $current = request()->route() ? request()->route()->getName() : '';
                @endphp
                @foreach($tabs as $tab)
                    @php
                        $base = explode('.', $tab['route'])[1] ?? '';
                        $active = str_starts_with($current, 'admin.' . $base);
                    @endphp
                    <a href="{{ route($tab['route']) }}"
                       class="py-3 text-sm uppercase tracking-wider border-b-2 -mb-px
                              {{ $active ? 'border-ssbc-gold text-ssbc-gold font-semibold' : 'border-transparent text-white/70 hover:text-white' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </header>

    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            @if (session('status'))
                <div class="mb-6 border border-ssbc-green/30 bg-ssbc-green/5 px-4 py-3 text-sm text-ssbc-green">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

</body>
</html>
