<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('join_submissions');
        Schema::dropIfExists('membership_applications');
    }

    public function down(): void
    {
        // Legacy tables are intentionally not restorable.
        // The Form Builder + form_submissions tables superseded them.
    }
};
