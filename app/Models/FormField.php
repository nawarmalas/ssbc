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

    /**
     * Render a stored answer for display.
     * - checkbox_group: decode JSON, map values to option labels, join with ", "
     * - select/radio: map single value to option label
     * - declaration: "Accepted" / "—"
     * - other types: trimmed string, or em-dash when empty
     */
    public function formatAnswer(?string $raw, string $locale = 'en'): string
    {
        if ($raw === null || $raw === '') {
            return $this->field_type === 'declaration' ? '—' : '—';
        }

        $optionLabel = function (string $value) use ($locale): string {
            foreach (($this->options ?? []) as $opt) {
                if (($opt['value'] ?? null) === $value) {
                    return $locale === 'ar'
                        ? ($opt['label_ar'] ?? $opt['label_en'] ?? $value)
                        : ($opt['label_en'] ?? $opt['label_ar'] ?? $value);
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
