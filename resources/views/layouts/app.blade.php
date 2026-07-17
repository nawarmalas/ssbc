<!DOCTYPE html>
<html lang="{{ $currentLocale }}" dir="{{ $currentLocale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a4731">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('google_verification')

    @include('partials.seo')

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    {{-- Preload the fonts this locale paints first, so text renders in its
         final face with no swap-induced layout shift. Arabic glyphs appear
         on both locales (hero tagline), so the AR subsets always preload. --}}
    @if($currentLocale === 'ar')
        <link rel="preload" href="/fonts/el-messiri-arabic-700-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/noto-kufi-arabic-arabic-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    @else
        <link rel="preload" href="/fonts/el-messiri-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/noto-kufi-arabic-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/el-messiri-arabic-600-normal.woff2" as="font" type="font/woff2" crossorigin>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-white">
    @include('partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
