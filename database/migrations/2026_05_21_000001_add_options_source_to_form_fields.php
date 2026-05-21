<?php

use App\Models\FormField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('options_source', 20)->default('manual')->after('options');
        });

        // The legacy "Sectors of Operation" field now opts in via options_source
        // so the observer can sync it through the same mechanism as new fields.
        FormField::where('code', 'sectors_of_operation')->update(['options_source' => 'sectors']);
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('options_source');
        });
    }
};
