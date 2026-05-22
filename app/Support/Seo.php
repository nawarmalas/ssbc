<?php

namespace App\Support;

use App\Models\NewsPost;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class Seo
{
    public const DEFAULT_ROOT = 'https://sysabc.org';

    public static function root(): string
    {
        $root = rtrim((string) config('app.url'), '/');

        if ($root === '' || Str::contains($root, ['localhost', '127.0.0.1'])) {
            $root = self::DEFAULT_ROOT;
        }

        if (! Str::startsWith($root, ['http://', 'https://'])) {
            $root = 'https://'.$root;
        }

        return $root;
    }

    public static function absoluteUrl(string $path = '/'): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = '/'.ltrim($path, '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return self::root().$path;
    }

    public static function currentCanonicalUrl(): string
    {
        return self::absoluteUrl(request()->getPathInfo());
    }

    public static function routeUrl(string $routeName, array $params = []): string
    {
        return self::absoluteUrl(route($routeName, $params, false));
    }

    public static function localizedAlternates(): array
    {
        $route = request()->route();

        if (! $route || ! $route->getName()) {
            return [];
        }

        $routeName = $route->getName();
        $params = $route->parameters();

        if (! array_key_exists('locale', $params)) {
            return [];
        }

        $alternates = [];

        foreach (['en', 'ar'] as $locale) {
            $alternates[$locale] = self::routeUrl($routeName, array_merge($params, ['locale' => $locale]));
        }

        $alternates['x-default'] = $alternates['en'];

        return $alternates;
    }

    public static function defaultDescription(?string $routeName, string $locale): string
    {
        $key = match ($routeName) {
            'home' => 'seo.home.description',
            'about' => 'seo.about.description',
            'news.index' => 'seo.news_index.description',
            'join.create' => 'seo.join.description',
            'contact.create' => 'seo.contact.description',
            default => 'seo.default.description',
        };

        $description = __($key, [], $locale);

        if ($description === $key) {
            return __('common.site_name');
        }

        return $description;
    }

    public static function shouldNoindex(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        return Str::startsWith($routeName, 'admin.')
            || Str::contains($routeName, '.thanks')
            || Str::startsWith($routeName, 'private-forms.');
    }

    public static function organizationSchema(SiteSetting $siteSettings): array
    {
        $emails = $siteSettings->emails();
        $phones = $siteSettings->phones();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => __('common.site_name', [], 'en'),
            'alternateName' => __('common.site_name', [], 'ar'),
            'url' => self::root(),
            'logo' => self::absoluteUrl('/images/logos/logo-two-tone.png'),
            'email' => $emails[0] ?? null,
            'telephone' => $phones[0] ?? null,
            'sameAs' => collect($siteSettings->socials())->pluck('url')->values()->all(),
        ], fn ($value) => $value !== null && $value !== []);
    }

    public static function newsArticleSchema(NewsPost $post, string $locale): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title($locale),
            'description' => $post->excerpt($locale),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'mainEntityOfPage' => self::routeUrl('news.show', [
                'locale' => $locale,
                'slug' => $post->slug,
            ]),
            'image' => $post->featuredImageUrl() ? self::absoluteUrl($post->featuredImageUrl()) : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => __('common.site_name', [], 'en'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::absoluteUrl('/images/logos/logo-two-tone.png'),
                ],
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }
}
