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

        $response = $this->post('/en/join', [
            '_token' => csrf_token(),
            'answers' => [
                $nameField->id  => [0 => 'Ahmad Al-Souri'],
                $emailField->id => [0 => 'ahmad@example.com'],
            ],
            '_repeats' => [],
        ]);

        $response->assertRedirect('/en/join/thanks');
        $this->assertDatabaseHas('form_submissions', ['display_name' => 'Ahmad Al-Souri']);
        $this->assertDatabaseHas('form_answers', ['answer_value' => 'ahmad@example.com']);
    }
}
