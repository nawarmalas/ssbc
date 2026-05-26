<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use App\Support\Seo;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $pages = [
            ['path' => '', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['path' => '/about', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['path' => '/news', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['path' => '/join', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['path' => '/contact', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        $urls = collect();

        foreach ($pages as $page) {
            $enUrl = Seo::absoluteUrl('/en' . $page['path']);
            $arUrl = Seo::absoluteUrl('/ar' . $page['path']);

            foreach (['en' => $enUrl, 'ar' => $arUrl] as $lang => $loc) {
                $urls->push([
                    'loc'        => $loc,
                    'priority'   => $page['priority'],
                    'changefreq' => $page['changefreq'],
                    'alternates' => [
                        ['hreflang' => 'en', 'href' => $enUrl],
                        ['hreflang' => 'ar', 'href' => $arUrl],
                        ['hreflang' => 'x-default', 'href' => $enUrl],
                    ],
                ]);
            }
        }

        NewsPost::published()->get()->each(function (NewsPost $post) use ($urls): void {
            $enUrl = Seo::routeUrl('news.show', ['locale' => 'en', 'slug' => $post->slug]);
            $arUrl = Seo::routeUrl('news.show', ['locale' => 'ar', 'slug' => $post->slug]);

            foreach (['en' => $enUrl, 'ar' => $arUrl] as $lang => $loc) {
                $urls->push([
                    'loc'        => $loc,
                    'lastmod'    => $post->updated_at?->toDateString(),
                    'priority'   => '0.7',
                    'changefreq' => 'monthly',
                    'alternates' => [
                        ['hreflang' => 'en', 'href' => $enUrl],
                        ['hreflang' => 'ar', 'href' => $arUrl],
                        ['hreflang' => 'x-default', 'href' => $enUrl],
                    ],
                ]);
            }
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
