<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Services\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class JoinFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_form_stores_submission_and_answers(): void
    {
        Mail::fake();

        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Personal', 'title_ar' => 'شخصي',
            'is_repeatable' => false, 'order_index' => 0,
        ]);
        $nameField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Full Name', 'label_ar' => 'الاسم',
            'field_type' => 'text', 'is_required' => true, 'is_active' => true, 'order_index' => 0,
        ]);
        $emailField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Email', 'label_ar' => 'البريد',
            'field_type' => 'email', 'is_required' => true, 'is_active' => true, 'order_index' => 1,
        ]);

        $answers = [
            $nameField->id  => [0 => 'Ahmad Al-Souri'],
            $emailField->id => [0 => 'ahmad@example.com'],
        ];

        // Section 3 (seeded by data migration) has required checkbox_group fields.
        $section3Required = FormField::whereHas('section', fn ($q) => $q
            ->where('form_id', 'join-us')
            ->where('title_en', 'Section 3: Interests and Cooperation'))
            ->where('is_required', true)
            ->get();

        foreach ($section3Required as $field) {
            $firstOption = $field->options[0]['value'] ?? 'value';
            $answers[$field->id] = [0 => [$firstOption]];
        }

        $response = $this->post('/en/join', [
            '_token' => csrf_token(),
            'answers' => $answers,
            '_repeats' => [],
        ]);

        $response->assertRedirect('/en/join/thanks');
        $this->assertDatabaseHas('form_submissions', ['display_name' => 'Ahmad Al-Souri']);
        $this->assertDatabaseHas('form_answers', ['answer_value' => 'ahmad@example.com']);
    }

    public function test_tel_field_accepts_00_international_prefix(): void
    {
        Mail::fake();

        $section = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Personal',
            'title_ar' => 'Personal',
            'is_repeatable' => false,
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

        $phoneField = FormField::create([
            'section_id' => $section->id,
            'label_en' => 'Mobile Number with Country Code',
            'label_ar' => 'Mobile Number with Country Code',
            'field_type' => 'tel',
            'is_required' => true,
            'is_active' => true,
            'order_index' => 1,
        ]);

        $this->post('/en/join', [
            '_token' => csrf_token(),
            'answers' => [
                $nameField->id => [0 => 'Ahmad Al-Souri'],
                $phoneField->id => [0 => '00966 50 000 0000'],
            ],
            '_repeats' => [],
        ])->assertRedirect('/en/join/thanks');

        $this->assertDatabaseHas('form_answers', [
            'field_id' => $phoneField->id,
            'answer_value' => '00966500000000',
        ]);
    }
}
