<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NewsPostImage extends Model
{
    protected $fillable = ['news_post_id', 'path', 'sort_order'];

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
