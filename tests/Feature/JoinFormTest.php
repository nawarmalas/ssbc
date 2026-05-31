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

    /**
     * The seeded join-us form (Section 3) carries required checkbox_group fields.
     * Any submission must satisfy them, so build valid answers for each.
     */
    private function seededRequiredAnswers(): array
    {
        $answers = [];
        $required = FormField::whereHas('section', fn ($q) => $q
                ->where('form_id', 'join-us')
                ->where('title_en', 'Section 3: Interests and Cooperation'))
            ->where('is_required', true)
            ->get();

        foreach ($required as $field) {
            $firstOption = $field->options[0]['value'] ?? 'value';
            $answers[$field->id] = [0 => [$firstOption]];
        }

        return $answers;
    }

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
            'answers' => $this->seededRequiredAnswers() + [
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

    public function test_tel_field_accepts_local_number_without_country_code(): void
    {
        Mail::fake();

        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Personal', 'title_ar' => 'Personal',
            'is_repeatable' => false, 'order_index' => 0,
        ]);
        $nameField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Full Name', 'label_ar' => 'Full Name',
            'field_type' => 'text', 'is_required' => true, 'is_active' => true, 'order_index' => 0,
        ]);
        $phoneField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Mobile Number', 'label_ar' => 'Mobile Number',
            'field_type' => 'tel', 'is_required' => true, 'is_active' => true, 'order_index' => 1,
        ]);

        // Local number with human formatting (spaces + dashes), no country code.
        $this->post('/en/join', [
            '_token' => csrf_token(),
            'answers' => $this->seededRequiredAnswers() + [
                $nameField->id => [0 => 'Ahmad Al-Souri'],
                $phoneField->id => [0 => '011 222-3333'],
            ],
            '_repeats' => [],
        ])->assertRedirect('/en/join/thanks');

        // Formatting characters are stripped; the local number is stored as-is.
        $this->assertDatabaseHas('form_answers', [
            'field_id' => $phoneField->id,
            'answer_value' => '0112223333',
        ]);
    }

    public function test_two_digit_establishment_year_is_expanded(): void
    {
        Mail::fake();

        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Company', 'title_ar' => 'Company',
            'is_repeatable' => false, 'order_index' => 0,
        ]);
        $nameField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Full Name', 'label_ar' => 'Full Name',
            'field_type' => 'text', 'is_required' => true, 'is_active' => true, 'order_index' => 0,
        ]);
        $yearField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Company Establishment Year', 'label_ar' => 'Year',
            'field_type' => 'number', 'is_required' => true, 'is_active' => true, 'order_index' => 1,
            'validation_rules' => ['min' => 1900, 'max' => (int) date('Y')],
        ]);

        // "96" should expand to "1996" rather than fail the 1900 minimum.
        $this->post('/en/join', [
            '_token' => csrf_token(),
            'answers' => $this->seededRequiredAnswers() + [
                $nameField->id => [0 => 'Ahmad Al-Souri'],
                $yearField->id => [0 => '96'],
            ],
            '_repeats' => [],
        ])->assertRedirect('/en/join/thanks');

        $this->assertDatabaseHas('form_answers', [
            'field_id' => $yearField->id,
            'answer_value' => '1996',
        ]);
    }

    public function test_url_field_accepts_address_without_scheme(): void
    {
        Mail::fake();

        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Company', 'title_ar' => 'Company',
            'is_repeatable' => false, 'order_index' => 0,
        ]);
        $nameField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Full Name', 'label_ar' => 'Full Name',
            'field_type' => 'text', 'is_required' => true, 'is_active' => true, 'order_index' => 0,
        ]);
        $urlField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'LinkedIn Profile Link', 'label_ar' => 'LinkedIn',
            'field_type' => 'url', 'is_required' => false, 'is_active' => true, 'order_index' => 1,
        ]);

        // "linkedin.com/in/ahmad" (no scheme) should be accepted and stored with https://.
        $this->post('/en/join', [
            '_token' => csrf_token(),
            'answers' => $this->seededRequiredAnswers() + [
                $nameField->id => [0 => 'Ahmad Al-Souri'],
                $urlField->id => [0 => 'linkedin.com/in/ahmad'],
            ],
            '_repeats' => [],
        ])->assertRedirect('/en/join/thanks');

        $this->assertDatabaseHas('form_answers', [
            'field_id' => $urlField->id,
            'answer_value' => 'https://linkedin.com/in/ahmad',
        ]);
    }

    public function test_server_validation_messages_are_localised_in_arabic(): void
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
        $phoneField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Mobile', 'label_ar' => 'الجوال',
            'field_type' => 'tel', 'is_required' => true, 'is_active' => true, 'order_index' => 1,
        ]);

        // Post an un-phone-like value to the Arabic form; the error must be Arabic.
        $response = $this->post('/ar/join', [
            '_token' => csrf_token(),
            'answers' => $this->seededRequiredAnswers() + [
                $nameField->id => [0 => 'أحمد'],
                $phoneField->id => [0 => 'not-a-phone'],
            ],
            '_repeats' => [],
        ]);

        $response->assertSessionHasErrors([
            "answers.{$phoneField->id}.0" => __('join.js.phone_server', [], 'ar'),
        ]);
    }
}
