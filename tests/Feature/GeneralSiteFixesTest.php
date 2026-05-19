<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralSiteFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_publishing_draft_makes_news_post_public_immediately(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $post = NewsPost::create([
            'slug' => 'draft-news',
            'title_en' => 'Draft News',
            'title_ar' => 'Draft News',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->patch(route('admin.news.update', $post), [
            'title_en' => 'Draft News',
            'title_ar' => 'Draft News',
            'status' => 'published',
        ])->assertRedirect(route('admin.news.index'));

        $post = $post->fresh();

        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertTrue(NewsPost::published()->whereKey($post)->exists());
    }

    public function test_submissions_can_be_filtered_by_form_id(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $joinForm = FormDefinition::where('form_id', 'join-us')->firstOrFail();
        $privateForm = FormDefinition::create([
            'form_id' => 'private-filter-test',
            'slug' => 'filter-test',
            'title_en' => 'Private Filter Test',
            'title_ar' => 'Private Filter Test',
            'visibility' => FormDefinition::VISIBILITY_PRIVATE,
            'access_token' => 'filter-token',
            'is_active' => true,
        ]);

        FormSubmission::create([
            'form_id' => $joinForm->form_id,
            'display_name' => 'Join Applicant',
            'submitted_at' => now(),
        ]);
        FormSubmission::create([
            'form_id' => $privateForm->form_id,
            'display_name' => 'Private Applicant',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.submissions.index', ['form_id' => $privateForm->form_id]))
            ->assertOk()
            ->assertSee('Private Applicant')
            ->assertDontSee('Join Applicant');
    }
}
