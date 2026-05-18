# Dynamic Form Builder & Submissions Manager — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the hardcoded 4-step join form with a fully dynamic, admin-managed form system backed by a relational database, with a visual form builder, repeatable company section, PDF/Excel export, and email notifications.

**Architecture:** Alpine.js + SortableJS form builder (all mutations via Fetch/JSON API), dynamic Blade renderer for the public form, queued mail jobs for notifications. `FormService` is the single source of truth for form structure, with a 5-minute cache keyed `form:join-us:sections`.

**Tech Stack:** Laravel 11, Alpine.js, SortableJS (CDN), barryvdh/laravel-dompdf, maatwebsite/laravel-excel, Laravel Mail (queued)

**Spec:** `docs/superpowers/specs/2026-05-18-dynamic-form-builder-design.md`

---

## File Map

### New files
| File | Responsibility |
|------|---------------|
| `database/migrations/2026_05_18_000000_create_form_tables.php` | Creates 5 tables |
| `app/Models/FormSection.php` | Section model + relationships |
| `app/Models/FormField.php` | Field model + casts |
| `app/Models/FormSubmission.php` | Submission model + helpers |
| `app/Models/FormAnswer.php` | Answer model |
| `app/Models/FormUpload.php` | Upload model |
| `app/Services/FormService.php` | `getActiveForm()`, `invalidateCache()` |
| `app/Http/Controllers/Admin/FormBuilderController.php` | All form builder API + preview |
| `app/Http/Controllers/Admin/SubmissionController.php` | Submission CRUD + PDF + Excel |
| `app/Exports/SubmissionsExport.php` | Excel export class |
| `app/Mail/AdminSubmissionNotification.php` | Admin email mailable |
| `app/Mail/ApplicantConfirmation.php` | Applicant email mailable |
| `database/seeders/FormSeeder.php` | Seeds all 20 join-us fields |
| `resources/views/admin/form-builder/index.blade.php` | Builder UI (Alpine accordion) |
| `resources/views/admin/submissions/index.blade.php` | Submissions table |
| `resources/views/admin/submissions/show.blade.php` | Submission detail |
| `resources/views/admin/submissions/pdf.blade.php` | DomPDF template |
| `resources/views/mail/admin-notification.blade.php` | Admin email HTML |
| `resources/views/mail/applicant-confirmation.blade.php` | Applicant email HTML |
| `tests/Feature/FormBuilderTest.php` | API endpoint tests |
| `tests/Feature/JoinFormTest.php` | Public form submission tests |

### Modified files
| File | Change |
|------|--------|
| `routes/web.php` | Add form-builder + submission routes |
| `app/Http/Controllers/JoinController.php` | Rewrite `create()` + `store()` |
| `resources/views/join/create.blade.php` | Rewrite as dynamic renderer |
| `resources/views/layouts/admin.blade.php` | Add Form Builder + Submissions nav tabs |
| `database/seeders/DatabaseSeeder.php` | Call `FormSeeder` |
| `config/services.php` | Add `google_script_url` |
| `.env.example` | Add `GOOGLE_SCRIPT_URL=` |

---

## Task 1: Install Packages + Bootstrap Tests

**Files:**
- Modify: `composer.json` (via composer)
- Create: `phpunit.xml`
- Create: `tests/TestCase.php`
- Create: `tests/Feature/FormBuilderTest.php` (skeleton)
- Create: `tests/Feature/JoinFormTest.php` (skeleton)

- [ ] **Step 1: Install packages**

```bash
cd C:\LevareLabs\ssbc
composer require barryvdh/laravel-dompdf maatwebsite/excel
```

Expected: both packages installed, `composer.json` updated.

- [ ] **Step 2: Publish DomPDF config**

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

Expected: `config/dompdf.php` created.

- [ ] **Step 3: Create phpunit.xml**

Create `C:\LevareLabs\ssbc\phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_KEY" value="base64:test_key_32_chars_padded_here=="/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="MAIL_MAILER" value="array"/>
    </php>
</phpunit>
```

- [ ] **Step 4: Create tests/TestCase.php**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {}
```

- [ ] **Step 5: Create tests/Feature/FormBuilderTest.php (skeleton)**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->create();
        return $this->actingAs($admin);
    }
}
```

- [ ] **Step 6: Create tests/Feature/JoinFormTest.php (skeleton)**

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoinFormTest extends TestCase
{
    use RefreshDatabase;
}
```

- [ ] **Step 7: Run tests to confirm setup**

```bash
php artisan test
```

Expected: `Tests: 0 passed` (no tests yet, but setup works).

- [ ] **Step 8: Commit**

```bash
git add phpunit.xml tests/ composer.json composer.lock config/dompdf.php
git commit -m "feat: install dompdf/excel packages, bootstrap test suite"
```

---

## Task 2: Database Migration

**Files:**
- Create: `database/migrations/2026_05_18_000000_create_form_tables.php`

- [ ] **Step 1: Create the migration file**

```bash
php artisan make:migration create_form_tables --create=form_sections
```

Then replace the generated file with:

`database/migrations/2026_05_18_000000_create_form_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->string('form_id', 64)->index();
            $table->string('title_en');
            $table->string('title_ar');
            $table->boolean('is_repeatable')->default(false);
            $table->unsignedTinyInteger('max_repeats')->default(5);
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('form_sections')->cascadeOnDelete();
            $table->string('label_en');
            $table->string('label_ar');
            $table->string('placeholder_en')->nullable();
            $table->string('placeholder_ar')->nullable();
            $table->enum('field_type', [
                'text', 'textarea', 'email', 'tel', 'number', 'date',
                'select', 'radio', 'checkbox_group', 'file', 'url', 'declaration',
            ]);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('file_config')->nullable();
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form_id', 64)->index();
            $table->string('display_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->unsignedBigInteger('field_id');
            $table->unsignedTinyInteger('repeat_index')->default(0);
            $table->text('answer_value')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['submission_id', 'field_id', 'repeat_index']);
        });

        Schema::create('form_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->unsignedBigInteger('field_id');
            $table->unsignedTinyInteger('repeat_index')->default(0);
            $table->string('file_path', 500);
            $table->string('file_name');
            $table->unsignedInteger('file_size');
            $table->timestamp('created_at')->useCurrent();

            $table->index('submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_uploads');
        Schema::dropIfExists('form_answers');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_sections');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: 5 new tables created. No errors.

- [ ] **Step 3: Add migration test**

In `tests/Feature/FormBuilderTest.php`, add to the class:

```php
public function test_form_tables_exist(): void
{
    $this->assertTrue(Schema::hasTable('form_sections'));
    $this->assertTrue(Schema::hasTable('form_fields'));
    $this->assertTrue(Schema::hasTable('form_submissions'));
    $this->assertTrue(Schema::hasTable('form_answers'));
    $this->assertTrue(Schema::hasTable('form_uploads'));
}
```

Add the import at the top:
```php
use Illuminate\Support\Facades\Schema;
```

- [ ] **Step 4: Run test to confirm**

```bash
php artisan test tests/Feature/FormBuilderTest.php
```

Expected: `1 passed`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ tests/Feature/FormBuilderTest.php
git commit -m "feat: add form builder database migration"
```

---

## Task 3: Eloquent Models

**Files:**
- Create: `app/Models/FormSection.php`
- Create: `app/Models/FormField.php`
- Create: `app/Models/FormSubmission.php`
- Create: `app/Models/FormAnswer.php`
- Create: `app/Models/FormUpload.php`

- [ ] **Step 1: Create FormSection.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSection extends Model
{
    protected $fillable = [
        'form_id', 'title_en', 'title_ar',
        'is_repeatable', 'max_repeats', 'order_index',
    ];

    protected function casts(): array
    {
        return [
            'is_repeatable' => 'boolean',
            'max_repeats' => 'integer',
            'order_index' => 'integer',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'section_id')
            ->where('is_active', true)
            ->orderBy('order_index');
    }

    public function allFields(): HasMany
    {
        return $this->hasMany(FormField::class, 'section_id')->orderBy('order_index');
    }
}
```

- [ ] **Step 2: Create FormField.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $fillable = [
        'section_id', 'label_en', 'label_ar',
        'placeholder_en', 'placeholder_ar',
        'field_type', 'is_required', 'is_active', 'order_index',
        'options', 'validation_rules', 'file_config',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'order_index' => 'integer',
            'options' => 'array',
            'validation_rules' => 'array',
            'file_config' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'section_id');
    }

    public function acceptedMimes(): string
    {
        $types = $this->file_config['accepted_types'] ?? ['pdf', 'jpg', 'jpeg', 'png'];
        return implode(',', $types);
    }

    public function maxFileSizeKb(): int
    {
        $mb = $this->file_config['max_size_mb'] ?? 5;
        return $mb * 1024;
    }
}
```

- [ ] **Step 3: Create FormSubmission.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id', 'display_name', 'ip_address',
        'status', 'admin_notes', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormAnswer::class, 'submission_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(FormUpload::class, 'submission_id');
    }

    /** Returns the answer value for a given field_id and optional repeat_index. */
    public function answerFor(int $fieldId, int $repeatIndex = 0): ?string
    {
        return $this->answers
            ->where('field_id', $fieldId)
            ->where('repeat_index', $repeatIndex)
            ->value('answer_value');
    }

    /** Returns all uploads for a given field_id and repeat_index. */
    public function uploadsFor(int $fieldId, int $repeatIndex = 0)
    {
        return $this->uploads
            ->where('field_id', $fieldId)
            ->where('repeat_index', $repeatIndex);
    }
}
```

- [ ] **Step 4: Create FormAnswer.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = ['submission_id', 'field_id', 'repeat_index', 'answer_value'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }
}
```

- [ ] **Step 5: Create FormUpload.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FormUpload extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'submission_id', 'field_id', 'repeat_index',
        'file_path', 'file_name', 'file_size',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/Form*.php
git commit -m "feat: add FormSection, FormField, FormSubmission, FormAnswer, FormUpload models"
```

---

## Task 4: FormService

**Files:**
- Create: `app/Services/FormService.php`

- [ ] **Step 1: Write the failing test**

In `tests/Feature/FormBuilderTest.php`, add:

```php
use App\Models\FormSection;
use App\Models\FormField;
use App\Services\FormService;

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
    $this->assertCount(1, $form->first()->fields); // inactive field excluded
}

public function test_get_active_form_is_cached(): void
{
    FormSection::create([
        'form_id' => 'join-us', 'title_en' => 'S1', 'title_ar' => 'ق1', 'order_index' => 0,
    ]);

    FormService::getActiveForm('join-us');
    FormSection::query()->delete(); // nuke DB
    $form = FormService::getActiveForm('join-us'); // should still return from cache

    $this->assertCount(1, $form);
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/FormBuilderTest.php --filter=test_get_active_form
```

Expected: FAIL — `FormService` class not found.

- [ ] **Step 3: Create FormService.php**

```php
<?php

namespace App\Services;

use App\Models\FormSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class FormService
{
    public static function getActiveForm(string $formId): Collection
    {
        return Cache::remember(
            "form:{$formId}:sections",
            300,
            fn () => FormSection::with(['fields' => function ($q) {
                $q->where('is_active', true)->orderBy('order_index');
            }])
                ->where('form_id', $formId)
                ->orderBy('order_index')
                ->get()
        );
    }

    public static function invalidateCache(string $formId = 'join-us'): void
    {
        Cache::forget("form:{$formId}:sections");
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test tests/Feature/FormBuilderTest.php --filter=test_get_active_form
```

Expected: 2 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/FormService.php tests/Feature/FormBuilderTest.php
git commit -m "feat: add FormService with cache-backed getActiveForm()"
```

---

## Task 5: FormSeeder

**Files:**
- Create: `database/seeders/FormSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create the seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\FormField;
use App\Models\FormSection;
use App\Services\FormService;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        if (FormSection::where('form_id', 'join-us')->exists()) {
            return;
        }

        // ── Section 1: Personal Information ─────────────────────────────────
        $personal = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Personal Information',
            'title_ar' => 'المعلومات الشخصية',
            'is_repeatable' => false,
            'order_index' => 0,
        ]);

        $personalFields = [
            ['label_en' => 'Full Name in Arabic (as in Passport)', 'label_ar' => 'الاسم الكامل بالعربية (كما في جواز السفر)', 'field_type' => 'text', 'is_required' => true, 'placeholder_en' => '', 'placeholder_ar' => ''],
            ['label_en' => 'Full Name in English (as in Passport)', 'label_ar' => 'الاسم الكامل بالإنجليزية (كما في جواز السفر)', 'field_type' => 'text', 'is_required' => true, 'placeholder_en' => '', 'placeholder_ar' => ''],
            ['label_en' => 'Date of Birth', 'label_ar' => 'تاريخ الميلاد', 'field_type' => 'date', 'is_required' => true, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'Current Position', 'label_ar' => 'المسمى الوظيفي الحالي', 'field_type' => 'text', 'is_required' => true, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'Mobile Number with Country Code', 'label_ar' => 'رقم الجوال مع رمز الدولة', 'field_type' => 'tel', 'is_required' => true, 'placeholder_en' => '+966 50 123 4567', 'placeholder_ar' => '+966 50 123 4567'],
            ['label_en' => 'Email Address', 'label_ar' => 'البريد الإلكتروني', 'field_type' => 'email', 'is_required' => true, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'Home Address', 'label_ar' => 'العنوان السكني', 'field_type' => 'textarea', 'is_required' => false, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'LinkedIn Profile Link', 'label_ar' => 'رابط الملف الشخصي على لينكد إن', 'field_type' => 'url', 'is_required' => false, 'placeholder_en' => 'https://linkedin.com/in/yourprofile', 'placeholder_ar' => null],
        ];

        foreach ($personalFields as $i => $field) {
            FormField::create(array_merge($field, ['section_id' => $personal->id, 'order_index' => $i, 'is_active' => true]));
        }

        // ── Section 2: Company Information (repeatable) ──────────────────────
        $sectors = [
            ['label_en' => 'Agriculture and Livestock', 'label_ar' => 'الزراعة والثروة الحيوانية', 'value' => 'agriculture'],
            ['label_en' => 'Oil and Mineral Resources', 'label_ar' => 'النفط والموارد المعدنية', 'value' => 'oil'],
            ['label_en' => 'Electricity and Water', 'label_ar' => 'الكهرباء والمياه', 'value' => 'electricity'],
            ['label_en' => 'Health and Pharmaceuticals', 'label_ar' => 'الصحة والدواء', 'value' => 'health'],
            ['label_en' => 'Real Estate Development and Construction', 'label_ar' => 'التطوير العقاري والإنشاء', 'value' => 'realestate'],
            ['label_en' => 'Education and Training', 'label_ar' => 'التعليم والتدريب', 'value' => 'education'],
            ['label_en' => 'Tourism', 'label_ar' => 'السياحة', 'value' => 'tourism'],
            ['label_en' => 'Drama and Media', 'label_ar' => 'الدراما والإعلام', 'value' => 'media'],
            ['label_en' => 'Development Work', 'label_ar' => 'العمل التنموي', 'value' => 'development'],
            ['label_en' => 'Transport and Logistics', 'label_ar' => 'النقل واللوجستيات', 'value' => 'transport'],
            ['label_en' => 'Telecommunications / IT / Business Incubators', 'label_ar' => 'الاتصالات وتقنية المعلومات وحاضنات الأعمال', 'value' => 'telecom'],
            ['label_en' => 'Legal Consulting and Services', 'label_ar' => 'الاستشارات والخدمات القانونية', 'value' => 'legal'],
        ];

        $sizeOptions = [
            ['label_en' => '1–10 employees', 'label_ar' => '1–10 موظفين', 'value' => '1-10'],
            ['label_en' => '11–50 employees', 'label_ar' => '11–50 موظفاً', 'value' => '11-50'],
            ['label_en' => '51–200 employees', 'label_ar' => '51–200 موظف', 'value' => '51-200'],
            ['label_en' => '200+ employees', 'label_ar' => 'أكثر من 200 موظف', 'value' => '200+'],
        ];

        $countryOptions = [
            ['label_en' => 'Syria', 'label_ar' => 'سوريا', 'value' => 'syria'],
            ['label_en' => 'KSA', 'label_ar' => 'المملكة العربية السعودية', 'value' => 'ksa'],
            ['label_en' => 'Both', 'label_ar' => 'كلاهما', 'value' => 'both'],
            ['label_en' => 'Other', 'label_ar' => 'أخرى', 'value' => 'other'],
        ];

        $company = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Company Information',
            'title_ar' => 'معلومات الشركة',
            'is_repeatable' => true,
            'max_repeats' => 5,
            'order_index' => 1,
        ]);

        $companyFields = [
            ['label_en' => 'Company Name', 'label_ar' => 'اسم الشركة', 'field_type' => 'text', 'is_required' => true, 'options' => null, 'validation_rules' => null],
            ['label_en' => 'Company Establishment Year', 'label_ar' => 'سنة تأسيس الشركة', 'field_type' => 'number', 'is_required' => true, 'options' => null, 'validation_rules' => ['min' => 1900, 'max' => (int) date('Y')]],
            ['label_en' => 'Company Size', 'label_ar' => 'حجم الشركة', 'field_type' => 'radio', 'is_required' => true, 'options' => $sizeOptions, 'validation_rules' => null],
            ['label_en' => 'Business Address', 'label_ar' => 'عنوان الشركة', 'field_type' => 'textarea', 'is_required' => true, 'options' => null, 'validation_rules' => null],
            ['label_en' => 'Company Website', 'label_ar' => 'الموقع الإلكتروني للشركة', 'field_type' => 'url', 'is_required' => false, 'options' => null, 'validation_rules' => null],
            ['label_en' => 'Current Operations Country', 'label_ar' => 'بلد العمليات الحالية', 'field_type' => 'radio', 'is_required' => false, 'options' => $countryOptions, 'validation_rules' => null],
            ['label_en' => 'Sectors of Operation', 'label_ar' => 'قطاعات العمل', 'field_type' => 'checkbox_group', 'is_required' => true, 'options' => $sectors, 'validation_rules' => null],
        ];

        foreach ($companyFields as $i => $field) {
            FormField::create(array_merge($field, [
                'section_id' => $company->id,
                'order_index' => $i,
                'is_active' => true,
                'placeholder_en' => null,
                'placeholder_ar' => null,
                'file_config' => null,
            ]));
        }

        // ── Section 3: Required Documents ───────────────────────────────────
        $docs = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Required Documents',
            'title_ar' => 'المستندات المطلوبة',
            'is_repeatable' => false,
            'order_index' => 2,
        ]);

        $docFields = [
            [
                'label_en' => 'Copy of ID or Passport',
                'label_ar' => 'نسخة من الهوية أو جواز السفر',
                'field_type' => 'file',
                'is_required' => true,
                'file_config' => ['accepted_types' => ['pdf', 'jpg', 'jpeg', 'png'], 'max_size_mb' => 5],
            ],
            [
                'label_en' => 'Commercial Registry or Trade License',
                'label_ar' => 'السجل التجاري أو الرخصة التجارية',
                'field_type' => 'file',
                'is_required' => true,
                'file_config' => ['accepted_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'], 'max_size_mb' => 5],
            ],
            [
                'label_en' => 'Company Profile',
                'label_ar' => 'ملف تعريف الشركة',
                'field_type' => 'file',
                'is_required' => false,
                'file_config' => ['accepted_types' => ['pdf', 'doc', 'docx'], 'max_size_mb' => 5],
            ],
        ];

        foreach ($docFields as $i => $field) {
            FormField::create(array_merge($field, [
                'section_id' => $docs->id,
                'order_index' => $i,
                'is_active' => true,
                'placeholder_en' => null,
                'placeholder_ar' => null,
                'options' => null,
                'validation_rules' => null,
            ]));
        }

        // ── Section 4: Declaration ───────────────────────────────────────────
        $decl = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Declaration',
            'title_ar' => 'الإقرار',
            'is_repeatable' => false,
            'order_index' => 3,
        ]);

        FormField::create([
            'section_id' => $decl->id,
            'label_en' => 'I declare that all provided information and attached documents are true and accurate, and I consent to sharing them with the SSBC committees for evaluation.',
            'label_ar' => 'أُقرّ بأن جميع المعلومات والمستندات المقدمة صحيحة ودقيقة، وأوافق على مشاركتها مع لجان المجلس للتقييم.',
            'field_type' => 'declaration',
            'is_required' => true,
            'is_active' => true,
            'order_index' => 0,
            'placeholder_en' => null,
            'placeholder_ar' => null,
            'options' => null,
            'validation_rules' => null,
            'file_config' => null,
        ]);

        FormService::invalidateCache('join-us');
    }
}
```

- [ ] **Step 2: Call FormSeeder from DatabaseSeeder**

In `database/seeders/DatabaseSeeder.php`, add after the existing seeding:

```php
$this->call(FormSeeder::class);
```

Full updated `run()` method:

```php
public function run(): void
{
    User::updateOrCreate(
        ['email' => 'admin@ssbc.org'],
        [
            'name' => 'SSBC Admin',
            'password' => Hash::make('Admin1234!'),
        ]
    );

    if (! SiteSetting::query()->exists()) {
        SiteSetting::create([
            'contact_email' => 'info@ssbc.org',
            'contact_phone' => '+966 11 000 0000',
            'address_en' => 'Riyadh, Kingdom of Saudi Arabia',
            'address_ar' => 'الرياض، المملكة العربية السعودية',
            'linkedin_url' => null,
            'twitter_url' => null,
            'footer_desc_en' => 'The Syrian Saudi Business Council is a formal bilateral institution dedicated to building enduring economic ties between the Syrian and Saudi business communities.',
            'footer_desc_ar' => 'مجلس الأعمال السوري السعودي هو مؤسسة ثنائية رسمية مكرسة لبناء روابط اقتصادية دائمة بين مجتمعَي الأعمال السوري والسعودي.',
        ]);
    }

    $this->call(FormSeeder::class);
}
```

Add the import at the top of DatabaseSeeder.php:
```php
use Database\Seeders\FormSeeder;
```

- [ ] **Step 3: Run the seeder**

```bash
php artisan db:seed --class=FormSeeder
```

Expected: No errors. Check DB — `form_sections` should have 4 rows, `form_fields` should have 20 rows.

- [ ] **Step 4: Verify in tinker**

```bash
php artisan tinker
>>> App\Models\FormSection::with('fields')->where('form_id','join-us')->get()->map(fn($s) => [$s->title_en, $s->fields->count()])
```

Expected:
```
[["Personal Information",8],["Company Information",7],["Required Documents",3],["Declaration",1]]
```

- [ ] **Step 5: Commit**

```bash
git add database/seeders/
git commit -m "feat: add FormSeeder with all 19 join-us fields (4 sections)"
```

---

## Task 6: FormBuilderController — Section CRUD

**Files:**
- Create: `app/Http/Controllers/Admin/FormBuilderController.php`

- [ ] **Step 1: Create the controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\FormSection;
use App\Services\FormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FormBuilderController extends Controller
{
    private const FORM_ID = 'join-us';

    private const FIELD_TYPES = [
        'text', 'textarea', 'email', 'tel', 'number', 'date',
        'select', 'radio', 'checkbox_group', 'file', 'url', 'declaration',
    ];

    // ── Index ────────────────────────────────────────────────────────────────

    public function index()
    {
        $sections = FormSection::with('allFields')
            ->where('form_id', self::FORM_ID)
            ->orderBy('order_index')
            ->get();

        return view('admin.form-builder.index', [
            'sectionsJson' => $sections->toJson(),
            'fieldTypes'   => self::FIELD_TYPES,
        ]);
    }

    public function preview()
    {
        $form = FormService::getActiveForm(self::FORM_ID);
        return view('join.create', ['form' => $form, 'preview' => true]);
    }

    // ── Sections ─────────────────────────────────────────────────────────────

    public function storeSection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title_en'      => ['required', 'string', 'max:255'],
            'title_ar'      => ['required', 'string', 'max:255'],
            'is_repeatable' => ['boolean'],
            'max_repeats'   => ['integer', 'min:2', 'max:10'],
        ]);

        $maxOrder = FormSection::where('form_id', self::FORM_ID)->max('order_index') ?? -1;

        $section = FormSection::create(array_merge($data, [
            'form_id'     => self::FORM_ID,
            'order_index' => $maxOrder + 1,
        ]));

        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function updateSection(Request $request, FormSection $section): JsonResponse
    {
        $data = $request->validate([
            'title_en'      => ['required', 'string', 'max:255'],
            'title_ar'      => ['required', 'string', 'max:255'],
            'is_repeatable' => ['boolean'],
            'max_repeats'   => ['integer', 'min:2', 'max:10'],
        ]);

        $section->update($data);
        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function destroySection(Request $request, FormSection $section): JsonResponse
    {
        $fieldCount = $section->allFields()->count();

        if ($fieldCount > 0 && ! $request->boolean('force')) {
            return response()->json(['success' => false, 'has_fields' => true, 'count' => $fieldCount]);
        }

        $section->delete(); // cascade deletes fields
        FormService::invalidateCache();

        return response()->json(['success' => true]);
    }

    public function reorderSections(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items'             => ['required', 'array'],
            'items.*.id'        => ['required', 'integer'],
            'items.*.order_index' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            FormSection::where('id', $item['id'])->update(['order_index' => $item['order_index']]);
        }

        FormService::invalidateCache();

        return response()->json(['success' => true]);
    }
```

- [ ] **Step 2: Add section CRUD tests**

In `tests/Feature/FormBuilderTest.php`, add:

```php
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

    // Without force — should refuse
    $this->actingAsAdmin()
        ->deleteJson("/admin/forms/join-us/sections/{$section->id}")
        ->assertJson(['success' => false, 'has_fields' => true]);

    // With force — should delete
    $this->actingAsAdmin()
        ->deleteJson("/admin/forms/join-us/sections/{$section->id}?force=1")
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('form_sections', ['id' => $section->id]);
}
```

- [ ] **Step 3: Run tests (expect route-not-found failure)**

```bash
php artisan test tests/Feature/FormBuilderTest.php --filter=test_admin_can
```

Expected: FAIL — routes not registered yet (added in Task 9).

- [ ] **Step 4: Commit controller progress**

```bash
git add app/Http/Controllers/Admin/FormBuilderController.php tests/Feature/FormBuilderTest.php
git commit -m "feat: add FormBuilderController section CRUD methods"
```

---

## Task 7: FormBuilderController — Field CRUD

**Files:**
- Modify: `app/Http/Controllers/Admin/FormBuilderController.php` (add field methods)

- [ ] **Step 1: Add field methods to FormBuilderController**

Append these methods inside the class (after `reorderSections`):

```php
    // ── Fields ───────────────────────────────────────────────────────────────

    public function storeField(Request $request): JsonResponse
    {
        $data = $this->validateField($request);

        $maxOrder = FormField::where('section_id', $data['section_id'])->max('order_index') ?? -1;
        $data['order_index'] = $maxOrder + 1;

        $field = FormField::create($data);
        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $field]);
    }

    public function updateField(Request $request, FormField $field): JsonResponse
    {
        $data = $this->validateField($request, $field);
        $field->update($data);
        FormService::invalidateCache();

        return response()->json(['success' => true, 'data' => $field->fresh()]);
    }

    public function destroyField(FormField $field): JsonResponse
    {
        $field->delete();
        FormService::invalidateCache();

        return response()->json(['success' => true]);
    }

    public function reorderFields(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items'               => ['required', 'array'],
            'items.*.id'          => ['required', 'integer'],
            'items.*.order_index' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            FormField::where('id', $item['id'])->update(['order_index' => $item['order_index']]);
        }

        FormService::invalidateCache();

        return response()->json(['success' => true]);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function validateField(Request $request, ?FormField $existing = null): array
    {
        $data = $request->validate([
            'section_id'      => ['required', 'integer', 'exists:form_sections,id'],
            'label_en'        => ['required', 'string', 'max:500'],
            'label_ar'        => ['required', 'string', 'max:500'],
            'placeholder_en'  => ['nullable', 'string', 'max:255'],
            'placeholder_ar'  => ['nullable', 'string', 'max:255'],
            'field_type'      => ['required', Rule::in(self::FIELD_TYPES)],
            'is_required'     => ['boolean'],
            'is_active'       => ['boolean'],
            'options'         => ['nullable', 'array'],
            'options.*.label_en' => ['required_with:options', 'string'],
            'options.*.label_ar' => ['required_with:options', 'string'],
            'options.*.value'    => ['required_with:options', 'string'],
            'validation_rules' => ['nullable', 'array'],
            'file_config'      => ['nullable', 'array'],
            'file_config.accepted_types' => ['nullable', 'array'],
            'file_config.max_size_mb'    => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        // Sanitize text fields
        foreach (['label_en', 'label_ar', 'placeholder_en', 'placeholder_ar'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = strip_tags($data[$key]);
            }
        }

        return $data;
    }
}
```

- [ ] **Step 2: Add field CRUD test**

In `tests/Feature/FormBuilderTest.php`, add:

```php
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
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/FormBuilderController.php tests/Feature/FormBuilderTest.php
git commit -m "feat: add FormBuilderController field CRUD and reorder methods"
```

---

## Task 8: Routes — Form Builder + Submissions

**Files:**
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/admin.blade.php`

- [ ] **Step 1: Add form builder routes to web.php**

Inside the `Route::middleware('auth')->group(...)` block (after the existing `settings` route, before the closing `});`), add:

```php
        // Form Builder
        Route::get('/forms/join-us', [FormBuilderController::class, 'index'])->name('forms.builder');
        Route::get('/forms/join-us/preview', [FormBuilderController::class, 'preview'])->name('forms.preview');
        Route::post('/forms/join-us/sections', [FormBuilderController::class, 'storeSection'])->name('forms.sections.store');
        Route::put('/forms/join-us/sections/{section}', [FormBuilderController::class, 'updateSection'])->name('forms.sections.update');
        Route::delete('/forms/join-us/sections/{section}', [FormBuilderController::class, 'destroySection'])->name('forms.sections.destroy');
        Route::post('/forms/join-us/sections/reorder', [FormBuilderController::class, 'reorderSections'])->name('forms.sections.reorder');
        Route::post('/forms/join-us/fields', [FormBuilderController::class, 'storeField'])->name('forms.fields.store');
        Route::put('/forms/join-us/fields/{field}', [FormBuilderController::class, 'updateField'])->name('forms.fields.update');
        Route::delete('/forms/join-us/fields/{field}', [FormBuilderController::class, 'destroyField'])->name('forms.fields.destroy');
        Route::post('/forms/join-us/fields/reorder', [FormBuilderController::class, 'reorderFields'])->name('forms.fields.reorder');

        // Submissions
        Route::get('/submissions/export', [SubmissionController::class, 'export'])->name('submissions.export');
        Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::patch('/submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
        Route::get('/submissions/{submission}/pdf', [SubmissionController::class, 'pdf'])->name('submissions.pdf');
```

Note: `/submissions/export` must be declared **before** `/submissions/{submission}` to avoid route collision.

- [ ] **Step 2: Add imports at top of web.php**

Add to the use block at the top of `routes/web.php`:

```php
use App\Http\Controllers\Admin\FormBuilderController;
use App\Http\Controllers\Admin\SubmissionController;
```

- [ ] **Step 3: Add nav tabs to admin layout**

In `resources/views/layouts/admin.blade.php`, replace the `$tabs` array (lines 31–38) with:

```php
$tabs = [
    ['route' => 'admin.dashboard',      'label' => __('admin.dashboard')],
    ['route' => 'admin.news.index',     'label' => __('admin.news')],
    ['route' => 'admin.forms.builder',  'label' => 'Form Builder'],
    ['route' => 'admin.submissions.index', 'label' => 'Submissions'],
    ['route' => 'admin.join.index',     'label' => __('admin.join')],
    ['route' => 'admin.contact.index',  'label' => __('admin.contact')],
    ['route' => 'admin.membership.index', 'label' => __('admin.membership')],
    ['route' => 'admin.settings.edit',  'label' => __('admin.settings')],
];
```

- [ ] **Step 4: Run FormBuilder tests (now routes exist)**

```bash
php artisan test tests/Feature/FormBuilderTest.php
```

Expected: All pass (routes now registered).

- [ ] **Step 5: Commit**

```bash
git add routes/web.php resources/views/layouts/admin.blade.php
git commit -m "feat: register form builder and submissions routes, add admin nav tabs"
```

---

## Task 9: Admin Form Builder Blade View

**Files:**
- Create: `resources/views/admin/form-builder/index.blade.php`

- [ ] **Step 1: Create the view**

```blade
@extends('layouts.admin')

@section('title', 'Form Builder — Join Us')

@section('content')
<div class="w-12 h-px bg-ssbc-gold mb-4"></div>
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-display font-bold text-ssbc-green">Form Builder — Join Us</h1>
    <div class="flex gap-3">
        <a href="{{ route('admin.forms.preview') }}" target="_blank"
           class="ssbc-btn-outline-dark text-sm">Preview Form ↗</a>
    </div>
</div>

<div x-data="formBuilder()" x-init="init()">

    {{-- Add Section --}}
    <div class="mb-4 flex justify-end">
        <button type="button" @click="openSectionModal(null)"
                class="ssbc-btn-primary text-sm">+ Add Section</button>
    </div>

    {{-- Sections accordion --}}
    <div x-ref="sectionsList" class="space-y-3">
        <template x-for="section in sections" :key="section.id">
            <div class="ssbc-admin-card" :data-id="section.id">

                {{-- Section header --}}
                <div class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none"
                     @click="toggleSection(section.id)">
                    <span class="drag-handle cursor-grab text-ssbc-sage/60 hover:text-ssbc-sage text-lg leading-none">⠿</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-ssbc-dark text-sm" x-text="section.title_en"></p>
                        <p class="text-xs text-ssbc-sage" dir="rtl" x-text="section.title_ar"></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span x-show="section.is_repeatable"
                              class="text-xs bg-ssbc-gold/20 text-ssbc-green px-2 py-0.5 rounded-full">Repeatable</span>
                        <span class="text-xs text-ssbc-sage" x-text="(section.all_fields?.length ?? 0) + ' fields'"></span>
                        <button type="button" @click.stop="openSectionModal(section)"
                                class="text-xs text-ssbc-sage hover:text-ssbc-green px-2">Edit</button>
                        <button type="button" @click.stop="confirmDeleteSection(section)"
                                class="text-xs text-red-500 hover:text-red-700 px-2">Delete</button>
                        <span class="text-ssbc-sage" x-text="openSections.includes(section.id) ? '▲' : '▼'"></span>
                    </div>
                </div>

                {{-- Section body (fields) --}}
                <div x-show="openSections.includes(section.id)" x-cloak
                     class="border-t border-ssbc-green/10 px-4 py-4">

                    {{-- Fields list --}}
                    <div :id="'fields-' + section.id" class="space-y-2 mb-4">
                        <template x-for="field in section.all_fields" :key="field.id">
                            <div class="flex items-center gap-2 p-3 bg-ssbc-light border border-ssbc-green/10 rounded"
                                 :data-id="field.id">
                                <span class="field-drag-handle cursor-grab text-ssbc-sage/60 hover:text-ssbc-sage">⠿</span>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm text-ssbc-dark" x-text="field.label_en"></span>
                                    <span class="ml-2 text-xs bg-ssbc-green/10 text-ssbc-green px-1.5 py-0.5 rounded"
                                          x-text="field.field_type"></span>
                                    <span x-show="field.is_required"
                                          class="ml-1 text-xs text-red-500">required</span>
                                    <span x-show="!field.is_active"
                                          class="ml-1 text-xs bg-gray-200 text-gray-500 px-1.5 py-0.5 rounded">inactive</span>
                                </div>
                                <button type="button" @click="openFieldModal(field, section.id)"
                                        class="text-xs text-ssbc-sage hover:text-ssbc-green px-2">Edit</button>
                                <button type="button" @click="confirmDeleteField(field, section)"
                                        class="text-xs text-red-500 hover:text-red-700 px-2">Delete</button>
                            </div>
                        </template>
                        <p x-show="!section.all_fields?.length"
                           class="text-xs text-ssbc-sage italic py-2">No fields yet.</p>
                    </div>

                    <button type="button" @click="openFieldModal(null, section.id)"
                            class="text-sm text-ssbc-gold hover:underline">+ Add Field</button>
                </div>
            </div>
        </template>
    </div>

    {{-- ── Section Modal ──────────────────────────────────────────────────── --}}
    <div x-show="sectionModalOpen" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-md p-6 shadow-xl" @click.outside="sectionModalOpen = false">
            <h2 class="text-lg font-bold text-ssbc-green mb-5"
                x-text="editingSection?.id ? 'Edit Section' : 'Add Section'"></h2>

            <div class="space-y-4">
                <div>
                    <label class="ssbc-label">Title (English) *</label>
                    <input type="text" x-model="sectionForm.title_en" class="ssbc-input">
                </div>
                <div>
                    <label class="ssbc-label">Title (Arabic) *</label>
                    <input type="text" x-model="sectionForm.title_ar" class="ssbc-input" dir="rtl">
                </div>
                <div class="flex items-center gap-3">
                    <label class="ssbc-label mb-0">Repeatable section?</label>
                    <input type="checkbox" x-model="sectionForm.is_repeatable" class="rounded border-ssbc-green/40">
                </div>
                <div x-show="sectionForm.is_repeatable">
                    <label class="ssbc-label">Max Repeats</label>
                    <input type="number" x-model.number="sectionForm.max_repeats" min="2" max="10" class="ssbc-input w-24">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="sectionModalOpen = false" class="ssbc-btn-outline-dark text-sm">Cancel</button>
                <button type="button" @click="saveSection()" :disabled="saving" class="ssbc-btn-primary text-sm">
                    <span x-text="saving ? 'Saving…' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Field Modal ────────────────────────────────────────────────────── --}}
    <div x-show="fieldModalOpen" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl p-6 shadow-xl my-8" @click.outside="fieldModalOpen = false">
            <h2 class="text-lg font-bold text-ssbc-green mb-5"
                x-text="editingField?.id ? 'Edit Field' : 'Add Field'"></h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="ssbc-label">Label (English) *</label>
                    <input type="text" x-model="fieldForm.label_en" class="ssbc-input">
                </div>
                <div>
                    <label class="ssbc-label">Label (Arabic) *</label>
                    <input type="text" x-model="fieldForm.label_ar" class="ssbc-input" dir="rtl">
                </div>
                <div>
                    <label class="ssbc-label">Placeholder (English)</label>
                    <input type="text" x-model="fieldForm.placeholder_en" class="ssbc-input">
                </div>
                <div>
                    <label class="ssbc-label">Placeholder (Arabic)</label>
                    <input type="text" x-model="fieldForm.placeholder_ar" class="ssbc-input" dir="rtl">
                </div>
                <div>
                    <label class="ssbc-label">Field Type *</label>
                    <select x-model="fieldForm.field_type" class="ssbc-input">
                        @foreach($fieldTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-4 items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" x-model="fieldForm.is_required" class="rounded border-ssbc-green/40">
                        Required
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" x-model="fieldForm.is_active" class="rounded border-ssbc-green/40">
                        Active
                    </label>
                </div>
            </div>

            {{-- Options builder (select / radio / checkbox_group) --}}
            <div x-show="['select','radio','checkbox_group'].includes(fieldForm.field_type)" class="mt-6">
                <div class="flex items-center justify-between mb-3">
                    <label class="ssbc-label mb-0">Options</label>
                    <button type="button" @click="addOption()" class="text-xs text-ssbc-gold hover:underline">+ Add Option</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(opt, i) in fieldForm.options" :key="i">
                        <div class="flex gap-2 items-center">
                            <input type="text" x-model="opt.label_en" placeholder="Label EN"
                                   class="ssbc-input text-sm flex-1">
                            <input type="text" x-model="opt.label_ar" placeholder="Label AR" dir="rtl"
                                   class="ssbc-input text-sm flex-1">
                            <input type="text" x-model="opt.value" placeholder="value"
                                   class="ssbc-input text-sm w-28">
                            <button type="button" @click="fieldForm.options.splice(i,1)"
                                    class="text-red-500 hover:text-red-700 shrink-0">✕</button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- File config --}}
            <div x-show="fieldForm.field_type === 'file'" class="mt-6">
                <label class="ssbc-label">Accepted File Types</label>
                <div class="flex flex-wrap gap-3 mt-2">
                    <template x-for="type in ['pdf','jpg','jpeg','png','doc','docx']" :key="type">
                        <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                            <input type="checkbox"
                                   :value="type"
                                   :checked="fieldForm.file_config.accepted_types?.includes(type)"
                                   @change="toggleFileType(type)"
                                   class="rounded border-ssbc-green/40">
                            <span x-text="type.toUpperCase()"></span>
                        </label>
                    </template>
                </div>
                <div class="mt-3">
                    <label class="ssbc-label">Max File Size (MB)</label>
                    <input type="number" x-model.number="fieldForm.file_config.max_size_mb"
                           min="1" max="50" class="ssbc-input w-24">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="fieldModalOpen = false" class="ssbc-btn-outline-dark text-sm">Cancel</button>
                <button type="button" @click="saveField()" :disabled="saving" class="ssbc-btn-primary text-sm">
                    <span x-text="saving ? 'Saving…' : 'Save Field'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Delete Confirmation Modal ────────────────────────────────────── --}}
    <div x-show="confirmModal.open" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-sm p-6 shadow-xl">
            <p class="text-sm text-ssbc-dark mb-1" x-text="confirmModal.message"></p>
            <p x-show="confirmModal.warning" class="text-xs text-red-600 mt-2 mb-4" x-text="confirmModal.warning"></p>
            <div class="mt-4 flex justify-end gap-3">
                <button type="button" @click="confirmModal.open = false" class="ssbc-btn-outline-dark text-sm">Cancel</button>
                <button type="button" @click="confirmModal.action()" class="bg-red-600 text-white text-sm px-4 py-2">Confirm Delete</button>
            </div>
        </div>
    </div>

</div>

{{-- SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>

<script>
function formBuilder() {
    return {
        sections: @json(json_decode($sectionsJson)),
        openSections: [],
        saving: false,

        // Section modal
        sectionModalOpen: false,
        editingSection: null,
        sectionForm: { title_en: '', title_ar: '', is_repeatable: false, max_repeats: 5 },

        // Field modal
        fieldModalOpen: false,
        editingField: null,
        editingFieldSectionId: null,
        fieldForm: {
            section_id: null, label_en: '', label_ar: '',
            placeholder_en: '', placeholder_ar: '',
            field_type: 'text', is_required: true, is_active: true,
            options: [], validation_rules: {}, file_config: { accepted_types: ['pdf'], max_size_mb: 5 },
        },

        // Confirm modal
        confirmModal: { open: false, message: '', warning: '', action: () => {} },

        csrf: document.querySelector('meta[name="csrf-token"]').content,

        init() {
            this.openSections = this.sections.length ? [this.sections[0].id] : [];
            this.$nextTick(() => this.initSortable());
        },

        toggleSection(id) {
            if (this.openSections.includes(id)) {
                this.openSections = this.openSections.filter(s => s !== id);
            } else {
                this.openSections.push(id);
            }
            this.$nextTick(() => this.initFieldSortable(id));
        },

        initSortable() {
            const list = this.$refs.sectionsList;
            if (!list) return;
            new Sortable(list, {
                animation: 150,
                handle: '.drag-handle',
                onEnd: () => this.reorderSections(),
            });
            this.sections.forEach(s => this.initFieldSortable(s.id));
        },

        initFieldSortable(sectionId) {
            const el = document.getElementById('fields-' + sectionId);
            if (!el) return;
            new Sortable(el, {
                animation: 150,
                handle: '.field-drag-handle',
                onEnd: () => this.reorderFields(sectionId),
            });
        },

        // ── Sections ─────────────────────────────────────────────────────────

        openSectionModal(section) {
            this.editingSection = section;
            this.sectionForm = section
                ? { title_en: section.title_en, title_ar: section.title_ar, is_repeatable: !!section.is_repeatable, max_repeats: section.max_repeats || 5 }
                : { title_en: '', title_ar: '', is_repeatable: false, max_repeats: 5 };
            this.sectionModalOpen = true;
        },

        async saveSection() {
            this.saving = true;
            const url = this.editingSection
                ? `/admin/forms/join-us/sections/${this.editingSection.id}`
                : '/admin/forms/join-us/sections';
            const method = this.editingSection ? 'PUT' : 'POST';

            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(this.sectionForm),
            }).then(r => r.json());

            if (res.success) {
                if (this.editingSection) {
                    const s = this.sections.find(s => s.id === this.editingSection.id);
                    if (s) Object.assign(s, res.data);
                } else {
                    this.sections.push({ ...res.data, all_fields: [] });
                }
            }
            this.saving = false;
            this.sectionModalOpen = false;
        },

        confirmDeleteSection(section) {
            this.confirmModal = {
                open: true,
                message: `Delete section "${section.title_en}"?`,
                warning: (section.all_fields?.length || 0) > 0
                    ? `This section has ${section.all_fields.length} field(s). All fields and their saved answers will be permanently deleted.`
                    : '',
                action: () => this.deleteSection(section),
            };
        },

        async deleteSection(section) {
            this.confirmModal.open = false;
            const res = await fetch(`/admin/forms/join-us/sections/${section.id}?force=1`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            }).then(r => r.json());

            if (res.success) {
                this.sections = this.sections.filter(s => s.id !== section.id);
            }
        },

        async reorderSections() {
            const items = Array.from(this.$refs.sectionsList.children).map((el, i) => ({
                id: parseInt(el.dataset.id),
                order_index: i,
            }));
            await fetch('/admin/forms/join-us/sections/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ items }),
            });
        },

        // ── Fields ───────────────────────────────────────────────────────────

        openFieldModal(field, sectionId) {
            this.editingField = field;
            this.editingFieldSectionId = sectionId;
            this.fieldForm = field ? {
                section_id: field.section_id,
                label_en: field.label_en, label_ar: field.label_ar,
                placeholder_en: field.placeholder_en || '',
                placeholder_ar: field.placeholder_ar || '',
                field_type: field.field_type,
                is_required: !!field.is_required,
                is_active: !!field.is_active,
                options: field.options ? JSON.parse(JSON.stringify(field.options)) : [],
                validation_rules: field.validation_rules || {},
                file_config: field.file_config || { accepted_types: ['pdf'], max_size_mb: 5 },
            } : {
                section_id: sectionId,
                label_en: '', label_ar: '',
                placeholder_en: '', placeholder_ar: '',
                field_type: 'text', is_required: true, is_active: true,
                options: [], validation_rules: {},
                file_config: { accepted_types: ['pdf'], max_size_mb: 5 },
            };
            this.fieldModalOpen = true;
        },

        addOption() {
            this.fieldForm.options.push({ label_en: '', label_ar: '', value: '' });
        },

        toggleFileType(type) {
            const types = this.fieldForm.file_config.accepted_types || [];
            if (types.includes(type)) {
                this.fieldForm.file_config.accepted_types = types.filter(t => t !== type);
            } else {
                this.fieldForm.file_config.accepted_types = [...types, type];
            }
        },

        async saveField() {
            this.saving = true;
            const url = this.editingField
                ? `/admin/forms/join-us/fields/${this.editingField.id}`
                : '/admin/forms/join-us/fields';
            const method = this.editingField ? 'PUT' : 'POST';

            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(this.fieldForm),
            }).then(r => r.json());

            if (res.success) {
                const section = this.sections.find(s => s.id === this.editingFieldSectionId);
                if (section) {
                    if (this.editingField) {
                        const idx = section.all_fields.findIndex(f => f.id === this.editingField.id);
                        if (idx >= 0) section.all_fields[idx] = res.data;
                    } else {
                        section.all_fields.push(res.data);
                        this.$nextTick(() => this.initFieldSortable(section.id));
                    }
                }
            }
            this.saving = false;
            this.fieldModalOpen = false;
        },

        confirmDeleteField(field, section) {
            this.confirmModal = {
                open: true,
                message: `Delete field "${field.label_en}"?`,
                warning: '',
                action: () => this.deleteField(field, section),
            };
        },

        async deleteField(field, section) {
            this.confirmModal.open = false;
            const res = await fetch(`/admin/forms/join-us/fields/${field.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            }).then(r => r.json());

            if (res.success) {
                section.all_fields = section.all_fields.filter(f => f.id !== field.id);
            }
        },

        async reorderFields(sectionId) {
            const el = document.getElementById('fields-' + sectionId);
            if (!el) return;
            const items = Array.from(el.children).map((div, i) => ({
                id: parseInt(div.dataset.id),
                order_index: i,
            }));
            await fetch('/admin/forms/join-us/fields/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ items }),
            });
        },
    };
}
</script>
@endsection
```

- [ ] **Step 2: Verify in browser**

Navigate to `http://localhost/admin/forms/join-us`. Log in as `admin@ssbc.org` / `Admin1234!`. You should see the accordion with the 4 seeded sections. Test: expand a section, add a field, drag to reorder, open preview.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/form-builder/
git commit -m "feat: add admin form builder view with Alpine accordion + field modal"
```

---

## Task 10: Rewrite JoinController

**Files:**
- Modify: `app/Http/Controllers/JoinController.php`
- Modify: `config/services.php`
- Modify: `.env.example`

- [ ] **Step 1: Update config/services.php**

Add to the return array:

```php
    'google_script_url' => env('GOOGLE_SCRIPT_URL'),
```

- [ ] **Step 2: Add to .env.example**

Append:

```
GOOGLE_SCRIPT_URL=
```

- [ ] **Step 3: Rewrite JoinController.php**

```php
<?php

namespace App\Http\Controllers;

use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\FormUpload;
use App\Mail\AdminSubmissionNotification;
use App\Mail\ApplicantConfirmation;
use App\Services\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JoinController extends Controller
{
    public function create(string $locale, bool $preview = false)
    {
        $form = FormService::getActiveForm('join-us');
        return view('join.create', compact('form', 'preview'));
    }

    public function store(Request $request, string $locale)
    {
        $form = FormService::getActiveForm('join-us');
        $repeats = $request->input('_repeats', []);

        // Build validation rules dynamically
        $rules = ['_repeats' => 'array'];

        foreach ($form as $section) {
            $count = $section->is_repeatable ? max(1, (int) ($repeats[$section->id] ?? 1)) : 1;

            foreach ($section->fields as $field) {
                for ($r = 0; $r < $count; $r++) {
                    $key = "answers.{$field->id}.{$r}";

                    if ($field->field_type === 'file') {
                        $fileKey = "files.{$field->id}.{$r}";
                        $mimes = $field->acceptedMimes();
                        $max   = $field->maxFileSizeKb();
                        $rules[$fileKey] = array_filter([
                            $field->is_required && $r === 0 ? 'required' : 'nullable',
                            'file',
                            "mimes:{$mimes}",
                            "max:{$max}",
                        ]);
                    } else {
                        $rules[$key] = $field->is_required && $r === 0 ? 'required' : 'nullable';
                        if ($field->field_type === 'email') $rules[$key] .= '|email';
                        if ($field->field_type === 'url')   $rules[$key] .= '|url';
                    }
                }
            }
        }

        $request->validate($rules);

        // Resolve display_name from first text field of first section
        $firstSection = $form->first();
        $nameFieldId  = $firstSection?->fields->where('field_type', 'text')->first()?->id;
        $displayName  = $nameFieldId ? $request->input("answers.{$nameFieldId}.0") : null;

        $submission = FormSubmission::create([
            'form_id'      => 'join-us',
            'display_name' => strip_tags((string) $displayName),
            'ip_address'   => $request->ip(),
            'submitted_at' => now(),
        ]);

        // Persist text answers
        $answers = $request->input('answers', []);
        $answerRows = [];

        foreach ($answers as $fieldId => $repeatValues) {
            foreach ($repeatValues as $repeatIndex => $value) {
                if ($value === null || $value === '') continue;
                $answerRows[] = [
                    'submission_id' => $submission->id,
                    'field_id'      => (int) $fieldId,
                    'repeat_index'  => (int) $repeatIndex,
                    'answer_value'  => strip_tags(is_array($value) ? json_encode($value) : (string) $value),
                    'created_at'    => now(),
                ];
            }
        }

        if ($answerRows) {
            FormAnswer::insert($answerRows);
        }

        // Persist file uploads
        $uuid = (string) Str::uuid();

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $fieldId => $repeatFiles) {
                foreach ($repeatFiles as $repeatIndex => $file) {
                    if (! $file || ! $file->isValid()) continue;
                    $path = $file->store("submissions/{$uuid}", 'public');
                    FormUpload::create([
                        'submission_id' => $submission->id,
                        'field_id'      => (int) $fieldId,
                        'repeat_index'  => (int) $repeatIndex,
                        'file_path'     => $path,
                        'file_name'     => $file->getClientOriginalName(),
                        'file_size'     => $file->getSize(),
                    ]);
                }
            }
        }

        // Notify admin
        Mail::to('info@sysabc.org')->queue(new AdminSubmissionNotification($submission));

        // Find applicant email answer (field_type = 'email')
        $emailFieldId = $form->flatMap->fields->where('field_type', 'email')->first()?->id;
        $applicantEmail = $emailFieldId
            ? FormAnswer::where('submission_id', $submission->id)->where('field_id', $emailFieldId)->value('answer_value')
            : null;

        if ($applicantEmail && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
            Mail::to($applicantEmail)->queue(new ApplicantConfirmation($submission));
        }

        // Google Sheets (fire-and-forget)
        $scriptUrl = config('services.google_script_url');
        if ($scriptUrl) {
            try {
                Http::timeout(5)->post($scriptUrl, [
                    'display_name' => $submission->display_name,
                    'submission_id' => $submission->id,
                    'submitted_at' => $submission->submitted_at->toISOString(),
                ]);
            } catch (\Throwable) {
                // silent
            }
        }

        return redirect()->route('join.thanks', ['locale' => $locale]);
    }

    public function thanks(string $locale)
    {
        return view('join.thanks');
    }
}
```

- [ ] **Step 4: Write submission test**

In `tests/Feature/JoinFormTest.php`, add:

```php
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Services\FormService;
use Illuminate\Support\Facades\Mail;

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
```

- [ ] **Step 5: Run the test**

```bash
php artisan test tests/Feature/JoinFormTest.php
```

Expected: 1 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/JoinController.php config/services.php .env.example tests/Feature/JoinFormTest.php
git commit -m "feat: rewrite JoinController with dynamic form processing"
```

---

## Task 11: Dynamic Form Blade View

**Files:**
- Modify: `resources/views/join/create.blade.php`

- [ ] **Step 1: Rewrite the view**

Replace the entire contents of `resources/views/join/create.blade.php`:

```blade
@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('title', __('join.hero.heading') . ' — ' . __('common.site_name'))

@section('content')

@include('partials.page-hero', [
    'eyebrow' => __('join.hero.eyebrow'),
    'heading' => __('join.hero.heading'),
    'body'    => __('join.intro'),
])

<section class="bg-white">
    <div class="ssbc-container py-16">
        <div class="max-w-3xl mx-auto">

            {{-- Institutional header --}}
            <div class="flex flex-col items-center mb-12 text-center">
                <img src="{{ asset('images/logos/logo-light.png') }}"
                     alt="{{ __('common.site_name') }}"
                     class="h-16 md:h-20 w-auto mb-4" width="800" height="346" loading="lazy">
                <div class="w-16 h-px bg-ssbc-gold"></div>
            </div>

            @if(isset($preview) && $preview)
                <div class="mb-8 bg-amber-50 border border-amber-300 px-4 py-3 text-sm text-amber-800 text-center">
                    Preview Mode — this form cannot be submitted from here.
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-8 border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold mb-2">Please correct the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div x-data="dynamicForm({{ $form->toJson() }})" x-init="init()">

                {{-- Step indicator --}}
                <div class="flex items-center justify-between mb-10">
                    <p class="ssbc-eyebrow">
                        {{ __('join.steps.step') }}
                        <span x-text="step + 1"></span>
                        {{ __('join.steps.of') }}
                        <span x-text="sections.length"></span>
                    </p>
                    <div class="flex gap-2">
                        <template x-for="(s, i) in sections" :key="i">
                            <span class="h-1 w-10 transition-colors"
                                  :class="i <= step ? 'bg-ssbc-gold' : 'bg-ssbc-green/15'"></span>
                        </template>
                    </div>
                </div>

                <form method="POST"
                      action="{{ isset($preview) && $preview ? '#' : route('join.store', ['locale' => $locale]) }}"
                      enctype="multipart/form-data"
                      @submit.prevent="onSubmit">
                    @csrf

                    {{-- Hidden _repeats fields --}}
                    <template x-for="(count, sectionId) in repeats" :key="sectionId">
                        <input type="hidden" :name="'_repeats[' + sectionId + ']'" :value="count">
                    </template>

                    {{-- Current section --}}
                    <template x-if="currentSection">
                        <div>
                            <h2 class="text-2xl font-display font-bold text-ssbc-green mb-2"
                                x-text="currentSection.title_{{ $locale }}"></h2>
                            <div class="w-12 h-px bg-ssbc-gold mb-8"></div>

                            {{-- Repeatable section tabs --}}
                            <template x-if="currentSection.is_repeatable">
                                <div>
                                    <div class="flex gap-2 flex-wrap mb-6">
                                        <template x-for="r in repeatCount" :key="r">
                                            <button type="button"
                                                    @click="activeRepeat = r - 1"
                                                    :class="activeRepeat === r - 1
                                                        ? 'bg-ssbc-gold text-ssbc-green border-ssbc-gold'
                                                        : 'bg-white text-ssbc-sage border-ssbc-green/20'"
                                                    class="px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors">
                                                <span x-text="currentSection.title_{{ $locale }} + ' ' + r"></span>
                                            </button>
                                        </template>
                                        <button type="button"
                                                x-show="repeatCount < currentSection.max_repeats"
                                                @click="addRepeat()"
                                                class="px-4 py-1.5 rounded-full text-sm border border-dashed border-ssbc-gold text-ssbc-gold">
                                            + Add another
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Fields --}}
                            <div class="space-y-6">
                                <template x-for="field in currentSection.fields" :key="field.id">
                                    <div>
                                        {{-- Label --}}
                                        <label class="ssbc-label" :for="'f_' + field.id + '_' + activeRepeat">
                                            <span x-text="field.label_{{ $locale }}"></span>
                                            <span x-show="field.is_required" class="text-red-500 ml-0.5">*</span>
                                        </label>

                                        {{-- text / email / tel / number / url --}}
                                        <template x-if="['text','email','tel','number','url'].includes(field.field_type)">
                                            <input
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                :type="field.field_type"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :placeholder="field.placeholder_{{ $locale }} || ''"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
                                                class="ssbc-input"
                                            >
                                        </template>

                                        {{-- textarea --}}
                                        <template x-if="field.field_type === 'textarea'">
                                            <textarea
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :placeholder="field.placeholder_{{ $locale }} || ''"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
                                                rows="3"
                                                class="ssbc-input"
                                            ></textarea>
                                        </template>

                                        {{-- date --}}
                                        <template x-if="field.field_type === 'date'">
                                            <input
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                type="date"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
                                                class="ssbc-input"
                                            >
                                        </template>

                                        {{-- select --}}
                                        <template x-if="field.field_type === 'select'">
                                            <select
                                                :id="'f_' + field.id + '_' + activeRepeat"
                                                :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                :required="field.is_required && activeRepeat === 0"
                                                x-model="answers[field.id + '_' + activeRepeat]"
                                                class="ssbc-input"
                                            >
                                                <option value="">— Select —</option>
                                                <template x-for="opt in (field.options || [])" :key="opt.value">
                                                    <option :value="opt.value"
                                                            x-text="opt.label_{{ $locale }}"></option>
                                                </template>
                                            </select>
                                        </template>

                                        {{-- radio --}}
                                        <template x-if="field.field_type === 'radio'">
                                            <div class="flex flex-wrap gap-4 mt-1">
                                                <template x-for="opt in (field.options || [])" :key="opt.value">
                                                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                                                        <input type="radio"
                                                               :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                               :value="opt.value"
                                                               x-model="answers[field.id + '_' + activeRepeat]"
                                                               class="text-ssbc-gold focus:ring-ssbc-gold">
                                                        <span x-text="opt.label_{{ $locale }}"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- checkbox_group --}}
                                        <template x-if="field.field_type === 'checkbox_group'">
                                            <div>
                                                <div class="grid sm:grid-cols-2 gap-2 mt-1">
                                                    <template x-for="opt in (field.options || [])" :key="opt.value">
                                                        <label class="flex items-start gap-2 cursor-pointer text-sm p-2 hover:bg-ssbc-beige/40 rounded transition-colors">
                                                            <input type="checkbox"
                                                                   :value="opt.value"
                                                                   :checked="(checkboxAnswers[field.id + '_' + activeRepeat] || []).includes(opt.value)"
                                                                   @change="toggleCheckbox(field.id, activeRepeat, opt.value)"
                                                                   class="mt-0.5 shrink-0 text-ssbc-gold focus:ring-ssbc-gold">
                                                            <span x-text="opt.label_{{ $locale }}"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                                {{-- Hidden serialized value --}}
                                                <input type="hidden"
                                                       :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                       :value="JSON.stringify(checkboxAnswers[field.id + '_' + activeRepeat] || [])">
                                            </div>
                                        </template>

                                        {{-- file --}}
                                        <template x-if="field.field_type === 'file'">
                                            <div>
                                                <div class="border-2 border-dashed border-ssbc-green/20 p-6 text-center hover:border-ssbc-gold transition-colors relative"
                                                     @dragover.prevent
                                                     @drop.prevent="handleFileDrop(field, activeRepeat, $event)">
                                                    <input type="file"
                                                           :id="'f_' + field.id + '_' + activeRepeat"
                                                           :name="'files[' + field.id + '][' + activeRepeat + ']'"
                                                           :accept="'.' + (field.file_config?.accepted_types || ['pdf']).join(',.')"
                                                           :required="field.is_required && activeRepeat === 0"
                                                           @change="handleFileSelect(field, activeRepeat, $event)"
                                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                                    <div x-show="!fileNames[field.id + '_' + activeRepeat]">
                                                        <p class="text-sm text-ssbc-sage">Drag & drop or click to browse</p>
                                                        <p class="text-xs text-ssbc-sage/70 mt-1"
                                                           x-text="'.' + (field.file_config?.accepted_types || ['pdf']).join(', .') + ' — max ' + (field.file_config?.max_size_mb || 5) + ' MB'"></p>
                                                    </div>
                                                    <div x-show="fileNames[field.id + '_' + activeRepeat]"
                                                         class="flex items-center justify-center gap-2">
                                                        <span class="text-sm text-ssbc-green font-semibold"
                                                              x-text="fileNames[field.id + '_' + activeRepeat]"></span>
                                                        <span class="text-xs text-ssbc-sage">✓</span>
                                                    </div>
                                                </div>
                                                <p x-show="fileErrors[field.id + '_' + activeRepeat]"
                                                   x-text="fileErrors[field.id + '_' + activeRepeat]"
                                                   class="text-red-500 text-xs mt-1"></p>
                                            </div>
                                        </template>

                                        {{-- declaration --}}
                                        <template x-if="field.field_type === 'declaration'">
                                            <div class="border border-ssbc-green/15 bg-ssbc-beige/50 p-6">
                                                <label class="flex items-start gap-3 cursor-pointer">
                                                    <input type="checkbox"
                                                           :name="'answers[' + field.id + '][' + activeRepeat + ']'"
                                                           value="1"
                                                           :required="field.is_required"
                                                           x-model="answers[field.id + '_' + activeRepeat]"
                                                           class="mt-1 rounded-none border-ssbc-green/40 text-ssbc-gold focus:ring-ssbc-gold">
                                                    <span class="text-sm text-ssbc-dark leading-relaxed"
                                                          x-text="field.label_{{ $locale }}"></span>
                                                </label>
                                            </div>
                                        </template>

                                        {{-- Field error --}}
                                        <p x-show="stepErrors[field.id + '_' + activeRepeat]"
                                           x-text="stepErrors[field.id + '_' + activeRepeat]"
                                           class="text-red-500 text-xs mt-1"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Navigation --}}
                    <div class="mt-12 flex items-center justify-between border-t border-ssbc-green/15 pt-6">
                        <button type="button" class="ssbc-btn-outline-dark"
                                x-show="step > 0" @click="prevStep()">
                            ← {{ __('common.previous') }}
                        </button>
                        <span x-show="step === 0"></span>

                        <button type="button" class="ssbc-btn-primary"
                                x-show="step < sections.length - 1"
                                @click="nextStep()">
                            {{ __('common.next') }} →
                        </button>

                        @if(!(isset($preview) && $preview))
                        <button type="submit" class="ssbc-btn-primary"
                                x-show="step === sections.length - 1">
                            {{ __('join.submit') }}
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function dynamicForm(sectionsJson) {
    const sections = sectionsJson;

    return {
        sections,
        step: 0,
        activeRepeat: 0,
        answers: {},
        checkboxAnswers: {},
        repeats: {},
        fileNames: {},
        fileErrors: {},
        stepErrors: {},

        init() {
            sections.forEach(s => {
                if (s.is_repeatable) this.repeats[s.id] = 1;
            });
        },

        get currentSection() {
            return this.sections[this.step] || null;
        },

        get repeatCount() {
            if (!this.currentSection?.is_repeatable) return 1;
            return this.repeats[this.currentSection.id] || 1;
        },

        addRepeat() {
            const s = this.currentSection;
            if (!s?.is_repeatable) return;
            const current = this.repeats[s.id] || 1;
            if (current < s.max_repeats) {
                this.repeats[s.id] = current + 1;
                this.activeRepeat = current;
            }
        },

        toggleCheckbox(fieldId, repeatIndex, value) {
            const key = fieldId + '_' + repeatIndex;
            const current = this.checkboxAnswers[key] || [];
            if (current.includes(value)) {
                this.checkboxAnswers[key] = current.filter(v => v !== value);
            } else {
                this.checkboxAnswers[key] = [...current, value];
            }
        },

        handleFileSelect(field, repeatIndex, event) {
            const file = event.target.files[0];
            if (!file) return;
            this.validateAndSetFile(field, repeatIndex, file);
        },

        handleFileDrop(field, repeatIndex, event) {
            const file = event.dataTransfer.files[0];
            if (!file) return;
            this.validateAndSetFile(field, repeatIndex, file);
        },

        validateAndSetFile(field, repeatIndex, file) {
            const key = field.id + '_' + repeatIndex;
            const maxBytes = (field.file_config?.max_size_mb || 5) * 1024 * 1024;
            const accepted = (field.file_config?.accepted_types || ['pdf']).map(t => '.' + t);
            const ext = '.' + file.name.split('.').pop().toLowerCase();

            if (!accepted.includes(ext)) {
                this.fileErrors[key] = 'File type not accepted. Allowed: ' + accepted.join(', ');
                this.fileNames[key] = null;
                return;
            }
            if (file.size > maxBytes) {
                this.fileErrors[key] = 'File too large. Max ' + (field.file_config?.max_size_mb || 5) + ' MB.';
                this.fileNames[key] = null;
                return;
            }
            this.fileErrors[key] = null;
            this.fileNames[key] = file.name;
        },

        validateCurrentStep() {
            const s = this.currentSection;
            if (!s) return true;
            const count = s.is_repeatable ? (this.repeats[s.id] || 1) : 1;
            let valid = true;
            this.stepErrors = {};

            for (const field of s.fields) {
                if (!field.is_required) continue;
                for (let r = 0; r < count; r++) {
                    if (r > 0 && !s.is_repeatable) break;
                    const key = field.id + '_' + r;

                    if (field.field_type === 'checkbox_group') {
                        if (!(this.checkboxAnswers[key]?.length)) {
                            if (r === 0) { this.stepErrors[key] = 'Please select at least one option.'; valid = false; }
                        }
                    } else if (field.field_type === 'file') {
                        if (!this.fileNames[key] && r === 0) {
                            this.stepErrors[key] = 'This file is required.'; valid = false;
                        }
                    } else if (field.field_type !== 'declaration') {
                        const val = this.answers[key];
                        if (!val || val === '') {
                            if (r === 0) { this.stepErrors[key] = 'This field is required.'; valid = false; }
                        }
                    }
                }
            }
            return valid;
        },

        nextStep() {
            if (!this.validateCurrentStep()) return;
            this.step++;
            this.activeRepeat = 0;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        prevStep() {
            this.step--;
            this.activeRepeat = 0;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        onSubmit(event) {
            if (!this.validateCurrentStep()) { event.preventDefault(); return; }
            event.target.submit();
        },
    };
}
</script>

@endsection
```

- [ ] **Step 2: Verify in browser**

Navigate to `http://localhost/en/join`. The form should render dynamically from the DB. Test: fill in Personal Information, click Next, add a company, proceed to Documents, upload a file, proceed to Declaration, submit. Check `/en/join/thanks` loads.

- [ ] **Step 3: Commit**

```bash
git add resources/views/join/create.blade.php
git commit -m "feat: rewrite join form as dynamic Alpine renderer"
```

---

## Task 12: Mail Notifications

**Files:**
- Create: `app/Mail/AdminSubmissionNotification.php`
- Create: `app/Mail/ApplicantConfirmation.php`
- Create: `resources/views/mail/admin-notification.blade.php`
- Create: `resources/views/mail/applicant-confirmation.blade.php`

- [ ] **Step 1: Create AdminSubmissionNotification.php**

```php
<?php

namespace App\Mail;

use App\Models\FormSubmission;
use App\Services\FormService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New SSBC Membership Application — ' . $this->submission->display_name,
        );
    }

    public function content(): Content
    {
        $form = FormService::getActiveForm('join-us');
        $this->submission->load(['answers', 'uploads']);

        return new Content(
            view: 'mail.admin-notification',
            with: ['form' => $form, 'submission' => $this->submission],
        );
    }
}
```

- [ ] **Step 2: Create ApplicantConfirmation.php**

```php
<?php

namespace App\Mail;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicantConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SSBC Membership Application — Received');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.applicant-confirmation',
            with: ['submission' => $this->submission],
        );
    }
}
```

- [ ] **Step 3: Create admin-notification.blade.php**

```blade
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #1a2e20; }
  h1 { color: #1a3a2a; font-size: 20px; }
  h2 { color: #1a3a2a; font-size: 15px; margin-top: 24px; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  td { padding: 6px 10px; border: 1px solid #e0e0e0; vertical-align: top; font-size: 13px; }
  td:first-child { background: #f5f5f0; font-weight: bold; width: 35%; }
</style></head>
<body>
<h1>New Membership Application</h1>
<p>Submitted: {{ $submission->submitted_at->format('d M Y H:i') }} UTC</p>
<p>IP: {{ $submission->ip_address }}</p>

@foreach($form as $section)
  @php
    $count = $section->is_repeatable
        ? max(1, $submission->answers->where('repeat_index', '>', 0)->pluck('repeat_index')->max() + 1 ?? 1)
        : 1;
  @endphp
  @for($r = 0; $r < $count; $r++)
    <h2>{{ $section->title_en }}{{ $count > 1 ? ' ' . ($r + 1) : '' }}</h2>
    <table>
      @foreach($section->fields as $field)
        @php $answer = $submission->answerFor($field->id, $r); @endphp
        @if($field->field_type === 'file')
          @php $uploads = $submission->uploadsFor($field->id, $r); @endphp
          <tr>
            <td>{{ $field->label_en }}</td>
            <td>@foreach($uploads as $u)<a href="{{ $u->url() }}">{{ $u->file_name }}</a> @endforeach</td>
          </tr>
        @elseif($field->field_type !== 'declaration')
          <tr>
            <td>{{ $field->label_en }}</td>
            <td>{{ $answer ?? '—' }}</td>
          </tr>
        @endif
      @endforeach
    </table>
  @endfor
@endforeach

<p style="margin-top:24px;font-size:12px;color:#888;">
  View in admin: <a href="{{ url('/admin/submissions/' . $submission->id) }}">Submission #{{ $submission->id }}</a>
</p>
</body>
</html>
```

- [ ] **Step 4: Create applicant-confirmation.blade.php**

```blade
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#1a2e20;max-width:600px;margin:0 auto;">
  <div style="background:#1a3a2a;padding:24px;">
    <h1 style="color:#c5a84a;margin:0;font-size:22px;">Syrian Saudi Business Council</h1>
  </div>
  <div style="padding:32px;">
    <h2 style="color:#1a3a2a;">Application Received</h2>
    <p>Dear {{ $submission->display_name }},</p>
    <p>Thank you for submitting your SSBC membership application. We have received your application and it is currently under review.</p>
    <p>The Council will be in touch with you shortly regarding the next steps.</p>
    <p style="margin-top:32px;">Best regards,<br><strong>Syrian Saudi Business Council</strong></p>
  </div>
</body>
</html>
```

- [ ] **Step 5: Commit**

```bash
git add app/Mail/ resources/views/mail/
git commit -m "feat: add admin and applicant email notifications"
```

---

## Task 13: SubmissionController

**Files:**
- Create: `app/Http/Controllers/Admin/SubmissionController.php`
- Create: `app/Exports/SubmissionsExport.php`

- [ ] **Step 1: Create SubmissionController.php**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SubmissionsExport;
use App\Http\Controllers\Controller;
use App\Models\FormSection;
use App\Models\FormSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = FormSubmission::where('form_id', 'join-us')
            ->orderByDesc('submitted_at');

        if ($request->filled('from')) {
            $query->whereDate('submitted_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('submitted_at', '<=', $request->input('to'));
        }

        $submissions = $query->paginate(30)->withQueryString();

        return view('admin.submissions.index', compact('submissions'));
    }

    public function show(FormSubmission $submission)
    {
        $submission->load(['answers', 'uploads']);
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
            ->orderBy('order_index')
            ->get();

        return view('admin.submissions.show', compact('submission', 'sections'));
    }

    public function update(Request $request, FormSubmission $submission)
    {
        $data = $request->validate([
            'status'      => ['sometimes', 'in:pending,under_review,approved,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update($data);

        return redirect()->route('admin.submissions.show', $submission)
            ->with('status', 'Submission updated.');
    }

    public function destroy(FormSubmission $submission)
    {
        $submission->delete();

        return redirect()->route('admin.submissions.index')
            ->with('status', 'Submission deleted.');
    }

    public function pdf(FormSubmission $submission)
    {
        $submission->load(['answers', 'uploads']);
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
            ->orderBy('order_index')
            ->get();

        $pdf = Pdf::loadView('admin.submissions.pdf', compact('submission', 'sections'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("ssbc-submission-{$submission->id}.pdf");
    }

    public function export(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        return Excel::download(
            new SubmissionsExport($from, $to),
            'ssbc-submissions-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
```

- [ ] **Step 2: Create SubmissionsExport.php**

```php
<?php

namespace App\Exports;

use App\Models\FormField;
use App\Models\FormSection;
use App\Models\FormSubmission;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubmissionsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private ?string $from = null,
        private ?string $to = null,
    ) {}

    public function collection(): Collection
    {
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
            ->orderBy('order_index')
            ->get();

        $query = FormSubmission::where('form_id', 'join-us')
            ->with(['answers'])
            ->orderBy('submitted_at');

        if ($this->from) $query->whereDate('submitted_at', '>=', $this->from);
        if ($this->to)   $query->whereDate('submitted_at', '<=', $this->to);

        $maxRepeats = 5;

        return $query->get()->map(function (FormSubmission $sub) use ($sections, $maxRepeats) {
            $row = [
                $sub->id,
                $sub->submitted_at->format('Y-m-d H:i'),
                $sub->status,
                $sub->display_name,
                $sub->ip_address,
            ];

            foreach ($sections as $section) {
                $repeats = $section->is_repeatable ? $maxRepeats : 1;
                foreach ($section->allFields as $field) {
                    if ($field->field_type === 'file') continue;
                    for ($r = 0; $r < $repeats; $r++) {
                        $row[] = $sub->answerFor($field->id, $r) ?? '';
                    }
                }
            }

            return $row;
        });
    }

    public function headings(): array
    {
        $sections = FormSection::with('allFields')
            ->where('form_id', 'join-us')
            ->orderBy('order_index')
            ->get();

        $maxRepeats = 5;
        $headers = ['ID', 'Submitted At', 'Status', 'Display Name', 'IP Address'];

        foreach ($sections as $section) {
            $repeats = $section->is_repeatable ? $maxRepeats : 1;
            foreach ($section->allFields as $field) {
                if ($field->field_type === 'file') continue;
                for ($r = 0; $r < $repeats; $r++) {
                    $suffix = $repeats > 1 ? ' (' . ($r + 1) . ')' : '';
                    $headers[] = $field->label_en . $suffix;
                }
            }
        }

        return $headers;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/SubmissionController.php app/Exports/SubmissionsExport.php
git commit -m "feat: add SubmissionController and SubmissionsExport"
```

---

## Task 14: Submissions Views

**Files:**
- Create: `resources/views/admin/submissions/index.blade.php`
- Create: `resources/views/admin/submissions/show.blade.php`
- Create: `resources/views/admin/submissions/pdf.blade.php`

- [ ] **Step 1: Create submissions/index.blade.php**

```blade
@extends('layouts.admin')

@section('title', 'Submissions')

@section('content')
<div class="w-12 h-px bg-ssbc-gold mb-4"></div>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-display font-bold text-ssbc-green">Submissions</h1>
    <a href="{{ route('admin.submissions.export', request()->only('from','to')) }}"
       class="ssbc-btn-outline-dark text-sm">Export Excel ↓</a>
</div>

{{-- Date filter --}}
<form method="GET" class="flex gap-3 items-end mb-6">
    <div>
        <label class="ssbc-label text-xs">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="ssbc-input text-sm">
    </div>
    <div>
        <label class="ssbc-label text-xs">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="ssbc-input text-sm">
    </div>
    <button type="submit" class="ssbc-btn-primary text-sm">Filter</button>
    @if(request('from') || request('to'))
        <a href="{{ route('admin.submissions.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">Clear</a>
    @endif
</form>

<div class="ssbc-admin-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-ssbc-light text-ssbc-green/80 text-xs uppercase tracking-wider">
            <tr>
                <th class="text-left px-4 py-3">Date</th>
                <th class="text-left px-4 py-3">Applicant</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ssbc-green/10">
            @forelse($submissions as $sub)
                <tr>
                    <td class="px-4 py-3 text-ssbc-dark/70 whitespace-nowrap">{{ $sub->submitted_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 font-semibold text-ssbc-dark">{{ $sub->display_name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="ssbc-status-badge ssbc-status-{{ $sub->status }}">{{ ucfirst(str_replace('_', ' ', $sub->status)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.submissions.show', $sub) }}" class="ssbc-link-gold text-sm">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-ssbc-sage">No submissions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $submissions->links() }}</div>
@endsection
```

- [ ] **Step 2: Create submissions/show.blade.php**

```blade
@extends('layouts.admin')

@section('title', 'Submission #' . $submission->id)

@section('content')
<a href="{{ route('admin.submissions.index') }}" class="text-sm text-ssbc-sage hover:text-ssbc-green">← Back to Submissions</a>

<div class="mt-4 w-12 h-px bg-ssbc-gold mb-4"></div>
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-display font-bold text-ssbc-green">{{ $submission->display_name ?? 'Submission #' . $submission->id }}</h1>
        <p class="text-sm text-ssbc-sage">Submitted {{ $submission->submitted_at->format('d M Y H:i') }} UTC · IP: {{ $submission->ip_address }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.submissions.pdf', $submission) }}" class="ssbc-btn-outline-dark text-sm">Download PDF</a>
        <form method="POST" action="{{ route('admin.submissions.destroy', $submission) }}"
              onsubmit="return confirm('Delete this submission permanently?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:text-red-800 border border-red-300 px-3 py-2">Delete</button>
        </form>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    {{-- Answers --}}
    <div class="lg:col-span-2 space-y-6">
        @foreach($sections as $section)
            @php
                $maxRepeat = $submission->answers->where('repeat_index', '>', 0)->pluck('repeat_index')->max() ?? 0;
                $count = $section->is_repeatable ? $maxRepeat + 1 : 1;
            @endphp
            @for($r = 0; $r < $count; $r++)
                <div class="ssbc-admin-card p-5">
                    <h3 class="font-display font-bold text-ssbc-green mb-4">
                        {{ $section->title_en }}{{ $count > 1 ? ' ' . ($r + 1) : '' }}
                    </h3>
                    <dl class="grid sm:grid-cols-2 gap-4">
                        @foreach($section->allFields as $field)
                            @if($field->field_type === 'declaration') @continue @endif
                            <div @if($field->field_type === 'textarea') class="sm:col-span-2" @endif>
                                <dt class="ssbc-eyebrow mb-1">{{ $field->label_en }}</dt>
                                @if($field->field_type === 'file')
                                    @foreach($submission->uploadsFor($field->id, $r) as $upload)
                                        <dd class="text-sm">
                                            <a href="{{ $upload->url() }}" target="_blank" download
                                               class="ssbc-link-gold">{{ $upload->file_name }}</a>
                                            <span class="text-ssbc-sage text-xs ml-1">({{ round($upload->file_size / 1024) }} KB)</span>
                                        </dd>
                                    @endforeach
                                @else
                                    <dd class="text-sm text-ssbc-dark whitespace-pre-wrap">{{ $submission->answerFor($field->id, $r) ?? '—' }}</dd>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endfor
        @endforeach
    </div>

    {{-- Sidebar: status + notes --}}
    <div class="space-y-4">
        <form method="POST" action="{{ route('admin.submissions.update', $submission) }}" class="ssbc-admin-card p-5">
            @csrf @method('PATCH')
            <h3 class="font-display font-bold text-ssbc-green mb-4">Status</h3>
            <select name="status" class="ssbc-input mb-4 text-sm">
                @foreach(['pending','under_review','approved','rejected'] as $s)
                    <option value="{{ $s }}" @selected($submission->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <h3 class="font-display font-bold text-ssbc-green mb-2">Admin Notes</h3>
            <textarea name="admin_notes" rows="5" class="ssbc-input text-sm mb-4">{{ $submission->admin_notes }}</textarea>
            <button type="submit" class="ssbc-btn-primary text-sm w-full">Save</button>
        </form>
    </div>
</div>
@endsection
```

- [ ] **Step 3: Create submissions/pdf.blade.php**

```blade
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a2e20; margin: 0; padding: 20px; }
  .header { background: #1a3a2a; color: #c5a84a; padding: 16px 20px; margin: -20px -20px 24px; }
  .header h1 { margin: 0; font-size: 16px; }
  .header p { margin: 4px 0 0; font-size: 10px; color: rgba(255,255,255,0.7); }
  h2 { color: #1a3a2a; font-size: 12px; border-bottom: 1px solid #e0d8c8; padding-bottom: 3px; margin: 16px 0 6px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 4px 8px; border: 1px solid #e0e0e0; font-size: 10px; vertical-align: top; }
  td:first-child { background: #f5f5f0; font-weight: bold; width: 35%; }
  .meta { font-size: 9px; color: #888; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="header">
    <h1>Syrian Saudi Business Council — Membership Application</h1>
    <p>Submission #{{ $submission->id }} · {{ $submission->submitted_at->format('d M Y H:i') }} UTC</p>
</div>
<p class="meta">Status: {{ ucfirst(str_replace('_',' ',$submission->status)) }} · IP: {{ $submission->ip_address }}</p>

@foreach($sections as $section)
    @php
        $maxRepeat = $submission->answers->where('repeat_index', '>', 0)->pluck('repeat_index')->max() ?? 0;
        $count = $section->is_repeatable ? $maxRepeat + 1 : 1;
    @endphp
    @for($r = 0; $r < $count; $r++)
        <h2>{{ $section->title_en }}{{ $count > 1 ? ' ' . ($r + 1) : '' }}</h2>
        <table>
            @foreach($section->allFields as $field)
                @if($field->field_type === 'declaration') @continue @endif
                <tr>
                    <td>{{ $field->label_en }}</td>
                    <td>
                        @if($field->field_type === 'file')
                            @foreach($submission->uploadsFor($field->id, $r) as $u)
                                {{ $u->file_name }} ({{ round($u->file_size/1024) }} KB)
                            @endforeach
                        @else
                            {{ $submission->answerFor($field->id, $r) ?? '—' }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endfor
@endforeach

@if($submission->admin_notes)
    <h2>Admin Notes</h2>
    <p>{{ $submission->admin_notes }}</p>
@endif
</body>
</html>
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/admin/submissions/
git commit -m "feat: add submissions index, show, and PDF views"
```

---

## Task 15: Rate Limiting + Storage Link + Final Wiring

**Files:**
- Modify: `routes/web.php` (add throttle to join.store)
- Modify: `bootstrap/app.php` (confirm queue config)

- [ ] **Step 1: Add throttle middleware to join.store**

In `routes/web.php`, find the join store route (inside the locale prefix group) and add throttle:

```php
Route::post('/join', [JoinController::class, 'store'])->name('join.store')->middleware('throttle:5,1');
```

- [ ] **Step 2: Create storage symlink**

```bash
php artisan storage:link
```

Expected: `public/storage` symlink created (or already exists).

- [ ] **Step 3: Run all tests**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 4: Run the full seeder on the actual DB**

```bash
php artisan migrate --force
php artisan db:seed
```

Expected: All 4 sections and 19 fields seeded. No duplicate errors (seeder checks `if exists`).

- [ ] **Step 5: Smoke test in browser**

1. Go to `http://localhost/en/join` — dynamic form loads, 4 steps visible
2. Go to `http://localhost/admin/forms/join-us` — accordion shows 4 sections, fields list populates
3. Add a new field via the modal — it appears in the accordion without page reload
4. Drag a section to reorder — order persists on refresh
5. Click "Preview Form" — opens read-only form with banner
6. Submit a test application — check `form_submissions` + `form_answers` tables
7. Go to `http://localhost/admin/submissions` — submission appears
8. Click submission → view detail → download PDF → export Excel

- [ ] **Step 6: Final commit**

```bash
git add routes/web.php
git commit -m "feat: add throttle to join.store, wire final routes"
```

---

## Task 16: Add .gitignore for .superpowers

- [ ] **Step 1: Add to .gitignore**

Open `.gitignore` and add:

```
/.superpowers/
```

- [ ] **Step 2: Commit**

```bash
git add .gitignore
git commit -m "chore: ignore .superpowers brainstorm directory"
```

---

## Self-Review Checklist

- [x] Schema: all 5 tables with correct columns, indexes, FK constraints — Task 2
- [x] All 12 field types supported — Task 11 (public form renderer handles all)
- [x] Admin section add/edit/delete/reorder — Tasks 6, 7, 9
- [x] Admin field add/edit/delete/reorder — Tasks 7, 9
- [x] Active/inactive toggle — covered in field modal (`is_active` checkbox)
- [x] Options builder for select/radio/checkbox — Task 9 Alpine sub-panel
- [x] File config (accepted types + max size) — Task 9 Alpine sub-panel
- [x] Section deletion guard — Task 6 controller + Task 9 Alpine confirm modal
- [x] Repeatable company section — Task 11 (`is_repeatable` + `addRepeat()`)
- [x] Preview route — Task 6 controller method + preview flag in view
- [x] Dynamic public form rendering — Task 11
- [x] All field types rendered — Task 11
- [x] Multi-step navigation with validation — Task 11
- [x] File drag-and-drop with client validation — Task 11
- [x] `_repeats` hidden fields for server validation — Task 11
- [x] Server-side validation dynamic rules — Task 10
- [x] Save to form_submissions + form_answers + form_uploads — Task 10
- [x] Admin email notification — Task 12
- [x] Applicant confirmation email — Task 12
- [x] Google Sheets POST (fire-and-forget) — Task 10
- [x] Submissions index with date filter — Task 13
- [x] Submission detail view — Task 14
- [x] Status + admin notes update — Task 13, 14
- [x] Delete submission — Task 13
- [x] PDF download — Task 13 + 14
- [x] Excel export — Task 13
- [x] CSRF protection — all Fetch calls use `X-CSRF-TOKEN`, all forms have `@csrf`
- [x] Rate limiting on join.store — Task 15
- [x] File type safety (mimes validation) — Task 10
- [x] strip_tags on text answers — Task 10
- [x] Admin auth middleware — existing + routes registered under `auth` group in Task 8
- [x] Cache invalidation on every mutation — FormService::invalidateCache() called in all controller mutations
- [x] FormSeeder idempotent (checks `if exists`) — Task 5
- [x] `display_name` denormalized on submission — Task 10
- [x] Storage symlink — Task 15
