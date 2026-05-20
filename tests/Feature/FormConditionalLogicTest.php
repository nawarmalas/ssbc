<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSection;
use App\Services\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormConditionalLogicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Section 3 (seeded by data migration) has required checkbox_group fields.
     * Return answer entries that satisfy them so tests can isolate conditional logic.
     */
    private function section3Answers(): array
    {
        $section3Required = FormField::whereHas('section', fn ($q) => $q
            ->where('form_id', 'join-us')
            ->where('title_en', 'Section 3: Interests and Cooperation'))
            ->where('is_required', true)
            ->get();

        $answers = [];
        foreach ($section3Required as $field) {
            $firstOption = $field->options[0]['value'] ?? 'value';
            $answers[$field->id] = [0 => [$firstOption]];
        }
        return $answers;
    }

    private function buildForm(): array
    {
        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Company', 'title_ar' => 'شركة',
            'is_repeatable' => false, 'order_index' => 0,
        ]);

        $nameField = FormField::create([
            'section_id' => $section->id, 'label_en' => 'Full Name', 'label_ar' => 'الاسم',
            'field_type' => 'text', 'is_required' => true, 'is_active' => true, 'order_index' => 0,
        ]);

        $countryField = FormField::create([
            'section_id' => $section->id,
            'code'       => 'current_operations_country',
            'label_en'   => 'Current Operations Country',
            'label_ar'   => 'بلد العمليات',
            'field_type' => 'radio',
            'is_required'=> false,
            'is_active'  => true,
            'order_index'=> 1,
            'options'    => [
                ['value' => 'syria', 'label_en' => 'Syria',  'label_ar' => 'سوريا'],
                ['value' => 'other', 'label_en' => 'Other',  'label_ar' => 'أخرى'],
            ],
        ]);

        $otherField = FormField::create([
            'section_id'        => $section->id,
            'code'              => 'country_other_specify',
            'label_en'          => 'Country Name (if Other)',
            'label_ar'          => 'اسم البلد',
            'field_type'        => 'text',
            'is_required'       => true,
            'is_active'         => true,
            'order_index'       => 2,
            'conditional_logic' => [
                'operator'   => 'AND',
                'conditions' => [
                    ['field_code' => 'current_operations_country', 'op' => 'equals', 'value' => 'other'],
                ],
            ],
        ]);

        FormService::invalidateCache('join-us');

        return compact('section', 'nameField', 'countryField', 'otherField');
    }

    public function test_conditional_field_is_required_when_condition_met(): void
    {
        Mail::fake();
        ['nameField' => $name, 'countryField' => $country, 'otherField' => $other] = $this->buildForm();

        $response = $this->post('/en/join', [
            '_token'  => csrf_token(),
            'answers' => [
                $name->id    => [0 => 'Ahmad'],
                $country->id => [0 => 'other'],
            ],
            '_repeats' => [],
        ]);

        $response->assertSessionHasErrors(["answers.{$other->id}.0"]);
    }

    public function test_conditional_field_is_nullable_when_condition_not_met(): void
    {
        Mail::fake();
        ['nameField' => $name, 'countryField' => $country, 'otherField' => $other] = $this->buildForm();

        $response = $this->post('/en/join', [
            '_token'  => csrf_token(),
            'answers' => array_replace($this->section3Answers(), [
                $name->id    => [0 => 'Ahmad'],
                $country->id => [0 => 'syria'],
            ]),
            '_repeats' => [],
        ]);

        $response->assertRedirect('/en/join/thanks');
    }

    public function test_conditional_field_answer_not_stored_when_condition_false(): void
    {
        Mail::fake();
        ['nameField' => $name, 'countryField' => $country, 'otherField' => $other] = $this->buildForm();

        $this->post('/en/join', [
            '_token'  => csrf_token(),
            'answers' => array_replace($this->section3Answers(), [
                $name->id    => [0 => 'Ahmad'],
                $country->id => [0 => 'syria'],
                $other->id   => [0 => 'Some stale value'],
            ]),
            '_repeats' => [],
        ]);

        $this->assertDatabaseMissing('form_answers', ['field_id' => $other->id]);
    }

    public function test_required_checkbox_group_fails_when_empty(): void
    {
        Mail::fake();
        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Interests', 'title_ar' => 'اهتمامات',
            'is_repeatable' => false, 'order_index' => 0,
        ]);
        $checkboxField = FormField::create([
            'section_id' => $section->id,
            'label_en'   => 'Professional Profile',
            'label_ar'   => 'الملف المهني',
            'field_type' => 'checkbox_group',
            'is_required'=> true,
            'is_active'  => true,
            'order_index'=> 0,
            'options'    => [
                ['value' => 'investor', 'label_en' => 'Investor', 'label_ar' => 'مستثمر'],
            ],
        ]);
        FormService::invalidateCache('join-us');

        $response = $this->post('/en/join', [
            '_token'  => csrf_token(),
            'answers' => [$checkboxField->id => [0 => []]],
            '_repeats' => [],
        ]);

        $response->assertSessionHasErrors(["answers.{$checkboxField->id}.0"]);
    }

    public function test_required_checkbox_group_passes_when_has_selection(): void
    {
        Mail::fake();
        $section = FormSection::create([
            'form_id' => 'join-us', 'title_en' => 'Interests', 'title_ar' => 'اهتمامات',
            'is_repeatable' => false, 'order_index' => 0,
        ]);
        $checkboxField = FormField::create([
            'section_id' => $section->id,
            'label_en'   => 'Professional Profile',
            'label_ar'   => 'الملف المهني',
            'field_type' => 'checkbox_group',
            'is_required'=> true,
            'is_active'  => true,
            'order_index'=> 0,
            'options'    => [
                ['value' => 'investor', 'label_en' => 'Investor', 'label_ar' => 'مستثمر'],
            ],
        ]);
        FormService::invalidateCache('join-us');

        $response = $this->post('/en/join', [
            '_token'  => csrf_token(),
            'answers' => array_replace($this->section3Answers(), [
                $checkboxField->id => [0 => ['investor']],
            ]),
            '_repeats' => [],
        ]);

        $response->assertRedirect('/en/join/thanks');
    }
}
