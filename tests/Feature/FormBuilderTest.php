<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FormSection;
use App\Models\FormField;
use App\Services\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->create();
        return $this->actingAs($admin);
    }

    public function test_form_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('form_sections'));
        $this->assertTrue(Schema::hasTable('form_fields'));
        $this->assertTrue(Schema::hasTable('form_submissions'));
        $this->assertTrue(Schema::hasTable('form_answers'));
        $this->assertTrue(Schema::hasTable('form_uploads'));
    }

    public function test_get_active_form_returns_sections_with_fields(): void
    {
        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Personal', 'title_ar' => 'شخصي',
            'order_index' => 0,
        ]);
        FormField::create([
            'section_id' => $section->id, 'label_en' => 'Name', 'label_ar' => 'الاسم',
            'field_type' => 'text', 'is_active' => true, 'order_index' => 0,
        ]);
        FormField::create([
            'section_id' => $section->id, 'label_en' => 'Hidden', 'label_ar' => 'مخفي',
            'field_type' => 'text', 'is_active' => false, 'order_index' => 1,
        ]);

        $form = FormService::getActiveForm('join-us');

        $this->assertCount(1, $form);
        $this->assertCount(1, $form->first()->fields);
    }

    public function test_get_active_form_is_cached(): void
    {
        FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'S1', 'title_ar' => 'ق1', 'order_index' => 0,
        ]);

        FormService::getActiveForm('join-us');
        FormSection::query()->delete();
        $form = FormService::getActiveForm('join-us');

        $this->assertCount(1, $form);
    }

    public function test_admin_can_create_section(): void
    {
        $this->actingAsAdmin()->postJson('/admin/forms/join-us/sections', [
            'title_en' => 'Test Section',
            'title_ar' => 'قسم تجريبي',
        ])->assertJson(['success' => true]);

        $this->assertDatabaseHas('form_sections', ['title_en' => 'Test Section']);
    }

    public function test_admin_can_delete_section_with_fields_only_when_forced(): void
    {
        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'S', 'title_ar' => 'ق', 'order_index' => 0,
        ]);
        FormField::create([
            'section_id' => $section->id, 'label_en' => 'F', 'label_ar' => 'ف',
            'field_type' => 'text', 'order_index' => 0,
        ]);

        $this->actingAsAdmin()
            ->deleteJson("/admin/forms/join-us/sections/{$section->id}")
            ->assertJson(['success' => false, 'has_fields' => true]);

        $this->actingAsAdmin()
            ->deleteJson("/admin/forms/join-us/sections/{$section->id}?force=1")
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('form_sections', ['id' => $section->id]);
    }

    public function test_admin_can_create_and_delete_field(): void
    {
        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'S', 'title_ar' => 'ق', 'order_index' => 0,
        ]);

        $response = $this->actingAsAdmin()->postJson('/admin/forms/join-us/fields', [
            'section_id' => $section->id,
            'label_en'   => 'Phone Number',
            'label_ar'   => 'رقم الهاتف',
            'field_type' => 'tel',
            'is_required' => true,
        ]);

        $response->assertJson(['success' => true]);
        $fieldId = $response->json('data.id');

        $this->actingAsAdmin()
            ->deleteJson("/admin/forms/join-us/fields/{$fieldId}")
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('form_fields', ['id' => $fieldId]);
    }
}
