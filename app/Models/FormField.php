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
