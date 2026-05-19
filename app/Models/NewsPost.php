<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    protected $fillable = [
        'slug',
        'title_en',
        'title_ar',
        'excerpt_en',
        'excerpt_ar',
        'content_en',
        'content_ar',
        'featured_image',
        'category',
        'status',
        'published_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function title(string $locale): string
    {
        return $locale === 'ar' ? ($this->title_ar ?: $this->title_en) : $this->title_en;
    }

    public function excerpt(string $locale): ?string
    {
        return $locale === 'ar' ? ($this->excerpt_ar ?: $this->excerpt_en) : $this->excerpt_en;
    }

    public function content(string $locale): ?string
    {
        return $locale === 'ar' ? ($this->content_ar ?: $this->content_en) : $this->content_en;
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image ? Storage::disk('public')->url($this->featured_image) : null;
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
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
