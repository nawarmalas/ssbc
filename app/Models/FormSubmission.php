<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id', 'display_name', 'ip_address',
        'status', 'admin_notes', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormAnswer::class, 'submission_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(FormUpload::class, 'submission_id');
    }

    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_id', 'form_id');
    }

    public function answerFor(int $fieldId, int $repeatIndex = 0): ?string
    {
        return $this->answers
            ->where('field_id', $fieldId)
            ->where('repeat_index', $repeatIndex)
            ->value('answer_value');
    }

    public function uploadsFor(int $fieldId, int $repeatIndex = 0)
    {
        return $this->uploads
            ->where('field_id', $fieldId)
            ->where('repeat_index', $repeatIndex);
    }
}
