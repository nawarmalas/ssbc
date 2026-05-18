<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FormUpload extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'submission_id', 'field_id', 'repeat_index',
        'file_path', 'file_name', 'file_size',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }
}
