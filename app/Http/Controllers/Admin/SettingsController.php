<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Schema of editable copy fields. Each value is the dot-key used in the
     * JSON column AND the `__('home.x')` / `__('about.x')` translation key.
     */
    private const HOME_FIELDS = [
        'Hero' => [
            'hero.eyebrow'       => ['label' => 'Eyebrow',       'type' => 'text'],
            'hero.headline'      => ['label' => 'Headline',      'type' => 'text'],
            'hero.tagline'       => ['label' => 'Arabic tagline (poetic line)', 'type' => 'text'],
            'hero.body'          => ['label' => 'Body',          'type' => 'textarea'],
            'hero.cta_primary'   => ['label' => 'Primary CTA',   'type' => 'text'],
            'hero.cta_secondary' => ['label' => 'Secondary CTA', 'type' => 'text'],
        ],
        'Overview' => [
            'overview.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text'],
            'overview.heading' => ['label' => 'Heading', 'type' => 'text'],
            'overview.body'    => ['label' => 'Body',    'type' => 'textarea'],
        ],
        'Mission · Vision · Values' => [
            'mvv.eyebrow'        => ['label' => 'Eyebrow',       'type' => 'text'],
            'mvv.mission_label'  => ['label' => 'Mission label', 'type' => 'text'],
            'mvv.mission'        => ['label' => 'Mission text',  'type' => 'textarea'],
            'mvv.vision_label'   => ['label' => 'Vision label',  'type' => 'text'],
            'mvv.vision'         => ['label' => 'Vision text',   'type' => 'textarea'],
            'mvv.values_label'   => ['label' => 'Values label',  'type' => 'text'],
            'mvv.values'         => [
                'label' => 'Value chips',
                'type'  => 'list',
                'count' => 6,
                'shape' => ['value' => ['label' => 'Value', 'type' => 'text']],
            ],
        ],
        'Pillars' => [
            'pillars.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text'],
            'pillars.heading' => ['label' => 'Heading', 'type' => 'text'],
            'pillars.body'    => ['label' => 'Body',    'type' => 'textarea'],
            'pillars.items'   => [
                'label' => 'Pillar cards',
                'type'  => 'list',
                'count' => 6,
                'shape' => [
                    'title' => ['label' => 'Title',       'type' => 'text'],
                    'desc'  => ['label' => 'Description', 'type' => 'textarea'],
                ],
            ],
        ],
        'Sectors' => [
            'sectors.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text'],
            'sectors.heading' => ['label' => 'Heading', 'type' => 'text'],
            'sectors.body'    => ['label' => 'Body',    'type' => 'textarea'],
        ],
        'News block' => [
            'news.eyebrow'  => ['label' => 'Eyebrow',          'type' => 'text'],
            'news.heading'  => ['label' => 'Heading',          'type' => 'text'],
            'news.view_all' => ['label' => 'View-all label',   'type' => 'text'],
            'news.empty'    => ['label' => 'Empty-state text', 'type' => 'text'],
        ],
        'Call to action' => [
            'cta.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text'],
            'cta.heading' => ['label' => 'Heading', 'type' => 'text'],
            'cta.body'    => ['label' => 'Body',    'type' => 'textarea'],
            'cta.button'  => ['label' => 'Button',  'type' => 'text'],
        ],
    ];

    private const ABOUT_FIELDS = [
        'Hero' => [
            'hero.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text'],
            'hero.heading' => ['label' => 'Heading', 'type' => 'text'],
        ],
        'Implementation phases' => [
            'phases.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text'],
            'phases.heading' => ['label' => 'Heading', 'type' => 'text'],
            'phases.body'    => ['label' => 'Body',    'type' => 'textarea'],
            'phases.items'   => [
                'label' => 'Phase cards',
                'type'  => 'list',
                'count' => 3,
                'shape' => [
                    'label' => ['label' => 'Label (e.g. Phase 1 · 2026)', 'type' => 'text'],
                    'title' => ['label' => 'Title',                       'type' => 'text'],
                    'desc'  => ['label' => 'Description',                 'type' => 'textarea'],
                ],
            ],
        ],
    ];

    public function edit()
    {
        $settings = SiteSetting::query()->first() ?? new SiteSetting();

        return view('admin.settings.edit', [
            'settings'     => $settings,
            'homeSchema'   => self::HOME_FIELDS,
            'aboutSchema'  => self::ABOUT_FIELDS,
        ]);
    }

    public function update(Request $request)
    {
        // Strip empty rows from the dynamic email/phone editors so a blank
        // "added" row doesn't trip the per-element 'required' rule.
        $rawSocials = (array) $request->input('social_links', []);
        $cleanSocials = [];
        foreach (array_keys(SiteSetting::SOCIAL_PLATFORMS) as $platform) {
            $value = $rawSocials[$platform] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $cleanSocials[$platform] = trim($value);
            }
        }

        $request->merge([
            'contact_emails' => array_values(array_filter(
                (array) $request->input('contact_emails', []),
                fn ($v) => is_string($v) && trim($v) !== ''
            )),
            'contact_phones' => array_values(array_filter(
                (array) $request->input('contact_phones', []),
                fn ($v) => is_string($v) && trim($v) !== ''
            )),
            'social_links' => $cleanSocials,
        ]);

        $data = $request->validate([
            'contact_emails'   => ['required', 'array', 'min:1'],
            'contact_emails.*' => ['required', 'email', 'max:255'],
            'contact_phones'   => ['required', 'array', 'min:1'],
            'contact_phones.*' => ['required', 'string', 'max:64'],
            'address_en' => ['required', 'string', 'max:2000'],
            'address_ar' => ['required', 'string', 'max:2000'],
            'social_links'           => ['array'],
            'social_links.linkedin'  => ['nullable', 'url', 'max:255'],
            'social_links.x'         => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.facebook'  => ['nullable', 'url', 'max:255'],
            'footer_desc_en' => ['nullable', 'string', 'max:2000'],
            'footer_desc_ar' => ['nullable', 'string', 'max:2000'],
        ], [
            'social_links.linkedin.url'  => 'Please enter a full LinkedIn URL, e.g. https://www.linkedin.com/company/example.',
            'social_links.x.url'         => 'Please enter a full X URL, e.g. https://x.com/example.',
            'social_links.instagram.url' => 'Please enter a full Instagram URL, e.g. https://www.instagram.com/example.',
            'social_links.facebook.url'  => 'Please enter a full Facebook URL, e.g. https://www.facebook.com/example.',
            'contact_emails.required'    => 'Add at least one contact email.',
            'contact_emails.*.email'     => 'One of the contact emails is not a valid email address.',
            'contact_phones.required'    => 'Add at least one contact phone.',
        ]);

        // Mirror the first element back to the singular columns so older
        // readers that still touch contact_email / contact_phone keep working.
        $data['contact_email'] = $data['contact_emails'][0] ?? null;
        $data['contact_phone'] = $data['contact_phones'][0] ?? null;

        // Always persist the cleaned social_links — when every field is empty
        // Laravel's validator drops the key from $data, which would leave the
        // previous values in place.
        $data['social_links'] = $cleanSocials;

        $this->saveSettings($data);

        return redirect()->route('admin.settings.edit')
            ->with('status', __('admin.settings_updated'))
            ->with('open_tab', 'contact');
    }

    public function updateHome(Request $request)
    {
        $payload = $this->validateContent($request, self::HOME_FIELDS);

        $settings = SiteSetting::query()->first();
        $merged = $settings?->home_content ?? [];
        $merged['en'] = $payload['en'];
        $merged['ar'] = $payload['ar'];

        $this->saveSettings(['home_content' => $merged]);

        return redirect()->route('admin.settings.edit')
            ->with('status', __('admin.settings_updated'))
            ->with('open_tab', 'home');
    }

    public function updateAbout(Request $request)
    {
        $payload = $this->validateContent($request, self::ABOUT_FIELDS);

        $settings = SiteSetting::query()->first();
        $merged = $settings?->about_content ?? [];
        $merged['en'] = $payload['en'];
        $merged['ar'] = $payload['ar'];

        $this->saveSettings(['about_content' => $merged]);

        return redirect()->route('admin.settings.edit')
            ->with('status', __('admin.settings_updated'))
            ->with('open_tab', 'about');
    }

    public function updateHeroImage(Request $request)
    {
        $request->validate([
            'hero_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $path = $request->file('hero_image')->store('site', 'public');

        $settings = SiteSetting::query()->first();
        if ($settings?->hero_image_path) {
            Storage::disk('public')->delete($settings->hero_image_path);
        }

        $this->saveSettings(['hero_image_path' => $path]);

        return redirect()->route('admin.settings.edit')
            ->with('status', __('admin.settings_updated'))
            ->with('open_tab', 'home');
    }

    public function deleteHeroImage()
    {
        $settings = SiteSetting::query()->first();
        if ($settings?->hero_image_path) {
            Storage::disk('public')->delete($settings->hero_image_path);
            $settings->update(['hero_image_path' => null]);
        }

        return redirect()->route('admin.settings.edit')
            ->with('status', __('admin.settings_updated'))
            ->with('open_tab', 'home');
    }

    private function saveSettings(array $data): void
    {
        $settings = SiteSetting::query()->first();
        if ($settings) {
            $settings->update($data);
        } else {
            $defaults = [
                'contact_email' => 'info@ssbc.org',
                'contact_phone' => '',
                'address_en' => '',
                'address_ar' => '',
            ];
            SiteSetting::create(array_merge($defaults, $data));
        }
    }

    /**
     * Validate a content POST against a schema. Returns ['en' => [...], 'ar' => [...]].
     *
     * Field names use literal dots (e.g. `en[hero.headline]`), so Laravel's
     * dot-notation rule paths don't apply. We validate the top-level arrays
     * exist and then sanitize each value per schema-declared shape.
     */
    private function validateContent(Request $request, array $schema): array
    {
        $request->validate([
            'en' => ['array'],
            'ar' => ['array'],
        ]);

        $clean = ['en' => [], 'ar' => []];

        foreach (['en', 'ar'] as $locale) {
            $input = (array) $request->input($locale, []);

            foreach ($schema as $section) {
                foreach ($section as $key => $meta) {
                    $value = $this->extractField($input[$key] ?? null, $meta);
                    if ($value !== null) {
                        $clean[$locale][$key] = $value;
                    }
                }
            }
        }

        return $clean;
    }

    /**
     * Pull a field's value out of raw POST input according to its schema entry.
     * Returns null when nothing meaningful was submitted (so the JSON column
     * stays clean and the lang-file default keeps winning).
     */
    private function extractField($raw, array $meta)
    {
        $type = $meta['type'] ?? 'text';

        if ($type === 'list') {
            return $this->extractList($raw, $meta);
        }

        if (! is_string($raw)) {
            return null;
        }

        $maxLen = $type === 'textarea' ? 5000 : 500;
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, $maxLen);
    }

    /**
     * A "list" submission looks like `[0 => ['title' => 'x', 'desc' => 'y'], 1 => ...]`.
     * For a single-key shape (e.g. `value`) we collapse to a flat array of strings
     * so the public blade can `@foreach` without changes.
     */
    private function extractList($raw, array $meta): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $shape = $meta['shape'] ?? [];
        $rows  = [];
        $isFlat = count($shape) === 1;
        $onlyShapeKey = $isFlat ? array_key_first($shape) : null;

        for ($i = 0; $i < ($meta['count'] ?? 0); $i++) {
            $row = $raw[$i] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $cleanRow = [];
            foreach ($shape as $field => $fieldMeta) {
                $value = $this->extractField($row[$field] ?? null, $fieldMeta);
                if ($value !== null) {
                    $cleanRow[$field] = $value;
                }
            }

            if ($cleanRow === []) {
                continue;
            }

            $rows[] = $isFlat ? $cleanRow[$onlyShapeKey] : $cleanRow;
        }

        return $rows === [] ? null : $rows;
    }
}
