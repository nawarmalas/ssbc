<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('admin.title'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ssbc-light" x-data="{ sidebarOpen: false }">

@php
    use App\Models\ContactSubmission;
    use App\Models\FormSubmission;

    $current = request()->route() ? request()->route()->getName() : '';

    $unread = [
        'contact'     => ContactSubmission::where('status', 'new')->count(),
        'submissions' => FormSubmission::where('status', 'pending')->count(),
    ];

    $nav = auth()->user()?->isNewsSubadmin()
        ? [
            ['key' => 'news', 'route' => 'admin.news.index', 'label' => __('admin.news'), 'badge' => null],
        ]
        : [
            ['key' => 'dashboard',   'route' => 'admin.dashboard',         'label' => __('admin.dashboard'),          'badge' => null],
            ['key' => 'news',        'route' => 'admin.news.index',        'label' => __('admin.news'),               'badge' => null],
            ['key' => 'forms',       'route' => 'admin.forms.index',       'label' => __('admin.form_builder'),       'badge' => null],
            ['key' => 'submissions', 'route' => 'admin.submissions.index', 'label' => __('admin.submissions'),        'badge' => $unread['submissions']],
            ['key' => 'contact',     'route' => 'admin.contact.index',     'label' => __('admin.contact'),            'badge' => $unread['contact']],
            ['key' => 'users',       'route' => 'admin.users.index',       'label' => 'Admin Users',                  'badge' => null],
            ['key' => 'settings',    'route' => 'admin.settings.edit',     'label' => __('admin.site_customization'), 'badge' => null],
        ];
@endphp

{{-- Mobile overlay --}}
<div x-show="sidebarOpen" x-cloak
     @click="sidebarOpen = false"
     class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

{{-- Sidebar --}}
<aside
    class="fixed inset-y-0 left-0 z-40 w-56 bg-ssbc-green flex flex-col transform transition-transform duration-200
           lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    <div class="h-20 flex items-center px-6 border-b border-white/10">
        <a href="{{ auth()->user()?->isNewsSubadmin() ? route('admin.news.index') : route('admin.dashboard') }}" aria-label="{{ __('common.site_name') }}">
            <img src="{{ asset('images/logos/logo-one-tone.png') }}"
                 alt="{{ __('common.site_name') }}"
                 class="h-12 w-auto"
                 loading="eager">
        </a>
    </div>

    <nav class="flex-1 py-4 overflow-y-auto">
        @foreach($nav as $item)
            @php
                $isActive = str_starts_with($current, 'admin.' . $item['key']);
            @endphp
            <a href="{{ route($item['route']) }}"
               class="ssbc-nav-link {{ $isActive ? 'ssbc-nav-link-active' : '' }}">
                <span>{{ $item['label'] }}</span>
                @if(!empty($item['badge']) && $item['badge'] > 0)
                    <span class="ssbc-nav-badge">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="px-6 py-4 border-t border-white/10 text-xs text-white/40">
        v1.0
    </div>
</aside>

{{-- Main column --}}
<div class="lg:pl-56 flex flex-col min-h-screen">

    {{-- Top bar --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
        <div class="h-16 px-4 sm:px-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <button type="button"
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden text-ssbc-dark hover:text-ssbc-green p-1"
                        aria-label="Toggle navigation">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-base sm:text-lg font-display font-semibold text-ssbc-green truncate">
                    @yield('page_title', __('admin.title'))
                </h1>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <span class="hidden sm:inline text-xs text-ssbc-sage">{{ auth()->user()->email }}</span>
                @endauth
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-xs uppercase tracking-wider text-ssbc-sage hover:text-ssbc-green border border-gray-200 px-3 py-1.5">
                        {{ __('admin.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Page content --}}
    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-8">
        @if (session('status'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-cloak
                 x-init="setTimeout(() => show = false, 4000)"
                 x-transition.opacity.duration.300ms
                 class="mb-6 flex items-center justify-between gap-3 border border-ssbc-green/30 bg-ssbc-green/5 px-4 py-3 text-sm text-ssbc-green">
                <span>{{ session('status') }}</span>
                <button type="button" @click="show = false" aria-label="Dismiss"
                        class="text-ssbc-green/70 hover:text-ssbc-green text-lg leading-none">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-cloak
                 x-init="setTimeout(() => show = false, 6000)"
                 x-transition.opacity.duration.300ms
                 class="mb-6 flex items-center justify-between gap-3 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                <span>{{ session('error') }}</span>
                <button type="button" @click="show = false" aria-label="Dismiss"
                        class="text-red-600 hover:text-red-800 text-lg leading-none">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

</body>
</html>
