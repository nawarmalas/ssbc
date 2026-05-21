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

    /**
     * Active sectors shaped as form-field options ([value, label_en, label_ar]).
     * Shared by the form builder and SectorObserver so sectors-backed fields
     * stay in a single, consistent shape.
     */
    public static function activeFieldOptions(): array
    {
        return static::active()->get()
            ->map(fn (Sector $s) => [
                'value'    => $s->slug,
                'label_en' => $s->name_en,
                'label_ar' => $s->name_ar,
            ])->values()->all();
    }
}
