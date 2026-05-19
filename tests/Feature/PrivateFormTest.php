<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivateFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_private_form(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post('/admin/forms', [
            'title_en' => 'Board Nomination',
            'title_ar' => 'Board Nomination',
            'description_en' => 'Private form',
        ])->assertRedirect();

        $this->assertDatabaseHas('form_definitions', [
            'title_en' => 'Board Nomination',
            'visibility' => 'private',
            'is_active' => true,
        ]);
    }

    public function test_private_form_link_renders_and_submits(): void
    {
        Mail::fake();

        $form = $this->privateForm();
        $section = FormSection::create([
            'form_id' => $form->form_id,
            'title_en' => 'Details',
            'title_ar' => 'Details',
            'order_index' => 0,
        ]);
        $nameField = FormField::create([
            'section_id' => $section->id,
            'label_en' => 'Full Name',
            'label_ar' => 'Full Name',
            'field_type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'order_index' => 0,
        ]);

        $this->get(route('private-forms.show', [
            'locale' => 'en',
            'form' => $form->slug,
            'token' => $form->access_token,
        ]))->assertOk()->assertSee('Board Nomination');

        $this->post(route('private-forms.store', [
            'locale' => 'en',
            'form' => $form->slug,
            'token' => $form->access_token,
        ]), [
            'answers' => [
                $nameField->id => [0 => 'Private Applicant'],
            ],
            '_repeats' => [],
        ])->assertRedirect(route('private-forms.thanks', [
            'locale' => 'en',
            'form' => $form->slug,
            'token' => $form->access_token,
        ]));

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->form_id,
            'display_name' => 'Private Applicant',
        ]);
    }

    public function test_private_form_invalid_or_disabled_link_returns_404(): void
    {
        $form = $this->privateForm();

        $this->get(route('private-forms.show', [
            'locale' => 'en',
            'form' => $form->slug,
            'token' => 'wrong-token',
        ]))->assertNotFound();

        $form->update(['is_active' => false]);

        $this->get(route('private-forms.show', [
            'locale' => 'en',
            'form' => $form->slug,
            'token' => $form->access_token,
        ]))->assertNotFound();
    }

    private function privateForm(): FormDefinition
    {
        return FormDefinition::create([
            'form_id' => 'private-board-nomination',
            'slug' => 'board-nomination',
            'title_en' => 'Board Nomination',
            'title_ar' => 'Board Nomination',
            'visibility' => FormDefinition::VISIBILITY_PRIVATE,
            'access_token' => Str::random(48),
            'is_active' => true,
        ]);
    }
}
