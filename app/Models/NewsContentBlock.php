<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsContentBlock extends Model
{
    protected $fillable = [
        'news_post_id',
        'locale',
        'type',
        'sort_order',
        'content',
        'image_path',
        'caption_en',
        'caption_ar',
    ];

    public function newsPost(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NewsPost::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
