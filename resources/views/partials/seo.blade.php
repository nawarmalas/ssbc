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
    $keywords = trim($__env->yieldContent('keywords', Seo::defaultKeywords($routeName, $locale)));
    $alternates = Seo::localizedAlternates();
    $schemas = [Seo::organizationSchema($siteSettings)];

    if ($routeName === 'home') {
        $schemas[] = Seo::websiteSchema();
    }

    if ($routeName === 'news.show' && isset($post) && $post instanceof \App\Models\NewsPost) {
        $schemas[] = Seo::newsArticleSchema($post, $locale);
        $schemas[] = Seo::breadcrumbSchema([
            ['name' => __('common.site_name'), 'url' => Seo::routeUrl('home', ['locale' => $locale])],
            ['name' => __('nav.news', [], $locale), 'url' => Seo::routeUrl('news.index', ['locale' => $locale])],
            ['name' => $post->title($locale)],
        ]);
    } elseif (in_array($routeName, ['about', 'join.create', 'contact.create', 'news.index'], true)) {
        $pageNames = [
            'about'          => __('nav.about', [], $locale),
            'join.create'    => __('nav.join', [], $locale),
            'contact.create' => __('nav.contact', [], $locale),
            'news.index'     => __('nav.news', [], $locale),
        ];
        $schemas[] = Seo::breadcrumbSchema([
            ['name' => __('common.site_name'), 'url' => Seo::routeUrl('home', ['locale' => $locale])],
            ['name' => $pageNames[$routeName]],
        ]);
    }
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
@if($keywords)
<meta name="keywords" content="{{ $keywords }}">
@endif
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
