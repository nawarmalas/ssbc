# SYSABC.ORG 5-Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply 5 fixes: dynamic Strategic Pillars from DB, remove Economic Sectors section, single-source-of-truth sectors in membership form via SectorObserver, conditional "Other" country input, and new Section 3 (Interests & Cooperation).

**Architecture:** Two migrations (schema then data), a SectorObserver syncing `form_fields.options`, a `fieldIsVisible()` helper in `FormSubmissionService` for conditional field skipping, and Alpine.js updates for conditional display and proper array-based checkbox submission.

**Tech Stack:** Laravel 11, PHPUnit, Alpine.js, Blade, MySQL

**Design spec:** `docs/superpowers/specs/2026-05-20-sysabc-fixes-design.md`

---

## File Map

| Action | File | Responsibility |
|--------|------|---------------|
| MODIFY | `.gitignore` | Exclude stale `ssbc_app/` tree |
| CREATE | `database/migrations/2026_05_20_100000_add_form_field_and_sector_columns.php` | Schema: 3 cols on form_fields, 2 on sectors |
| CREATE | `database/migrations/2026_05_20_100001_seed_codes_sections_and_fields.php` | Data: slugs, codes, Fix-4 field, Section 3 |
| MODIFY | `app/Models/Sector.php` | SoftDeletes + frozen slug in booted() |
| MODIFY | `database/factories/SectorFactory.php` | Add slug to factory definition |
| MODIFY | `app/Models/FormField.php` | New fillable/casts + formatAnswer() sector fallback |
| CREATE | `app/Observers/SectorObserver.php` | Rebuild form options on sector change |
| MODIFY | `app/Providers/AppServiceProvider.php` | Register SectorObserver |
| MODIFY | `app/Http/Controllers/Admin/FormBuilderController.php` | Allow-list + delete guard for system-managed |
| MODIFY | `app/Services/FormSubmissionService.php` | fieldIsVisible(), checkbox fix, storeAnswers |
| MODIFY | `resources/views/pages/home.blade.php` | Dynamic pillars + remove sectors include |
| DELETE | `resources/views/pages/partials/sectors.blade.php` | No longer needed |
| MODIFY | `resources/views/join/create.blade.php` | Alpine conditional logic + checkbox arrays |
| MODIFY | `resources/views/admin/form-builder/index.blade.php` | System-managed field UI |
| MODIFY | `tests/Feature/Admin/SectorControllerTest.php` | Update destroy test for soft-delete |
| CREATE | `tests/Feature/SectorObserverTest.php` | Observer syncs field options |
| CREATE | `tests/Feature/FormConditionalLogicTest.php` | fieldIsVisible server-side |

---

## Task 1: Housekeeping + Schema Migration

**Files:**
- Modify: `.gitignore`
- Create: `database/migrations/2026_05_20_100000_add_form_field_and_sector_columns.php`

- [ ] **Step 1: Add ssbc_app/ to .gitignore**

Open `.gitignore` and append:
```
ssbc_app/
```

- [ ] **Step 2: Create the schema migration**

```bash
php artisan make:migration add_form_field_and_sector_columns --path=database/migrations
```

Then replace its contents with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->after('section_id');
            $table->json('conditional_logic')->nullable()->after('file_config');
            $table->boolean('is_system_managed')->default(false)->after('is_active');
            $table->unique(['section_id', 'code'], 'form_fields_section_id_code_unique');
        });

        Schema::table('sectors', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->unique()->after('id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropUnique('form_fields_section_id_code_unique');
            $table->dropColumn(['code', 'conditional_logic', 'is_system_managed']);
        });

        Schema::table('sectors', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('slug');
        });
    }
};
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate
```

Expected: "Migrating: 2026_05_20_100000_add_form_field_and_sector_columns" then "Migrated".

- [ ] **Step 4: Commit**

```bash
git add .gitignore database/migrations/2026_05_20_100000_add_form_field_and_sector_columns.php
git commit -m "feat: add code/conditional_logic/is_system_managed to form_fields; slug/soft-delete to sectors"
```

---

## Task 2: Sector Model — SoftDeletes + Frozen Slug

**Files:**
- Modify: `app/Models/Sector.php`
- Modify: `database/factories/SectorFactory.php`
- Modify: `tests/Feature/Admin/SectorControllerTest.php`

- [ ] **Step 1: Update the existing sector destroy test to expect soft-delete**

Open `tests/Feature/Admin/SectorControllerTest.php` and replace the `test_destroy_deletes_sector` method:

```php
public function test_destroy_deletes_sector(): void
{
    $sector = Sector::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.sectors.destroy', $sector))
        ->assertRedirect(route('admin.sectors.index'));

    $this->assertSoftDeleted('sectors', ['id' => $sector->id]);
}
```

- [ ] **Step 2: Run the test to confirm it fails (sector is hard-deleted currently)**

```bash
php artisan test tests/Feature/Admin/SectorControllerTest.php --filter=test_destroy_deletes_sector
```

Expected: FAIL — `assertSoftDeleted` fails because `deleted_at` is null (hard-deleted).

- [ ] **Step 3: Update Sector model**

Replace the contents of `app/Models/Sector.php` with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sector extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar', 'name_en',
        'description_ar', 'description_en',
        'sort_order', 'is_active', 'slug',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sector $s) {
            if (! $s->slug) {
                $base = Str::slug($s->name_en ?: $s->name_ar);
                $slug = $base;
                $i = 2;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $s->slug = $slug;
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? ($this->name_ar ?: $this->name_en) : ($this->name_en ?: $this->name_ar);
    }

    public function description(): string
    {
        return app()->getLocale() === 'ar' ? ($this->description_ar ?: $this->description_en) : ($this->description_en ?: $this->description_ar);
    }
}
```

- [ ] **Step 4: Update SectorFactory to include a slug**

Replace `database/factories/SectorFactory.php` with:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SectorFactory extends Factory
{
    public function definition(): array
    {
        $nameEn = $this->faker->unique()->words(3, true);
        return [
            'name_ar'        => $this->faker->randomElement([
                'قطاع الزراعة', 'قطاع الصناعة', 'قطاع الخدمات', 'قطاع التقنية',
            ]),
            'name_en'        => $nameEn,
            'description_ar' => $this->faker->randomElement([
                'يدعم هذا القطاع فرص الاستثمار والتنمية المستدامة وبناء الشراكات الاقتصادية.',
                'يركز هذا القطاع على تطوير القدرات المحلية وتعزيز كفاءة سلاسل القيمة.',
            ]),
            'description_en' => $this->faker->paragraph(),
            'sort_order'     => $this->faker->numberBetween(0, 20),
            'is_active'      => true,
            'slug'           => Str::slug($nameEn),
        ];
    }
}
```

- [ ] **Step 5: Run all sector tests**

```bash
php artisan test tests/Feature/Admin/SectorControllerTest.php
```

Expected: All tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Models/Sector.php database/factories/SectorFactory.php tests/Feature/Admin/SectorControllerTest.php
git commit -m "feat: add SoftDeletes and frozen slug to Sector model"
```

---

## Task 3: FormField Model — New Columns + formatAnswer Fallback

**Files:**
- Modify: `app/Models/FormField.php`

- [ ] **Step 1: Update FormField model**

Replace the contents of `app/Models/FormField.php` with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $fillable = [
        'section_id', 'code',
        'label_en', 'label_ar',
        'placeholder_en', 'placeholder_ar',
        'field_type', 'is_required', 'is_active', 'is_system_managed',
        'order_index',
        'options', 'validation_rules', 'conditional_logic', 'file_config',
    ];

    protected function casts(): array
    {
        return [
            'is_required'       => 'boolean',
            'is_active'         => 'boolean',
            'is_system_managed' => 'boolean',
            'order_index'       => 'integer',
            'options'           => 'array',
            'validation_rules'  => 'array',
            'conditional_logic' => 'array',
            'file_config'       => 'array',
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

    /**
     * Render a stored answer for display.
     * - checkbox_group: decode JSON, map values to option labels, join with ", "
     * - select/radio: map single value to option label
     * - declaration: "Accepted" / "—"
     * - sectors_of_operation: fallback to Sector model (incl. trashed) when label not found
     * - other types: trimmed string, or em-dash when empty
     */
    public function formatAnswer(?string $raw, string $locale = 'en'): string
    {
        if ($raw === null || $raw === '') {
            return '—';
        }

        $optionLabel = function (string $value) use ($locale): string {
            foreach (($this->options ?? []) as $opt) {
                if (($opt['value'] ?? null) === $value) {
                    return $locale === 'ar'
                        ? ($opt['label_ar'] ?? $opt['label_en'] ?? $value)
                        : ($opt['label_en'] ?? $opt['label_ar'] ?? $value);
                }
            }

            // Fallback: resolve deleted sectors by slug
            if ($this->code === 'sectors_of_operation') {
                $sector = \App\Models\Sector::withTrashed()->where('slug', $value)->first();
                if ($sector) {
                    return $locale === 'ar'
                        ? ($sector->name_ar ?: $sector->name_en)
                        : ($sector->name_en ?: $sector->name_ar);
                }
            }

            return $value;
        };

        if ($this->field_type === 'checkbox_group') {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) return $raw;
            return collect($decoded)->map($optionLabel)->join(', ');
        }

        if (in_array($this->field_type, ['select', 'radio'], true)) {
            return $optionLabel($raw);
        }

        if ($this->field_type === 'declaration') {
            return $raw === '1' || $raw === 1 || $raw === true ? 'Accepted' : '—';
        }

        return $raw;
    }
}
```

- [ ] **Step 2: Run existing tests to confirm nothing broke**

```bash
php artisan test
```

Expected: All tests PASS.

- [ ] **Step 3: Commit**

```bash
git add app/Models/FormField.php
git commit -m "feat: add code/conditional_logic/is_system_managed to FormField; sector slug fallback in formatAnswer"
```

---

## Task 4: SectorObserver + Registration

**Files:**
- Create: `app/Observers/SectorObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/SectorObserverTest.php`

- [ ] **Step 1: Write the observer test (will fail — observer doesn't exist yet)**

Create `tests/Feature/SectorObserverTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSection;
use App\Models\Sector;
use App\Services\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SectorObserverTest extends TestCase
{
    use RefreshDatabase;

    private function makeSection(): FormSection
    {
        return FormSection::create([
            'form_id'       => 'join-us',
            'title_en'      => 'Company',
            'title_ar'      => 'شركة',
            'is_repeatable' => false,
            'order_index'   => 0,
        ]);
    }

    private function makeSectorsField(FormSection $section): FormField
    {
        return FormField::create([
            'section_id'        => $section->id,
            'code'              => 'sectors_of_operation',
            'label_en'          => 'Sectors of Operation',
            'label_ar'          => 'قطاعات العمل',
            'field_type'        => 'checkbox_group',
            'is_required'       => true,
            'is_active'         => true,
            'is_system_managed' => true,
            'order_index'       => 0,
            'options'           => [],
        ]);
    }

    public function test_creating_sector_rebuilds_form_field_options(): void
    {
        $section = $this->makeSection();
        $field   = $this->makeSectorsField($section);

        Sector::create([
            'name_en'        => 'Agriculture',
            'name_ar'        => 'الزراعة',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'sort_order'     => 1,
            'is_active'      => true,
        ]);

        $field->refresh();
        $this->assertCount(1, $field->options);
        $this->assertSame('agriculture', $field->options[0]['value']);
        $this->assertSame('Agriculture', $field->options[0]['label_en']);
    }

    public function test_updating_sector_name_rebuilds_options(): void
    {
        $section = $this->makeSection();
        $field   = $this->makeSectorsField($section);

        $sector = Sector::create([
            'name_en'        => 'Agriculture',
            'name_ar'        => 'الزراعة',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'sort_order'     => 1,
            'is_active'      => true,
        ]);

        $sector->update(['name_en' => 'Updated Agriculture', 'name_ar' => 'زراعة محدثة']);

        $field->refresh();
        $this->assertSame('Updated Agriculture', $field->options[0]['label_en']);
        $this->assertSame('زراعة محدثة', $field->options[0]['label_ar']);
    }

    public function test_soft_deleting_sector_removes_it_from_options(): void
    {
        $section = $this->makeSection();
        $field   = $this->makeSectorsField($section);

        $s1 = Sector::create([
            'name_en' => 'Agriculture', 'name_ar' => 'الزراعة',
            'description_en' => 'D', 'description_ar' => 'د',
            'sort_order' => 1, 'is_active' => true,
        ]);
        $s2 = Sector::create([
            'name_en' => 'Tourism', 'name_ar' => 'السياحة',
            'description_en' => 'D', 'description_ar' => 'د',
            'sort_order' => 2, 'is_active' => true,
        ]);

        $s1->delete(); // soft delete

        $field->refresh();
        $this->assertCount(1, $field->options);
        $this->assertSame('tourism', $field->options[0]['value']);
    }

    public function test_observer_invalidates_form_cache(): void
    {
        $section = $this->makeSection();
        $this->makeSectorsField($section);

        // Prime the cache
        FormService::getActiveForm('join-us');
        $this->assertTrue(Cache::has('form:join-us:sections'));

        Sector::create([
            'name_en' => 'Agriculture', 'name_ar' => 'الزراعة',
            'description_en' => 'D', 'description_ar' => 'د',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $this->assertFalse(Cache::has('form:join-us:sections'));
    }
}
```

- [ ] **Step 2: Run the test to confirm it fails**

```bash
php artisan test tests/Feature/SectorObserverTest.php
```

Expected: All 4 tests FAIL — observer doesn't exist yet.

- [ ] **Step 3: Create the SectorObserver**

Create `app/Observers/SectorObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\FormField;
use App\Models\Sector;
use App\Services\FormService;
use Illuminate\Support\Facades\DB;

class SectorObserver
{
    public function saved(Sector $sector): void        { $this->sync(); }
    public function deleted(Sector $sector): void      { $this->sync(); }
    public function restored(Sector $sector): void     { $this->sync(); }
    public function forceDeleted(Sector $sector): void { $this->sync(); }

    private function sync(): void
    {
        DB::afterCommit(function () {
            $field = FormField::where('code', 'sectors_of_operation')->first();
            if (! $field) return;

            $options = Sector::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($s) => [
                    'value'    => $s->slug,
                    'label_en' => $s->name_en,
                    'label_ar' => $s->name_ar,
                ])->values()->all();

            $field->forceFill(['options' => $options])->saveQuietly();

            $formId = $field->section?->form_id ?? 'join-us';
            FormService::invalidateCache($formId);
        });
    }
}
```

- [ ] **Step 4: Register the observer in AppServiceProvider**

Open `app/Providers/AppServiceProvider.php` and add inside `boot()`:

```php
\App\Models\Sector::observe(\App\Observers\SectorObserver::class);
```

- [ ] **Step 5: Run observer tests**

```bash
php artisan test tests/Feature/SectorObserverTest.php
```

Expected: All 4 tests PASS.

- [ ] **Step 6: Run full test suite**

```bash
php artisan test
```

Expected: All tests PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Observers/SectorObserver.php app/Providers/AppServiceProvider.php tests/Feature/SectorObserverTest.php
git commit -m "feat: add SectorObserver to sync form field options on sector changes"
```

---

## Task 5: Data Migration (Migration 2)

**Files:**
- Create: `database/migrations/2026_05_20_100001_seed_codes_sections_and_fields.php`

This migration: backfills sector slugs, sets field codes, adds Fix-4 conditional field, inserts Section 3.

- [ ] **Step 1: Create the migration file**

```bash
php artisan make:migration seed_codes_sections_and_fields --path=database/migrations
```

Rename the file to `2026_05_20_100001_seed_codes_sections_and_fields.php` and replace its contents:

```php
<?php

use App\Models\FormField;
use App\Models\FormSection;
use App\Models\Sector;
use App\Services\FormService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Backfill sector slugs ──────────────────────────────────────
        foreach (Sector::withTrashed()->get() as $sector) {
            if ($sector->slug) continue;
            $base = Str::slug($sector->name_en ?: $sector->name_ar);
            $slug = $base;
            $i    = 2;
            while (Sector::withTrashed()->where('slug', $slug)->where('id', '!=', $sector->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $sector->slug = $slug;
            $sector->saveQuietly();
        }

        // ── Step 2: Set code + is_system_managed on Sectors of Operation field ─
        $sectorsField = FormField::where('label_en', 'Sectors of Operation')
            ->whereHas('section', fn($q) => $q->where('form_id', 'join-us'))
            ->first();

        if ($sectorsField) {
            $options = Sector::whereNull('deleted_at')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($s) => [
                    'value'    => $s->slug,
                    'label_en' => $s->name_en,
                    'label_ar' => $s->name_ar,
                ])->values()->all();

            $sectorsField->forceFill([
                'code'              => 'sectors_of_operation',
                'is_system_managed' => true,
                'options'           => $options,
            ])->saveQuietly();
        }

        // ── Step 3: Set code on Current Operations Country field ───────────────
        $countryField = FormField::where('label_en', 'Current Operations Country')
            ->whereHas('section', fn($q) => $q->where('form_id', 'join-us'))
            ->first();

        if ($countryField) {
            $countryField->forceFill(['code' => 'current_operations_country'])->saveQuietly();

            // ── Step 4: Add country_other_specify field ────────────────────────
            $alreadyExists = FormField::where('code', 'country_other_specify')
                ->whereHas('section', fn($q) => $q->where('form_id', 'join-us'))
                ->exists();

            if (! $alreadyExists) {
                FormField::create([
                    'section_id'        => $countryField->section_id,
                    'code'              => 'country_other_specify',
                    'label_en'          => 'Country Name (if Other)',
                    'label_ar'          => 'اسم البلد (إذا اخترت أخرى)',
                    'field_type'        => 'text',
                    'is_required'       => false,
                    'is_active'         => true,
                    'is_system_managed' => false,
                    'order_index'       => $countryField->order_index + 1,
                    'conditional_logic' => [
                        'operator'   => 'AND',
                        'conditions' => [
                            [
                                'field_code' => 'current_operations_country',
                                'op'         => 'equals',
                                'value'      => 'other',
                            ],
                        ],
                    ],
                    'placeholder_en'   => null,
                    'placeholder_ar'   => null,
                    'options'          => null,
                    'validation_rules' => null,
                    'file_config'      => null,
                ]);
            }
        }

        // ── Step 5: Shift existing sections with order_index >= 2 ─────────────
        FormSection::where('form_id', 'join-us')
            ->where('order_index', '>=', 2)
            ->orderBy('order_index', 'desc')
            ->each(fn($s) => $s->increment('order_index'));

        // ── Step 6: Insert Section 3 + 4 fields ───────────────────────────────
        $alreadyExists = FormSection::where('form_id', 'join-us')
            ->where('title_en', 'Section 3: Interests and Cooperation')
            ->exists();

        if (! $alreadyExists) {
            $section3 = FormSection::create([
                'form_id'       => 'join-us',
                'title_en'      => 'Section 3: Interests and Cooperation',
                'title_ar'      => 'القسم الثالث: الاهتمامات وتوجهات التعاون',
                'is_repeatable' => false,
                'max_repeats'   => null,
                'order_index'   => 2,
            ]);

            // Q1 — Professional Profile
            $q1 = FormField::create([
                'section_id'        => $section3->id,
                'code'              => 'professional_profile',
                'label_en'          => 'What is your professional profile or nature of commercial interest?',
                'label_ar'          => 'ما هي صفتك المهنية أو طبيعة اهتمامك التجاري؟',
                'field_type'        => 'checkbox_group',
                'is_required'       => true,
                'is_active'         => true,
                'is_system_managed' => false,
                'order_index'       => 0,
                'options'           => [
                    ['value' => 'investor',          'label_en' => 'Investor',               'label_ar' => 'مستثمر'],
                    ['value' => 'business_owner',    'label_en' => 'Business Owner',          'label_ar' => 'صاحب أعمال'],
                    ['value' => 'strategic_partner', 'label_en' => 'Strategic Partner',       'label_ar' => 'شريك استراتيجي'],
                    ['value' => 'service_provider',  'label_en' => 'Service Provider',        'label_ar' => 'مزود خدمات'],
                    ['value' => 'consultant',        'label_en' => 'Consultant or Expert',    'label_ar' => 'استشاري أو خبير'],
                    ['value' => 'other',             'label_en' => 'Other (Please specify)',  'label_ar' => 'أخرى (يرجى التحديد)'],
                ],
                'placeholder_en'   => null,
                'placeholder_ar'   => null,
                'validation_rules' => null,
                'file_config'      => null,
                'conditional_logic'=> null,
            ]);

            // Q1a — Other specify
            FormField::create([
                'section_id'        => $section3->id,
                'code'              => 'professional_profile_other',
                'label_en'          => 'Please specify (Other)',
                'label_ar'          => 'يرجى التحديد (أخرى)',
                'field_type'        => 'text',
                'is_required'       => false,
                'is_active'         => true,
                'is_system_managed' => false,
                'order_index'       => 1,
                'conditional_logic' => [
                    'operator'   => 'AND',
                    'conditions' => [
                        [
                            'field_code' => 'professional_profile',
                            'op'         => 'contains',
                            'value'      => 'other',
                        ],
                    ],
                ],
                'placeholder_en'   => null,
                'placeholder_ar'   => null,
                'options'          => null,
                'validation_rules' => null,
                'file_config'      => null,
            ]);

            // Q2 — Target Market
            FormField::create([
                'section_id'        => $section3->id,
                'code'              => 'target_market',
                'label_en'          => 'What is the target market for your operations or investments?',
                'label_ar'          => 'ما هو السوق المستهدف لعملياتكم أو استثماراتكم؟',
                'field_type'        => 'checkbox_group',
                'is_required'       => true,
                'is_active'         => true,
                'is_system_managed' => false,
                'order_index'       => 2,
                'options'           => [
                    ['value' => 'syrian_market',  'label_en' => 'Syrian Market',          'label_ar' => 'السوق السورية'],
                    ['value' => 'saudi_market',   'label_en' => 'Saudi Market',           'label_ar' => 'السوق السعودية'],
                    ['value' => 'both_markets',   'label_en' => 'Both Markets',           'label_ar' => 'كلا السوقين'],
                    ['value' => 'other_regional', 'label_en' => 'Other Regional Markets', 'label_ar' => 'أسواق إقليمية أخرى'],
                ],
                'placeholder_en'   => null,
                'placeholder_ar'   => null,
                'validation_rules' => null,
                'file_config'      => null,
                'conditional_logic'=> null,
            ]);

            // Q3 — Type of Cooperation
            FormField::create([
                'section_id'        => $section3->id,
                'code'              => 'cooperation_type',
                'label_en'          => 'What kind of cooperation are you looking for by joining the council?',
                'label_ar'          => 'ما نوع التعاون الذي تبحث عنه من خلال انضمامكم للمجلس؟',
                'field_type'        => 'checkbox_group',
                'is_required'       => true,
                'is_active'         => true,
                'is_system_managed' => false,
                'order_index'       => 3,
                'options'           => [
                    ['value' => 'investment_opps',    'label_en' => 'Exploring Investment Opportunities',              'label_ar' => 'البحث عن فرص استثمارية'],
                    ['value' => 'joint_ventures',     'label_en' => 'Building Joint Ventures',                         'label_ar' => 'بناء مشاريع مشتركة'],
                    ['value' => 'market_entry',       'label_en' => 'Market Entry Support and Facilitation',           'label_ar' => 'دعم وتسهيلات دخول السوق'],
                    ['value' => 'strategic_partners', 'label_en' => 'Establishing Strategic Partnerships',             'label_ar' => 'عقد شراكات استراتيجية'],
                    ['value' => 'distribution',       'label_en' => 'Building Distribution Partnerships and Agencies', 'label_ar' => 'بناء شراكات توزيع ووكالات'],
                    ['value' => 'legal_support',      'label_en' => 'Legal and Regulatory Support',                    'label_ar' => 'الحصول على دعم قانوني وتنظيمي'],
                    ['value' => 'matchmaking',        'label_en' => 'Business Matchmaking and Networking',             'label_ar' => 'توفيق الأعمال والتشبيك'],
                    ['value' => 'export_import',      'label_en' => 'Export and Import Opportunities',                 'label_ar' => 'البحث عن فرص تصدير واستيراد'],
                    ['value' => 'financing',          'label_en' => 'Seeking Financing Opportunities',                 'label_ar' => 'البحث عن فرص تمويلية'],
                ],
                'placeholder_en'   => null,
                'placeholder_ar'   => null,
                'validation_rules' => null,
                'file_config'      => null,
                'conditional_logic'=> null,
            ]);
        }

        // ── Step 7: Invalidate form cache ─────────────────────────────────────
        FormService::invalidateCache('join-us');
    }

    public function down(): void
    {
        // Remove Section 3 and its fields (cascade via foreign key or explicit)
        $section3 = FormSection::where('form_id', 'join-us')
            ->where('title_en', 'Section 3: Interests and Cooperation')
            ->first();
        if ($section3) {
            FormField::where('section_id', $section3->id)->delete();
            $section3->delete();
        }

        // Remove country_other_specify field
        FormField::where('code', 'country_other_specify')->delete();

        // Restore order_index for shifted sections
        FormSection::where('form_id', 'join-us')
            ->where('order_index', '>=', 3)
            ->orderBy('order_index')
            ->each(fn($s) => $s->decrement('order_index'));

        // Clear codes and system flags
        FormField::where('code', 'sectors_of_operation')->update([
            'code'              => null,
            'is_system_managed' => false,
        ]);
        FormField::where('code', 'current_operations_country')->update(['code' => null]);

        FormService::invalidateCache('join-us');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: "Migrating: 2026_05_20_100001_seed_codes_sections_and_fields" then "Migrated".

- [ ] **Step 3: Verify data in tinker**

```bash
php artisan tinker
```

Run inside tinker:
```php
App\Models\FormField::where('code', 'sectors_of_operation')->value('is_system_managed'); // true
App\Models\FormField::where('code', 'country_other_specify')->value('label_en'); // "Country Name (if Other)"
App\Models\FormSection::where('form_id','join-us')->orderBy('order_index')->pluck('title_en','order_index');
// {0: "Personal Information", 1: "Company Information", 2: "Section 3: Interests and Cooperation", 3: "Required Documents", 4: "Declaration"}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_20_100001_seed_codes_sections_and_fields.php
git commit -m "feat: data migration — sector slugs, field codes, Fix-4 conditional field, Section 3"
```

---

## Task 6: FormBuilderController — System-Managed Guards

**Files:**
- Modify: `app/Http/Controllers/Admin/FormBuilderController.php`
- Modify: `tests/Feature/FormBuilderTest.php`

- [ ] **Step 1: Write failing tests for the guards**

Open `tests/Feature/FormBuilderTest.php` and add these two tests to the existing class:

```php
public function test_cannot_update_options_on_system_managed_field(): void
{
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);
    $formDef = \App\Models\FormDefinition::where('form_id', 'join-us')->first()
        ?? \App\Models\FormDefinition::factory()->create(['form_id' => 'join-us', 'title_en' => 'Join Us', 'title_ar' => 'انضم إلينا']);

    $section = \App\Models\FormSection::create([
        'form_id' => 'join-us', 'title_en' => 'S', 'title_ar' => 'س',
        'is_repeatable' => false, 'order_index' => 0,
    ]);
    $field = \App\Models\FormField::create([
        'section_id'        => $section->id,
        'code'              => 'sectors_of_operation',
        'label_en'          => 'Sectors of Operation',
        'label_ar'          => 'قطاعات العمل',
        'field_type'        => 'checkbox_group',
        'is_required'       => true,
        'is_active'         => true,
        'is_system_managed' => true,
        'order_index'       => 0,
        'options'           => [['value' => 'old', 'label_en' => 'Old', 'label_ar' => 'قديم']],
    ]);

    $this->actingAs($admin)->putJson(
        route('admin.forms.fields.update', [$formDef, $field]),
        [
            'section_id'  => $section->id,
            'label_en'    => 'Updated Label',
            'label_ar'    => 'تسمية محدثة',
            'field_type'  => 'radio', // should be blocked
            'is_required' => true,
            'is_active'   => true,
            'options'     => [['value' => 'new', 'label_en' => 'New', 'label_ar' => 'جديد']],
        ]
    )->assertOk();

    $field->refresh();
    // label updated — allowed
    $this->assertSame('Updated Label', $field->label_en);
    // field_type and options NOT changed — blocked by allow-list
    $this->assertSame('checkbox_group', $field->field_type);
    $this->assertSame('old', $field->options[0]['value']);
}

public function test_cannot_delete_system_managed_field(): void
{
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);
    $formDef = \App\Models\FormDefinition::where('form_id', 'join-us')->first()
        ?? \App\Models\FormDefinition::factory()->create(['form_id' => 'join-us', 'title_en' => 'Join Us', 'title_ar' => 'انضم إلينا']);

    $section = \App\Models\FormSection::create([
        'form_id' => 'join-us', 'title_en' => 'S', 'title_ar' => 'س',
        'is_repeatable' => false, 'order_index' => 0,
    ]);
    $field = \App\Models\FormField::create([
        'section_id'        => $section->id,
        'code'              => 'sectors_of_operation',
        'label_en'          => 'Sectors of Operation',
        'label_ar'          => 'قطاعات العمل',
        'field_type'        => 'checkbox_group',
        'is_required'       => true,
        'is_active'         => true,
        'is_system_managed' => true,
        'order_index'       => 0,
    ]);

    $this->actingAs($admin)
        ->deleteJson(route('admin.forms.fields.destroy', [$formDef, $field]))
        ->assertStatus(422);

    $this->assertDatabaseHas('form_fields', ['id' => $field->id]);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
php artisan test tests/Feature/FormBuilderTest.php --filter="test_cannot_update_options_on_system_managed_field|test_cannot_delete_system_managed_field"
```

Expected: Both FAIL.

- [ ] **Step 3: Update FormBuilderController**

Open `app/Http/Controllers/Admin/FormBuilderController.php`.

Replace the `validateField()` method with:

```php
private function validateField(Request $request, ?FormField $existing = null): array
{
    $data = $request->validate([
        'section_id'         => ['required', 'integer', 'exists:form_sections,id'],
        'label_en'           => ['required', 'string', 'max:500'],
        'label_ar'           => ['required', 'string', 'max:500'],
        'placeholder_en'     => ['nullable', 'string', 'max:255'],
        'placeholder_ar'     => ['nullable', 'string', 'max:255'],
        'field_type'         => ['required', Rule::in(self::FIELD_TYPES)],
        'is_required'        => ['boolean'],
        'is_active'          => ['boolean'],
        'options'            => ['nullable', 'array'],
        'options.*.label_en' => ['required_with:options', 'string'],
        'options.*.label_ar' => ['required_with:options', 'string'],
        'options.*.value'    => ['required_with:options', 'string'],
        'validation_rules'   => ['nullable', 'array'],
        'file_config'        => ['nullable', 'array'],
        'file_config.accepted_types' => ['nullable', 'array'],
        'file_config.max_size_mb'    => ['nullable', 'integer', 'min:1', 'max:50'],
    ]);

    foreach (['label_en', 'label_ar', 'placeholder_en', 'placeholder_ar'] as $key) {
        if (isset($data[$key])) {
            $data[$key] = strip_tags($data[$key]);
        }
    }

    // System-managed fields: only allow safe keys to be updated
    if ($existing && $existing->is_system_managed) {
        $allowed = ['label_en', 'label_ar', 'placeholder_en', 'placeholder_ar',
                    'is_required', 'is_active', 'order_index', 'section_id'];
        $data = array_intersect_key($data, array_flip($allowed));
    }

    return $data;
}
```

Replace the `destroyField()` method with:

```php
public function destroyField(FormDefinition $formDefinition, FormField $field): JsonResponse
{
    $this->ensureFieldBelongsToForm($field, $formDefinition);

    if ($field->is_system_managed) {
        return response()->json(['success' => false, 'error' => 'System-managed fields cannot be deleted.'], 422);
    }

    $field->delete();
    FormService::invalidateCache($formDefinition->form_id);

    return response()->json(['success' => true]);
}
```

- [ ] **Step 4: Run the tests**

```bash
php artisan test tests/Feature/FormBuilderTest.php
```

Expected: All tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/FormBuilderController.php tests/Feature/FormBuilderTest.php
git commit -m "feat: guard system-managed fields in FormBuilderController"
```

---

## Task 7: FormSubmissionService — fieldIsVisible + Checkbox Fix

**Files:**
- Modify: `app/Services/FormSubmissionService.php`
- Create: `tests/Feature/FormConditionalLogicTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/FormConditionalLogicTest.php`:

```php
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
            'is_required'       => false,
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
                // deliberately omit $other->id
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
            'answers' => [
                $name->id    => [0 => 'Ahmad'],
                $country->id => [0 => 'syria'],
                // $other->id not submitted — condition is false, should be fine
            ],
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
            'answers' => [
                $name->id    => [0 => 'Ahmad'],
                $country->id => [0 => 'syria'],
                $other->id   => [0 => 'Some stale value'],  // submitted but condition is false
            ],
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

        // Submit with empty array — should fail required validation
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
            'answers' => [$checkboxField->id => [0 => ['investor']]],
            '_repeats' => [],
        ]);

        $response->assertRedirect('/en/join/thanks');
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
php artisan test tests/Feature/FormConditionalLogicTest.php
```

Expected: All 5 tests FAIL (conditional logic and checkbox fix not yet implemented).

- [ ] **Step 3: Update FormSubmissionService**

Replace the contents of `app/Services/FormSubmissionService.php` with:

```php
<?php

namespace App\Services;

use App\Mail\AdminSubmissionNotification;
use App\Mail\ApplicantConfirmation;
use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormUpload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FormSubmissionService
{
    public function store(Request $request, string $formId, bool $sendApplicantConfirmation = false): FormSubmission
    {
        $form = FormService::getActiveForm($formId);

        $this->normaliseAnswers($request, $form);
        $request->validate($this->rulesFor($request, $form), [
            'answers.*.*.regex'          => 'Please enter a phone number with country code, e.g. +966 50 000 0000.',
            'answers.*.*.before'         => 'You must be at least 18 years old.',
            'answers.*.*.before_or_equal'=> 'Date cannot be in the future.',
            'answers.*.*.min'            => 'Please select at least one option.',
        ]);

        $submission = FormSubmission::create([
            'form_id'      => $formId,
            'display_name' => strip_tags((string) $this->displayName($request, $form)),
            'ip_address'   => $request->ip(),
            'submitted_at' => now(),
        ]);

        $this->storeAnswers($request, $submission, $form);
        $this->storeUploads($request, $submission);

        Mail::to('info@ssbc.org')->queue(new AdminSubmissionNotification($submission));

        if ($sendApplicantConfirmation) {
            $this->sendApplicantConfirmation($form, $submission);
        }

        $this->notifyGoogleSheet($submission);

        return $submission;
    }

    private function normaliseAnswers(Request $request, Collection $form): void
    {
        $answers = (array) $request->input('answers', []);

        foreach ($form as $section) {
            foreach ($section->fields as $field) {
                if ($field->field_type !== 'tel' || ! isset($answers[$field->id])) {
                    continue;
                }

                foreach ($answers[$field->id] as $repeat => $value) {
                    if (is_string($value) && $value !== '') {
                        $answers[$field->id][$repeat] = preg_replace('/\s+/', '', $value);
                    }
                }
            }
        }

        $request->merge(['answers' => $answers]);
    }

    private function rulesFor(Request $request, Collection $form): array
    {
        $rules   = ['_repeats' => 'array'];
        $repeats = $request->input('_repeats', []);
        $answers = (array) $request->input('answers', []);
        $today   = now()->toDateString();
        $dobMax  = now()->subYears(18)->toDateString();
        $phoneRegex = 'regex:/^\+[1-9]\d{7,14}$/';

        foreach ($form as $section) {
            $count = $section->is_repeatable ? max(1, (int) ($repeats[$section->id] ?? 1)) : 1;

            foreach ($section->fields as $field) {
                $labelLower = strtolower($field->label_en ?? '');
                $isDob      = str_contains($labelLower, 'birth') || str_contains($labelLower, 'dob');

                for ($repeat = 0; $repeat < $count; $repeat++) {
                    if ($field->field_type === 'file') {
                        $rules["files.{$field->id}.{$repeat}"] = array_values(array_filter([
                            $field->is_required && $repeat === 0 ? 'required' : 'nullable',
                            'file',
                            'mimes:'.$field->acceptedMimes(),
                            'max:'.$field->maxFileSizeKb(),
                        ]));
                        continue;
                    }

                    // Skip hidden conditional fields
                    if (! $this->fieldIsVisible($field, $answers, $repeat, $form)) {
                        $rules["answers.{$field->id}.{$repeat}"] = ['nullable'];
                        continue;
                    }

                    // checkbox_group: require array with at least 1 item
                    if ($field->field_type === 'checkbox_group') {
                        $rules["answers.{$field->id}.{$repeat}"] = $field->is_required && $repeat === 0
                            ? ['required', 'array', 'min:1']
                            : ['nullable', 'array'];
                        continue;
                    }

                    $fieldRules = [$field->is_required && $repeat === 0 ? 'required' : 'nullable'];

                    if ($field->field_type === 'email') {
                        $fieldRules[] = 'email';
                    } elseif ($field->field_type === 'url') {
                        $fieldRules[] = 'url';
                    } elseif ($field->field_type === 'tel') {
                        $fieldRules[] = $phoneRegex;
                    } elseif ($field->field_type === 'number') {
                        $fieldRules[] = 'integer';
                        if (($field->validation_rules['min'] ?? null) !== null) {
                            $fieldRules[] = 'min:'.$field->validation_rules['min'];
                        }
                        if (($field->validation_rules['max'] ?? null) !== null) {
                            $fieldRules[] = 'max:'.$field->validation_rules['max'];
                        }
                    } elseif ($field->field_type === 'date') {
                        $fieldRules[] = 'date';
                        $fieldRules[] = $isDob ? "before:{$dobMax}" : "before_or_equal:{$today}";
                    }

                    $rules["answers.{$field->id}.{$repeat}"] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    /**
     * Evaluate a field's conditional_logic against the submitted answers.
     * Returns true if the field should be shown (no logic = always visible).
     */
    private function fieldIsVisible(FormField $field, array $answers, int $repeat, Collection $form): bool
    {
        $logic = $field->conditional_logic ?? null;
        if (! $logic || empty($logic['conditions'])) {
            return true;
        }

        // Build code → field-id map from the form
        $codeToId = $form->flatMap->fields
            ->filter(fn($f) => $f->code !== null)
            ->mapWithKeys(fn($f) => [$f->code => $f->id])
            ->all();

        $results = [];
        foreach ($logic['conditions'] as $c) {
            $targetId = $codeToId[$c['field_code']] ?? null;
            $val      = $targetId !== null
                ? ($answers[$targetId][$repeat] ?? $answers[$targetId][0] ?? null)
                : null;

            $results[] = match ($c['op']) {
                'equals'     => $val === $c['value'],
                'not_equals' => $val !== $c['value'],
                'in'         => in_array($val, (array) $c['value'], true),
                'not_in'     => ! in_array($val, (array) $c['value'], true),
                'contains'   => is_array($val)
                                    ? in_array($c['value'], $val, true)
                                    : (is_string($val) && str_contains($val, (string) $c['value'])),
                default      => true,
            };
        }

        return ($logic['operator'] ?? 'AND') === 'OR'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    private function displayName(Request $request, Collection $form): ?string
    {
        $firstSection = $form->first();
        $nameFieldId  = $firstSection?->fields->where('field_type', 'text')->first()?->id;

        return $nameFieldId ? $request->input("answers.{$nameFieldId}.0") : null;
    }

    private function storeAnswers(Request $request, FormSubmission $submission, Collection $form): void
    {
        $fieldsById = $form->flatMap->fields->keyBy('id');
        $answers    = (array) $request->input('answers', []);
        $answerRows = [];

        foreach ($answers as $fieldId => $repeatValues) {
            $field = $fieldsById->get((int) $fieldId);
            if (! $field) continue;

            foreach ($repeatValues as $repeatIndex => $value) {
                if ($value === null || $value === '' || $value === []) continue;

                // Skip answers for hidden conditional fields
                if (! $this->fieldIsVisible($field, $answers, (int) $repeatIndex, $form)) {
                    continue;
                }

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
    }

    private function storeUploads(Request $request, FormSubmission $submission): void
    {
        $rawFiles = $request->file('files', []);

        if (empty($rawFiles)) {
            Log::warning('form_submission.store: no files in request', [
                'submission_id' => $submission->id,
                'form_id'       => $submission->form_id,
                'content_type'  => $request->header('content-type'),
                'content_length'=> $request->header('content-length'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_size'=> ini_get('upload_max_filesize'),
                'has_files_key' => isset($_FILES['files']),
                'all_files_keys'=> array_keys($request->allFiles()),
            ]);
        }

        foreach ($rawFiles as $fieldId => $repeatFiles) {
            foreach ($repeatFiles as $repeatIndex => $file) {
                if (! $file || ! $file->isValid()) continue;

                try {
                    $path = $file->store("submissions/{$submission->id}", 'public');
                    if (! $path) continue;

                    FormUpload::create([
                        'submission_id' => $submission->id,
                        'field_id'      => (int) $fieldId,
                        'repeat_index'  => (int) $repeatIndex,
                        'file_path'     => $path,
                        'file_name'     => $file->getClientOriginalName(),
                        'file_size'     => $file->getSize(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('form_submission.store: failed to persist upload', [
                        'submission_id' => $submission->id,
                        'field_id'      => $fieldId,
                        'repeat_index'  => $repeatIndex,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function sendApplicantConfirmation(Collection $form, FormSubmission $submission): void
    {
        $emailFieldId  = $form->flatMap->fields->where('field_type', 'email')->first()?->id;
        $applicantEmail = $emailFieldId
            ? FormAnswer::where('submission_id', $submission->id)->where('field_id', $emailFieldId)->value('answer_value')
            : null;

        if ($applicantEmail && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
            Mail::to($applicantEmail)->queue(new ApplicantConfirmation($submission));
        }
    }

    private function notifyGoogleSheet(FormSubmission $submission): void
    {
        $scriptUrl = config('services.google_script_url');
        if (! $scriptUrl) return;

        try {
            Http::timeout(5)->post($scriptUrl, [
                'form_id'       => $submission->form_id,
                'display_name'  => $submission->display_name,
                'submission_id' => $submission->id,
                'submitted_at'  => $submission->submitted_at->toISOString(),
            ]);
        } catch (\Throwable) {
            // Fire and forget.
        }
    }
}
```

- [ ] **Step 4: Run the conditional logic tests**

```bash
php artisan test tests/Feature/FormConditionalLogicTest.php
```

Expected: All 5 tests PASS.

- [ ] **Step 5: Run full test suite**

```bash
php artisan test
```

Expected: All tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/FormSubmissionService.php tests/Feature/FormConditionalLogicTest.php
git commit -m "feat: add fieldIsVisible helper, fix checkbox_group required validation, skip hidden answers"
```

---

## Task 8: Home Page — Fix 1 (Dynamic Pillars) + Fix 2 (Remove Economic Sectors)

**Files:**
- Modify: `resources/views/pages/home.blade.php`
- Delete: `resources/views/pages/partials/sectors.blade.php`

- [ ] **Step 1: Replace the Strategic Pillars loop (lines 115–122)**

Open `resources/views/pages/home.blade.php`. Find section 4 (Strategic Pillars). Replace lines 115–122:

**Before:**
```blade
        <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-10">
            @foreach($site->homeList($locale, 'pillars.items', (array) __('home.pillars.items')) as $item)
                <div class="ssbc-pillar-card">
                    <h3 class="text-lg font-display font-semibold text-ssbc-green mb-2">{{ $item['title'] ?? '' }}</h3>
                    <p class="text-sm text-ssbc-dark/75 leading-relaxed">{{ $item['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
```

**After:**
```blade
        <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-10">
            @foreach($sectors as $sector)
                <div class="ssbc-pillar-card">
                    <h3 class="text-lg font-display font-semibold text-ssbc-green mb-2">{{ $sector->name() }}</h3>
                    <p class="text-sm text-ssbc-dark/75 leading-relaxed">{{ $sector->description() }}</p>
                </div>
            @endforeach
        </div>
```

- [ ] **Step 2: Remove the Economic Sectors include (line 127)**

Remove these three lines from `home.blade.php`:

```blade
{{-- 4b. Sectors --}}
@include('pages.partials.sectors')
```

- [ ] **Step 3: Delete the sectors partial**

```bash
rm "C:\LevareLabs\sysabc.org\resources\views\pages\partials\sectors.blade.php"
```

Or on PowerShell: `Remove-Item "C:\LevareLabs\sysabc.org\resources\views\pages\partials\sectors.blade.php"`

- [ ] **Step 4: Run tests to confirm nothing broke**

```bash
php artisan test
```

Expected: All tests PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/pages/home.blade.php
git rm resources/views/pages/partials/sectors.blade.php
git commit -m "feat: show dynamic sectors in Strategic Pillars; remove Economic Sectors section"
```

---

## Task 9: Join Form — Alpine Conditional Logic + Checkbox Arrays

**Files:**
- Modify: `resources/views/join/create.blade.php`

This task has no automated tests — it requires manual verification in a browser.

- [ ] **Step 1: Add `codeToId` map to the Alpine component**

In `resources/views/join/create.blade.php`, find the `dynamicForm(sectionsJson)` function (line ~353). Add `codeToId: {}` to the reactive data object, and update `init()` to build the map.

**Before** (the return object data keys, ~line 362):
```js
    return {
        sections,
        locale,
        step: 0,
        activeRepeat: 0,
        answers: {},
        dateParts: {},
        checkboxAnswers: {},
        repeats: {},
        fileNames: {},
        fileErrors: {},
        stepErrors: {},
        submitting: false,
        submitError: null,
        firstErrorStep: null,
        months: [
```

**After:**
```js
    return {
        sections,
        locale,
        step: 0,
        activeRepeat: 0,
        answers: {},
        dateParts: {},
        checkboxAnswers: {},
        repeats: {},
        fileNames: {},
        fileErrors: {},
        stepErrors: {},
        submitting: false,
        submitError: null,
        firstErrorStep: null,
        codeToId: {},
        months: [
```

**Before** (`init()` body, ~line 384):
```js
        init() {
            sections.forEach(s => {
                if (s.is_repeatable) this.repeats[s.id] = 1;
                this.initDatePartsForSection(s, 1);
            });
        },
```

**After:**
```js
        init() {
            sections.forEach(s => {
                if (s.is_repeatable) this.repeats[s.id] = 1;
                this.initDatePartsForSection(s, 1);
                (s.fields || []).forEach(f => {
                    if (f.code) this.codeToId[f.code] = f.id;
                });
            });
        },
```

- [ ] **Step 2: Add `fieldIsVisible()` method to the Alpine component**

After the `init()` method, add:

```js
        fieldIsVisible(field, ri) {
            const logic = field.conditional_logic;
            if (!logic || !logic.conditions) return true;
            const results = logic.conditions.map(c => {
                const id = this.codeToId[c.field_code];
                if (id === undefined) return true;
                switch (c.op) {
                    case 'equals':     return this.answers[id + '_' + ri] === c.value;
                    case 'not_equals': return this.answers[id + '_' + ri] !== c.value;
                    case 'contains': {
                        const arr = this.checkboxAnswers[id + '_' + ri] ?? [];
                        return arr.includes(c.value);
                    }
                    default: return true;
                }
            });
            return logic.operator === 'OR'
                ? results.some(Boolean)
                : results.every(Boolean);
        },
```

- [ ] **Step 3: Add `x-show` conditional to each field container**

Find the field container div (line ~132):

**Before:**
```blade
                                    <template x-for="field in section.fields" :key="field.id">
                                        <div>
                                            {{-- Label --}}
                                            <label class="ssbc-label" :for="'f_' + field.id + '_' + (ri-1)">
```

**After:**
```blade
                                    <template x-for="field in section.fields" :key="field.id">
                                        <div x-show="fieldIsVisible(field, ri-1)">
                                            {{-- Label --}}
                                            <label class="ssbc-label" :for="'f_' + field.id + '_' + (ri-1)">
```

- [ ] **Step 4: Replace hidden JSON checkbox input with array inputs**

Find the checkbox_group template (lines ~244–263):

**Before** (the hidden serialized input at the bottom):
```blade
                                            {{-- checkbox_group --}}
                                            <template x-if="field.field_type === 'checkbox_group'">
                                                <div>
                                                    <div class="grid sm:grid-cols-2 gap-2 mt-1">
                                                        <template x-for="opt in (field.options || [])" :key="opt.value">
                                                            <label class="flex items-start gap-2 cursor-pointer text-sm p-2 hover:bg-ssbc-beige/40 rounded transition-colors">
                                                                <input type="checkbox"
                                                                       :value="opt.value"
                                                                       :checked="(checkboxAnswers[field.id + '_' + (ri-1)] || []).includes(opt.value)"
                                                                       @change="toggleCheckbox(field.id, ri-1, opt.value)"
                                                                       class="mt-0.5 shrink-0 text-ssbc-gold focus:ring-ssbc-gold">
                                                                <span x-text="locale === 'ar' ? opt.label_ar : opt.label_en"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                    {{-- Hidden serialized value --}}
                                                    <input type="hidden"
                                                           :name="'answers[' + field.id + '][' + (ri-1) + ']'"
                                                           :value="JSON.stringify(checkboxAnswers[field.id + '_' + (ri-1)] || [])">
                                                </div>
                                            </template>
```

**After** (each checkbox posts as `answers[id][ri][]`; remove the hidden input):
```blade
                                            {{-- checkbox_group --}}
                                            <template x-if="field.field_type === 'checkbox_group'">
                                                <div>
                                                    <div class="grid sm:grid-cols-2 gap-2 mt-1">
                                                        <template x-for="opt in (field.options || [])" :key="opt.value">
                                                            <label class="flex items-start gap-2 cursor-pointer text-sm p-2 hover:bg-ssbc-beige/40 rounded transition-colors">
                                                                <input type="checkbox"
                                                                       :name="'answers[' + field.id + '][' + (ri-1) + '][]'"
                                                                       :value="opt.value"
                                                                       :checked="(checkboxAnswers[field.id + '_' + (ri-1)] || []).includes(opt.value)"
                                                                       @change="toggleCheckbox(field.id, ri-1, opt.value)"
                                                                       class="mt-0.5 shrink-0 text-ssbc-gold focus:ring-ssbc-gold">
                                                                <span x-text="locale === 'ar' ? opt.label_ar : opt.label_en"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
```

- [ ] **Step 5: Update validateSection() to skip hidden conditional fields**

Find `validateSection(s)` (~line 555). Update the loop to skip fields where `fieldIsVisible` returns false:

**Before:**
```js
            for (const field of s.fields) {
                for (let r = 0; r < count; r++) {
                    if (r > 0 && !s.is_repeatable) break;
                    const key = field.id + '_' + r;
                    const requiredHere = field.is_required && r === 0;

                    if (field.field_type === 'checkbox_group') {
```

**After:**
```js
            for (const field of s.fields) {
                for (let r = 0; r < count; r++) {
                    if (r > 0 && !s.is_repeatable) break;
                    if (!this.fieldIsVisible(field, r)) continue;
                    const key = field.id + '_' + r;
                    const requiredHere = field.is_required && r === 0;

                    if (field.field_type === 'checkbox_group') {
```

- [ ] **Step 6: Run full test suite**

```bash
php artisan test
```

Expected: All tests PASS.

- [ ] **Step 7: Manual verification in browser**

Start the dev server and navigate to `/en/join`:

```bash
php artisan serve
```

Check:
1. **Section 2 — Country field:** Select "Other" → "Country Name (if Other)" text input appears. Select "Syria" → input disappears.
2. **Section 2 — Sectors:** All 15 sectors appear as checkboxes.
3. **Section 3 exists** with Q1, Q2, Q3 checkbox groups.
4. **Section 3 — Q1:** Check "Other (Please specify)" → text input appears. Uncheck → input disappears.
5. **Step validation:** Try advancing without selecting any checkbox in a required group → error message appears.
6. **Submit a complete form** → redirects to `/en/join/thanks`.
7. **Check admin submissions view** → Section 3 answers appear correctly.

- [ ] **Step 8: Commit**

```bash
git add resources/views/join/create.blade.php
git commit -m "feat: Alpine conditional field visibility, checkbox array inputs, skip hidden in validateSection"
```

---

## Task 10: Admin Form-Builder — System-Managed Field UI

**Files:**
- Modify: `resources/views/admin/form-builder/index.blade.php`

- [ ] **Step 1: Update the field row to show system-managed badge and hide delete**

Find the field row template (line ~56). The current field row is:

```blade
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
```

Replace with:

```blade
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
                                    <span x-show="field.is_system_managed"
                                          class="ml-1 text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded border border-amber-300">🔒 system</span>
                                </div>
                                <button type="button" @click="openFieldModal(field, section.id)"
                                        class="text-xs text-ssbc-sage hover:text-ssbc-green px-2">Edit</button>
                                <a x-show="field.is_system_managed"
                                   href="{{ route('admin.sectors.index') }}"
                                   class="text-xs text-ssbc-gold hover:underline px-2">Manage Sectors ↗</a>
                                <button x-show="!field.is_system_managed"
                                        type="button" @click="confirmDeleteField(field, section)"
                                        class="text-xs text-red-500 hover:text-red-700 px-2">Delete</button>
                            </div>
                        </template>
```

- [ ] **Step 2: Disable field_type and options editor in the field modal for system-managed fields**

Find the field modal's Field Type select (line ~155):

```blade
                <div>
                    <label class="ssbc-label">Field Type *</label>
                    <select x-model="fieldForm.field_type" class="ssbc-input">
                        @foreach($fieldTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
```

Replace with:

```blade
                <div>
                    <label class="ssbc-label">Field Type *</label>
                    <select x-model="fieldForm.field_type" class="ssbc-input"
                            :disabled="editingField?.is_system_managed">
                        @foreach($fieldTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
```

Find the options builder div (line ~174):

```blade
            {{-- Options builder (select / radio / checkbox_group) --}}
            <div x-show="['select','radio','checkbox_group'].includes(fieldForm.field_type)" class="mt-6">
```

Replace with:

```blade
            {{-- Options builder (select / radio / checkbox_group) --}}
            <div x-show="['select','radio','checkbox_group'].includes(fieldForm.field_type) && !editingField?.is_system_managed" class="mt-6">
```

Add a note that appears when viewing a system-managed field. After the options builder closing `</div>`, add:

```blade
            {{-- System-managed notice --}}
            <div x-show="editingField?.is_system_managed" class="mt-4 border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Options for this field are managed automatically via <a href="{{ route('admin.sectors.index') }}" class="underline font-medium">Sectors admin</a>. Labels, placeholders, and required status can still be edited here.
            </div>
```

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: All tests PASS.

- [ ] **Step 4: Manual verification**

In the admin at `/admin/forms/{id}/builder`:
1. Open the "Sectors of Operation" field → Field Type dropdown is disabled, options editor is hidden, amber system notice is visible.
2. Delete button is replaced by "Manage Sectors ↗" link.
3. Editing labels on the system-managed field still saves correctly.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/form-builder/index.blade.php
git commit -m "feat: mark system-managed fields in form builder UI"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Task |
|---|---|
| Fix 1: Dynamic sectors in Strategic Pillars | Task 8 |
| Fix 2: Remove Economic Sectors section | Task 8 |
| Fix 3: SectorObserver + slug + soft-delete + formatAnswer fallback | Tasks 2, 3, 4 |
| Fix 3: code column + is_system_managed + FormBuilderController guard | Tasks 1, 6 |
| Fix 4: country_other_specify field + conditional_logic column | Tasks 1, 5, 7, 9 |
| Fix 5: Section 3 with Q1/Q1a/Q2/Q3 | Task 5, 9 |
| checkbox_group required validation bug fix | Task 7 |
| Admin form-builder UI for system-managed fields | Task 10 |
| .gitignore ssbc_app/ | Task 1 |

All spec requirements covered.

**Placeholder scan:** No TBD/TODO/placeholder text present. All code steps show complete implementation.

**Type consistency:** `fieldIsVisible()` signature `(FormField $field, array $answers, int $repeat, Collection $form): bool` is consistent across Task 4 tests and Task 7 implementation. `codeToId` used consistently in Alpine (Task 9) and PHP (Task 7). `saveQuietly()` used in observer (Task 4) and migration (Task 5).
