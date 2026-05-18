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
}
