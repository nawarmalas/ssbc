<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    /**
     * Workflow statuses in their meaningful review order. Used for the
     * status filter whitelist and for ORDER BY CASE expressions — keep in
     * sync with the enum in the form_submissions migration.
     */
    public const STATUSES = ['pending', 'under_review', 'approved', 'rejected'];

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

    /**
     * SQL CASE expression ranking statuses in workflow order
     * (pending → under_review → approved → rejected). Built only from the
     * STATUSES constant — no user input is ever interpolated.
     */
    public static function statusOrderSql(): string
    {
        $cases = '';
        foreach (self::STATUSES as $rank => $status) {
            $cases .= " WHEN '{$status}' THEN {$rank}";
        }

        return 'CASE status'.$cases.' END';
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
