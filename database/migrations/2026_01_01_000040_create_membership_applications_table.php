<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();
            $table->string('full_name_en');
            $table->string('full_name_ar');
            $table->date('date_of_birth');
            $table->string('position');
            $table->string('mobile');
            $table->string('email');
            $table->text('home_address')->nullable();
            $table->string('linked_in')->nullable();
            $table->json('companies');
            $table->string('id_document_path');
            $table->json('company_document_paths');
            $table->string('company_profile_url')->nullable();
            $table->enum('status', ['new', 'reviewed', 'contacted', 'approved', 'rejected'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
