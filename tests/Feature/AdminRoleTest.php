<?php

namespace Tests\Feature;

use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_subadmin_can_only_access_news_area(): void
    {
        $subadmin = User::factory()->create(['role' => User::ROLE_NEWS_SUBADMIN]);

        $this->actingAs($subadmin)->get('/admin/news')->assertOk();
        $this->actingAs($subadmin)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($subadmin)->get('/admin/settings')->assertForbidden();
        $this->actingAs($subadmin)->get('/admin/forms')->assertForbidden();
        $this->actingAs($subadmin)->get('/admin/submissions')->assertForbidden();
        $this->actingAs($subadmin)->get('/admin/contact')->assertForbidden();
    }

    public function test_news_subadmin_cannot_publish_news(): void
    {
        $subadmin = User::factory()->create(['role' => User::ROLE_NEWS_SUBADMIN]);

        $this->actingAs($subadmin)->post('/admin/news', [
            'title_en' => 'Subadmin Draft',
            'title_ar' => 'Draft',
            'status' => 'published',
            'published_at' => now()->toDateTimeString(),
        ])->assertRedirect('/admin/news');

        $this->assertDatabaseHas('news_posts', [
            'title_en' => 'Subadmin Draft',
            'status' => 'draft',
            'published_at' => null,
            'created_by_user_id' => $subadmin->id,
        ]);
    }

    public function test_news_subadmin_cannot_edit_published_news(): void
    {
        $subadmin = User::factory()->create(['role' => User::ROLE_NEWS_SUBADMIN]);
        $post = NewsPost::create([
            'slug' => 'published-post',
            'title_en' => 'Published',
            'title_ar' => 'Published',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($subadmin)->get(route('admin.news.edit', $post))->assertForbidden();
        $this->actingAs($subadmin)->patch(route('admin.news.update', $post), [
            'title_en' => 'Changed',
            'title_ar' => 'Changed',
            'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_admin_can_publish_subadmin_draft(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $subadmin = User::factory()->create(['role' => User::ROLE_NEWS_SUBADMIN]);
        $post = NewsPost::create([
            'slug' => 'draft-post',
            'title_en' => 'Draft',
            'title_ar' => 'Draft',
            'status' => 'draft',
            'created_by_user_id' => $subadmin->id,
            'updated_by_user_id' => $subadmin->id,
        ]);

        $this->actingAs($admin)->patch(route('admin.news.update', $post), [
            'title_en' => 'Draft',
            'title_ar' => 'Draft',
            'status' => 'published',
        ])->assertRedirect('/admin/news');

        $this->assertDatabaseHas('news_posts', [
            'id' => $post->id,
            'status' => 'published',
            'updated_by_user_id' => $admin->id,
        ]);
    }
}
