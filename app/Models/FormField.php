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
        'options', 'options_source', 'validation_rules', 'conditional_logic', 'file_config',
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
        $mb = $this->file_config['max_size_mb'] ?? 100;
        return $mb * 1024;
    }

    /**
     * Render a stored answer for display.
     * - checkbox_group: decode JSON, map values to option labels, join with ", "
     * - select/radio: map single value to option label
     * - declaration: "Accepted" / "—"
     * - sectors-backed fields: fallback to Sector model (incl. trashed) when label not found
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
            if ($this->options_source === 'sectors') {
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
