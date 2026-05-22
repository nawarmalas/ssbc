<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormSection;
use App\Models\NewsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://sysabc.org']);
    }

    public function test_public_page_renders_core_seo_tags(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('<meta name="description" content="Official website of the Syrian Saudi Business Council', false)
            ->assertSee('<link rel="canonical" href="https://sysabc.org/en">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="https://sysabc.org/en">', false)
            ->assertSee('<link rel="alternate" hreflang="ar" href="https://sysabc.org/ar">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="https://sysabc.org/en">', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    }

    public function test_news_detail_renders_dynamic_article_seo(): void
    {
        $post = NewsPost::create([
            'slug' => 'trade-mission',
            'title_en' => 'Trade Mission Announced',
            'title_ar' => 'Trade Mission Announced',
            'excerpt_en' => 'Council announces a new bilateral trade mission.',
            'excerpt_ar' => 'Council announces a new bilateral trade mission.',
            'content_en' => '<p>Article body</p>',
            'content_ar' => '<p>Article body</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get('/en/news/'.$post->slug)
            ->assertOk()
            ->assertSee('Trade Mission Announced', false)
            ->assertSee('Council announces a new bilateral trade mission.', false)
            ->assertSee('<link rel="canonical" href="https://sysabc.org/en/news/trade-mission">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('"@type":"NewsArticle"', false)
            ->assertSee('"mainEntityOfPage":"https://sysabc.org/en/news/trade-mission"', false);
    }

    public function test_sitemap_contains_indexable_public_urls_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-22 12:00:00'));

        try {
            NewsPost::create([
                'slug' => 'published-news',
                'title_en' => 'Published News',
                'title_ar' => 'Published News',
                'status' => 'published',
                'published_at' => now()->subDay(),
            ]);

            NewsPost::create([
                'slug' => 'draft-news',
                'title_en' => 'Draft News',
                'title_ar' => 'Draft News',
                'status' => 'draft',
                'published_at' => null,
            ]);

            NewsPost::create([
                'slug' => 'future-news',
                'title_en' => 'Future News',
                'title_ar' => 'Future News',
                'status' => 'published',
                'published_at' => now()->addDay(),
            ]);

            $this->get('/sitemap.xml')
                ->assertOk()
                ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
                ->assertSee('<loc>https://sysabc.org/en</loc>', false)
                ->assertSee('<loc>https://sysabc.org/ar/contact</loc>', false)
                ->assertSee('<loc>https://sysabc.org/en/news/published-news</loc>', false)
                ->assertSee('<loc>https://sysabc.org/ar/news/published-news</loc>', false)
                ->assertDontSee('draft-news')
                ->assertDontSee('future-news')
                ->assertDontSee('/admin')
                ->assertDontSee('/thanks')
                ->assertDontSee('/forms/');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_robots_txt_points_to_sitemap_and_blocks_admin(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap: https://sysabc.org/sitemap.xml', $robots);
    }

    public function test_private_and_thank_you_pages_are_noindex(): void
    {
        $this->get('/en/join/thanks')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $form = FormDefinition::create([
            'form_id' => 'private-seo-test',
            'slug' => 'private-seo-test',
            'title_en' => 'Private SEO Test',
            'title_ar' => 'Private SEO Test',
            'visibility' => FormDefinition::VISIBILITY_PRIVATE,
            'access_token' => Str::random(48),
            'is_active' => true,
        ]);

        FormSection::create([
            'form_id' => $form->form_id,
            'title_en' => 'Details',
            'title_ar' => 'Details',
            'order_index' => 0,
        ]);

        $this->get(route('private-forms.show', [
            'locale' => 'en',
            'form' => $form->slug,
            'token' => $form->access_token,
        ]))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
