<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\User;
use App\Services\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        return $this->actingAs($admin);
    }

    private function joinForm(): FormDefinition
    {
        return FormDefinition::where('form_id', 'join-us')->firstOrFail();
    }

    public function test_form_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('form_definitions'));
        $this->assertTrue(Schema::hasTable('form_sections'));
        $this->assertTrue(Schema::hasTable('form_fields'));
        $this->assertTrue(Schema::hasTable('form_submissions'));
        $this->assertTrue(Schema::hasTable('form_answers'));
        $this->assertTrue(Schema::hasTable('form_uploads'));
    }

    public function test_get_active_form_returns_sections_with_fields(): void
    {
        $section = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Personal',
            'title_ar' => 'Personal',
            'order_index' => 0,
        ]);
        FormField::create([
            'section_id' => $section->id,
            'label_en' => 'Name',
            'label_ar' => 'Name',
            'field_type' => 'text',
            'is_active' => true,
            'order_index' => 0,
        ]);
        FormField::create([
            'section_id' => $section->id,
            'label_en' => 'Hidden',
            'label_ar' => 'Hidden',
            'field_type' => 'text',
            'is_active' => false,
            'order_index' => 1,
        ]);

        $form = FormService::getActiveForm('join-us');

        $personal = $form->firstWhere('id', $section->id);
        $this->assertNotNull($personal);
        $this->assertCount(1, $personal->fields);
    }

    public function test_get_active_form_is_cached(): void
    {
        FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'S1',
            'title_ar' => 'S1',
            'order_index' => 0,
        ]);

        $before = FormService::getActiveForm('join-us');
        FormSection::query()->delete();
        $form = FormService::getActiveForm('join-us');

        $this->assertCount($before->count(), $form);
        $this->assertGreaterThanOrEqual(1, $form->count());
    }

    public function test_admin_can_create_section(): void
    {
        $this->actingAsAdmin()->postJson(route('admin.forms.sections.store', $this->joinForm()), [
            'title_en' => 'Test Section',
            'title_ar' => 'Test Section',
        ])->assertJson(['success' => true]);

        $this->assertDatabaseHas('form_sections', ['title_en' => 'Test Section']);
    }

    public function test_admin_can_delete_section_with_fields_only_when_forced(): void
    {
        $section = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'S',
            'title_ar' => 'S',
            'order_index' => 0,
        ]);
        FormField::create([
            'section_id' => $section->id,
            'label_en' => 'F',
            'label_ar' => 'F',
            'field_type' => 'text',
            'order_index' => 0,
        ]);

        $this->actingAsAdmin()
            ->deleteJson(route('admin.forms.sections.destroy', [$this->joinForm(), $section]))
            ->assertJson(['success' => false, 'has_fields' => true]);

        $this->actingAsAdmin()
            ->deleteJson(route('admin.forms.sections.destroy', [$this->joinForm(), $section]).'?force=1')
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('form_sections', ['id' => $section->id]);
    }

    public function test_admin_can_create_and_delete_field(): void
    {
        $section = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'S',
            'title_ar' => 'S',
            'order_index' => 0,
        ]);

        $response = $this->actingAsAdmin()->postJson(route('admin.forms.fields.store', $this->joinForm()), [
            'section_id' => $section->id,
            'label_en' => 'Phone Number',
            'label_ar' => 'Phone Number',
            'field_type' => 'tel',
            'is_required' => true,
        ]);

        $response->assertJson(['success' => true]);
        $fieldId = $response->json('data.id');

        $this->actingAsAdmin()
            ->deleteJson(route('admin.forms.fields.destroy', [$this->joinForm(), $fieldId]))
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('form_fields', ['id' => $fieldId]);
    }
}
