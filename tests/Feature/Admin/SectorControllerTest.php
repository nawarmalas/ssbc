<?php

namespace Tests\Feature\Admin;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectorControllerTest extends TestCase
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
        $this->get(route('admin.sectors.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_index_shows_all_sectors(): void
    {
        Sector::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.sectors.index'))
            ->assertOk()
            ->assertViewIs('admin.sectors.index')
            ->assertViewHas('sectors');
    }

    public function test_create_shows_empty_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.sectors.create'))
            ->assertOk()
            ->assertViewIs('admin.sectors.create');
    }

    public function test_store_creates_sector(): void
    {
        $data = [
            'name_ar'        => 'القطاع الزراعي',
            'name_en'        => 'Agriculture',
            'description_ar' => 'وصف القطاع الزراعي.',
            'description_en' => 'Agriculture sector description.',
            'sort_order'     => 1,
            'is_active'      => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.sectors.store'), $data)
            ->assertRedirect(route('admin.sectors.index'));

        $this->assertDatabaseHas('sectors', ['name_en' => 'Agriculture', 'is_active' => true]);
    }

    public function test_store_sets_is_active_false_when_checkbox_absent(): void
    {
        $data = [
            'name_ar'        => 'القطاع الزراعي',
            'name_en'        => 'Agriculture',
            'description_ar' => 'وصف القطاع الزراعي.',
            'description_en' => 'Agriculture sector description.',
            'sort_order'     => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.sectors.store'), $data)
            ->assertRedirect(route('admin.sectors.index'));

        $this->assertDatabaseHas('sectors', ['name_en' => 'Agriculture', 'is_active' => false]);
    }

    public function test_edit_shows_form_with_sector_data(): void
    {
        $sector = Sector::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.sectors.edit', $sector))
            ->assertOk()
            ->assertViewIs('admin.sectors.edit')
            ->assertViewHas('sector', $sector);
    }

    public function test_update_changes_sector_fields(): void
    {
        $sector = Sector::factory()->create(['name_en' => 'Old Name']);

        $this->actingAs($this->admin)
            ->put(route('admin.sectors.update', $sector), [
                'name_ar'        => $sector->name_ar,
                'name_en'        => 'New Name',
                'description_ar' => $sector->description_ar,
                'description_en' => $sector->description_en,
                'sort_order'     => $sector->sort_order,
                'is_active'      => 1,
            ])
            ->assertRedirect(route('admin.sectors.index'));

        $this->assertDatabaseHas('sectors', ['id' => $sector->id, 'name_en' => 'New Name']);
    }

    public function test_update_sets_is_active_false_when_checkbox_absent(): void
    {
        $sector = Sector::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->put(route('admin.sectors.update', $sector), [
                'name_ar'        => $sector->name_ar,
                'name_en'        => $sector->name_en,
                'description_ar' => $sector->description_ar,
                'description_en' => $sector->description_en,
                'sort_order'     => $sector->sort_order,
            ])
            ->assertRedirect(route('admin.sectors.index'));

        $this->assertDatabaseHas('sectors', ['id' => $sector->id, 'is_active' => false]);
    }

    public function test_destroy_deletes_sector(): void
    {
        $sector = Sector::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.sectors.destroy', $sector))
            ->assertRedirect(route('admin.sectors.index'));

        $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
    }

    public function test_non_admin_cannot_access_sectors(): void
    {
        $subadmin = User::factory()->create(['role' => 'subadmin', 'permissions' => ['news_write']]);

        $this->actingAs($subadmin)
            ->get(route('admin.sectors.index'))
            ->assertForbidden();
    }
}
