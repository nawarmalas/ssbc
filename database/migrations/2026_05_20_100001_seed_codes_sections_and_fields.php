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
