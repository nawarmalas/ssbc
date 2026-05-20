<?php
// app/Models/BoardMember.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BoardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en',
        'role_ar', 'role_en',
        'bio_ar',  'bio_en',
        'photo', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function name(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar'
            ? ($this->name_ar ?: $this->name_en)
            : ($this->name_en ?: $this->name_ar);
    }

    public function role(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar'
            ? ($this->role_ar ?: $this->role_en)
            : ($this->role_en ?: $this->role_ar);
    }

    public function bio(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar'
            ? ($this->bio_ar ?: $this->bio_en)
            : ($this->bio_en ?: $this->bio_ar);
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }
}
