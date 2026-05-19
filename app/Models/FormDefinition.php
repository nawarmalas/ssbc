<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FormDefinition extends Model
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    protected $fillable = [
        'form_id',
        'slug',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'visibility',
        'access_token',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class, 'form_id', 'form_id')
            ->orderBy('order_index');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id', 'form_id');
    }

    public function isPrivate(): bool
    {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }

    public function publicUrl(string $locale = 'en'): ?string
    {
        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return route('join.create', ['locale' => $locale]);
        }

        if (! $this->access_token) {
            return null;
        }

        return route('private-forms.show', [
            'locale' => $locale,
            'form' => $this->slug,
            'token' => $this->access_token,
        ]);
    }

    public function title(string $locale = 'en'): string
    {
        return $locale === 'ar' ? ($this->title_ar ?: $this->title_en) : $this->title_en;
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'private-form';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
