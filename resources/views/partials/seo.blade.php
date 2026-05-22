@php
    use App\Support\Seo;

    $routeName = request()->route()?->getName();
    $locale = app()->getLocale();
    $title = trim($__env->yieldContent('title', __('common.site_name')));
    $description = trim($__env->yieldContent('meta_description', Seo::defaultDescription($routeName, $locale)));
    $canonical = trim($__env->yieldContent('canonical', Seo::currentCanonicalUrl()));
    $image = trim($__env->yieldContent('og_image', Seo::absoluteUrl('/images/logos/logo-two-tone.png')));
    $type = trim($__env->yieldContent('og_type', $routeName === 'news.show' ? 'article' : 'website'));
    $robots = trim($__env->yieldContent('robots', Seo::shouldNoindex($routeName) ? 'noindex, nofollow' : 'index, follow'));
    $alternates = Seo::localizedAlternates();
    $schemas = [Seo::organizationSchema($siteSettings)];

    if ($routeName === 'news.show' && isset($post) && $post instanceof \App\Models\NewsPost) {
        $schemas[] = Seo::newsArticleSchema($post, $locale);
    }
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

@foreach($alternates as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach

<meta property="og:site_name" content="{{ __('common.site_name') }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_SY' : 'en_US' }}">
@if($locale === 'en')
    <meta property="og:locale:alternate" content="ar_SY">
@else
    <meta property="og:locale:alternate" content="en_US">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">

@foreach($schemas as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
