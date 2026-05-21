<?php

use App\Models\FormField;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Sectors is now a reusable option source (options_source = 'sectors').
        // The original "Sectors of Operation" field no longer needs to be a
        // locked, undeletable system field — it becomes an ordinary
        // sectors-backed field, consistent with any others the admin adds.
        FormField::where('code', 'sectors_of_operation')
            ->update(['is_system_managed' => false]);
    }

    public function down(): void
    {
        FormField::where('code', 'sectors_of_operation')
            ->update(['is_system_managed' => true]);
    }
};
