<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use App\Support\Seo;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => Seo::absoluteUrl('/en'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => Seo::absoluteUrl('/ar'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => Seo::absoluteUrl('/en/about'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => Seo::absoluteUrl('/ar/about'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => Seo::absoluteUrl('/en/news'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => Seo::absoluteUrl('/ar/news'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => Seo::absoluteUrl('/en/join'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => Seo::absoluteUrl('/ar/join'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => Seo::absoluteUrl('/en/contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => Seo::absoluteUrl('/ar/contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
        ]);

        NewsPost::published()->get()->each(function (NewsPost $post) use ($urls): void {
            foreach (['en', 'ar'] as $locale) {
                $urls->push([
                    'loc' => Seo::routeUrl('news.show', ['locale' => $locale, 'slug' => $post->slug]),
                    'lastmod' => $post->updated_at?->toDateString(),
                    'priority' => '0.7',
                    'changefreq' => 'monthly',
                ]);
            }
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
