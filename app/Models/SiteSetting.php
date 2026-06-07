<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    public const SOCIAL_PLATFORMS = [
        'linkedin'  => 'LinkedIn',
        'x'         => 'X (Twitter)',
        'instagram' => 'Instagram',
        'facebook'  => 'Facebook',
    ];

    protected $fillable = [
        'contact_email',
        'contact_phone',
        'contact_emails',
        'contact_phones',
        'address_en',
        'address_ar',
        'social_links',
        'footer_desc_en',
        'footer_desc_ar',
        'home_content',
        'about_content',
        'hero_image_path',
    ];

    protected function casts(): array
    {
        return [
            'home_content'   => 'array',
            'about_content'  => 'array',
            'contact_emails' => 'array',
            'contact_phones' => 'array',
            'social_links'   => 'array',
        ];
    }

    /** @var self|null In-process singleton so View::composer('*') only hits the DB once per request. */
    private static ?self $instance = null;

    public static function current(): self
    {
        if (static::$instance === null) {
            static::$instance = static::query()->first() ?? new self([
                'contact_email' => 'info@ssbc.org',
                'contact_phone' => '',
                'address_en' => '',
                'address_ar' => '',
            ]);
        }

        return static::$instance;
    }

    /** Call after saving settings so the cached instance is refreshed on the next access. */
    public static function forgetCurrent(): void
    {
        static::$instance = null;
    }

    public function address(string $locale): string
    {
        return $locale === 'ar' ? ($this->address_ar ?: $this->address_en) : $this->address_en;
    }

    /**
     * All contact emails. Falls back to the singular `contact_email` column so
     * a fresh row before migration backfill still produces a one-element list.
     */
    public function emails(): array
    {
        $list = is_array($this->contact_emails) ? $this->contact_emails : [];
        $list = array_values(array_filter($list, fn ($v) => is_string($v) && trim($v) !== ''));

        if ($list !== []) {
            return $list;
        }

        return $this->contact_email ? [$this->contact_email] : [];
    }

    public function phones(): array
    {
        $list = is_array($this->contact_phones) ? $this->contact_phones : [];
        $list = array_values(array_filter($list, fn ($v) => is_string($v) && trim($v) !== ''));

        if ($list !== []) {
            return $list;
        }

        return $this->contact_phone ? [$this->contact_phone] : [];
    }

    /**
     * Filled social links in canonical platform order. Each entry has
     * `key`, `label`, and `url`. Empty platforms are omitted.
     */
    public function socials(): array
    {
        $links = is_array($this->social_links) ? $this->social_links : [];

        $out = [];
        foreach (self::SOCIAL_PLATFORMS as $key => $label) {
            $url = trim((string) ($links[$key] ?? ''));
            if ($url === '') {
                continue;
            }
            $out[] = [
                'key'   => $key,
                'label' => $label,
                'url'   => $url,
            ];
        }

        return $out;
    }

    public function footerDesc(string $locale): ?string
    {
        return $locale === 'ar' ? ($this->footer_desc_ar ?: $this->footer_desc_en) : $this->footer_desc_en;
    }

    /**
     * Read a homepage copy field for the given locale, falling back to the
     * provided default (typically `__('home.xxx')`).
     */
    public function homeContent(string $locale, string $key, ?string $default = null): ?string
    {
        return $this->readContent($this->home_content, $locale, $key, $default);
    }

    /**
     * Read an about-page copy field for the given locale, falling back to the
     * provided default (typically `__('about.xxx')`).
     */
    public function aboutContent(string $locale, string $key, ?string $default = null): ?string
    {
        return $this->readContent($this->about_content, $locale, $key, $default);
    }

    public function heroImageUrl(): ?string
    {
        if (! $this->hero_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->hero_image_path);
    }

    /**
     * Read a homepage list field for the given locale. Returns the saved
     * array if non-empty, otherwise the provided default array.
     */
    public function homeList(string $locale, string $key, array $default = []): array
    {
        return $this->readList($this->home_content, $locale, $key, $default);
    }

    public function aboutList(string $locale, string $key, array $default = []): array
    {
        return $this->readList($this->about_content, $locale, $key, $default);
    }

    private function readContent(?array $bag, string $locale, string $key, ?string $default): ?string
    {
        $value = $bag[$locale][$key] ?? null;

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return $default;
    }

    /**
     * Merge a saved list over the lang-file defaults per row. The result always
     * has `count($default)` rows so the public view's grid stays full even when
     * the admin has only customized one row.
     *
     * - Saved value at index N replaces the default at index N.
     * - Empty strings or missing keys fall back to the default row.
     * - If nothing is saved, the full default array is returned.
     */
    private function readList(?array $bag, string $locale, string $key, array $default): array
    {
        $value = $bag[$locale][$key] ?? null;

        if (! is_array($value) || count($value) === 0) {
            return $default;
        }

        if ($default === []) {
            return $value;
        }

        $merged = [];
        foreach ($default as $i => $def) {
            $saved = $value[$i] ?? null;

            if (is_array($def) && is_array($saved)) {
                $row = $def;
                foreach ($saved as $k => $v) {
                    if (is_string($v) && trim($v) !== '') {
                        $row[$k] = $v;
                    }
                }
                $merged[] = $row;
            } elseif (is_string($saved) && trim($saved) !== '') {
                $merged[] = $saved;
            } else {
                $merged[] = $def;
            }
        }

        return $merged;
    }
}
