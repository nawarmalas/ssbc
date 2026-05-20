<?php

use App\Models\FormField;
use App\Services\FormService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $countryField = FormField::where('code', 'current_operations_country')
            ->whereHas('section', fn ($query) => $query->where('form_id', 'join-us'))
            ->first();

        if (! $countryField) {
            return;
        }

        $countryOrder = (int) $countryField->order_index;
        $sectionId = $countryField->section_id;

        FormField::where('section_id', $sectionId)
            ->where('id', '!=', $countryField->id)
            ->where('order_index', '>', $countryOrder)
            ->orderBy('order_index', 'desc')
            ->get()
            ->each(function (FormField $field) {
                $field->forceFill(['order_index' => $field->order_index + 1])->saveQuietly();
            });

        FormField::where('code', 'country_other_specify')
            ->where('section_id', $sectionId)
            ->update(['order_index' => $countryOrder + 1]);

        FormService::invalidateCache('join-us');
    }

    public function down(): void
    {
        // No-op: this normalizes field order for existing production data.
    }
};
