<?php
// tests/Feature/Admin/BoardMemberControllerTest.php

namespace Tests\Feature\Admin;

use App\Models\BoardMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BoardMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.board-members.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_index_shows_all_members(): void
    {
        BoardMember::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.board-members.index'))
            ->assertOk()
            ->assertViewIs('admin.board-members.index')
            ->assertViewHas('members');
    }

    public function test_create_shows_empty_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.board-members.create'))
            ->assertOk()
            ->assertViewIs('admin.board-members.create');
    }

    public function test_store_creates_member_without_photo(): void
    {
        $data = [
            'name_ar'    => 'أحمد الرشيد',
            'name_en'    => 'Ahmad Al-Rashid',
            'role_ar'    => 'رئيس المجلس',
            'role_en'    => 'Chairman',
            'bio_ar'     => 'نبذة عن العضو',
            'bio_en'     => 'Member biography.',
            'sort_order' => 1,
            'is_active'  => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.board-members.store'), $data)
            ->assertRedirect(route('admin.board-members.index'));

        $this->assertDatabaseHas('board_members', ['name_en' => 'Ahmad Al-Rashid', 'photo' => null]);
    }

    public function test_store_uploads_photo(): void
    {
        Storage::fake('public');

        $data = [
            'name_ar'    => 'أحمد',
            'name_en'    => 'Ahmad',
            'role_ar'    => 'رئيس',
            'role_en'    => 'Chair',
            'bio_ar'     => 'نبذة',
            'bio_en'     => 'Bio',
            'sort_order' => 0,
            'is_active'  => 1,
            'photo'      => UploadedFile::fake()->image('photo.jpg', 400, 500),
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.board-members.store'), $data)
            ->assertRedirect(route('admin.board-members.index'));

        $member = BoardMember::first();
        $this->assertNotNull($member->photo);
        Storage::disk('public')->assertExists($member->photo);
    }

    public function test_edit_shows_form_with_member_data(): void
    {
        $member = BoardMember::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.board-members.edit', $member))
            ->assertOk()
            ->assertViewIs('admin.board-members.edit')
            ->assertViewHas('member', $member);
    }

    public function test_update_changes_member_fields(): void
    {
        $member = BoardMember::factory()->create(['name_en' => 'Old Name']);

        $this->actingAs($this->admin)
            ->put(route('admin.board-members.update', $member), [
                'name_ar'    => $member->name_ar,
                'name_en'    => 'New Name',
                'role_ar'    => $member->role_ar,
                'role_en'    => $member->role_en,
                'bio_ar'     => $member->bio_ar,
                'bio_en'     => $member->bio_en,
                'sort_order' => $member->sort_order,
                'is_active'  => 1,
            ])
            ->assertRedirect(route('admin.board-members.index'));

        $this->assertDatabaseHas('board_members', ['id' => $member->id, 'name_en' => 'New Name']);
    }

    public function test_update_replaces_photo_and_deletes_old(): void
    {
        Storage::fake('public');
        $oldPath = 'board-members/old-photo.jpg';
        Storage::disk('public')->put($oldPath, 'fake');
        $member = BoardMember::factory()->create(['photo' => $oldPath]);

        $this->actingAs($this->admin)
            ->put(route('admin.board-members.update', $member), [
                'name_ar'    => $member->name_ar,
                'name_en'    => $member->name_en,
                'role_ar'    => $member->role_ar,
                'role_en'    => $member->role_en,
                'bio_ar'     => $member->bio_ar,
                'bio_en'     => $member->bio_en,
                'sort_order' => $member->sort_order,
                'is_active'  => 1,
                'photo'      => UploadedFile::fake()->image('new.jpg', 400, 500),
            ])
            ->assertRedirect(route('admin.board-members.index'));

        $updated = $member->fresh();
        $this->assertNotNull($updated->photo);
        $this->assertNotSame($oldPath, $updated->photo);
        Storage::disk('public')->assertExists($updated->photo);

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_update_sets_is_active_false_when_checkbox_absent(): void
    {
        $member = BoardMember::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->put(route('admin.board-members.update', $member), [
                'name_ar'    => $member->name_ar,
                'name_en'    => $member->name_en,
                'role_ar'    => $member->role_ar,
                'role_en'    => $member->role_en,
                'bio_ar'     => $member->bio_ar,
                'bio_en'     => $member->bio_en,
                'sort_order' => $member->sort_order,
                // 'is_active' intentionally absent — simulates unchecked checkbox
            ])
            ->assertRedirect(route('admin.board-members.index'));

        $this->assertDatabaseHas('board_members', ['id' => $member->id, 'is_active' => false]);
    }

    public function test_destroy_deletes_member_and_photo(): void
    {
        Storage::fake('public');
        $path = 'board-members/photo.jpg';
        Storage::disk('public')->put($path, 'fake');
        $member = BoardMember::factory()->create(['photo' => $path]);

        $this->actingAs($this->admin)
            ->delete(route('admin.board-members.destroy', $member))
            ->assertRedirect(route('admin.board-members.index'));

        $this->assertDatabaseMissing('board_members', ['id' => $member->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_admin_cannot_access_board_members(): void
    {
        $subadmin = User::factory()->create(['role' => 'subadmin', 'permissions' => ['news_write']]);

        $this->actingAs($subadmin)
            ->get(route('admin.board-members.index'))
            ->assertForbidden();
    }
}
