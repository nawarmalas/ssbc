<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Field labels double as declaration paragraphs, which can be long.
        // The columns were VARCHAR(255) while validation already allowed more,
        // so a long declaration passed validation but failed the DB insert
        // (500 error). Widen to TEXT to remove the mismatch.
        Schema::table('form_fields', function (Blueprint $table) {
            $table->text('label_en')->change();
            $table->text('label_ar')->change();
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('label_en')->change();
            $table->string('label_ar')->change();
        });
    }
};
