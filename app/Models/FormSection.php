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
