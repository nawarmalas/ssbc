<?php

use App\Models\FormField;
use App\Services\FormService;
use Illuminate\Database\Migrations\Migration;

/**
 * Fix: field ID 5 in production has field_type='number' but should be 'tel'
 * (Mobile Number with Country Code). The server-side rulesFor() correctly maps
 * 'number' → 'integer' and 'tel' → regex, so the wrong field_type causes the
 * reported validation error "answers.5.0 field must be an integer".
 *
 * We correct the field by label (as the other seeders/migrations do) and also
 * guard by checking the current field_type so the migration is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $field = FormField::where('label_en', 'Mobile Number with Country Code')
            ->whereHas('section', fn ($q) => $q->where('form_id', 'join-us'))
            ->first();

        if (! $field) {
            return;
        }

        if ($field->field_type !== 'tel') {
            $field->forceFill(['field_type' => 'tel'])->saveQuietly();
        }

        FormService::invalidateCache('join-us');
    }

    public function down(): void
    {
        // Intentionally no-op: reverting to 'number' would re-introduce the bug.
    }
};
