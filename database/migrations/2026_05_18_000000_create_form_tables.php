<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->string('form_id', 64)->index();
            $table->string('title_en');
            $table->string('title_ar');
            $table->boolean('is_repeatable')->default(false);
            $table->unsignedTinyInteger('max_repeats')->default(5);
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('form_sections')->cascadeOnDelete();
            $table->string('label_en');
            $table->string('label_ar');
            $table->string('placeholder_en')->nullable();
            $table->string('placeholder_ar')->nullable();
            $table->enum('field_type', [
                'text', 'textarea', 'email', 'tel', 'number', 'date',
                'select', 'radio', 'checkbox_group', 'file', 'url', 'declaration',
            ]);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('file_config')->nullable();
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form_id', 64)->index();
            $table->string('display_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->unsignedBigInteger('field_id');
            $table->unsignedTinyInteger('repeat_index')->default(0);
            $table->text('answer_value')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['submission_id', 'field_id', 'repeat_index']);
        });

        Schema::create('form_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->unsignedBigInteger('field_id');
            $table->unsignedTinyInteger('repeat_index')->default(0);
            $table->string('file_path', 500);
            $table->string('file_name');
            $table->unsignedInteger('file_size');
            $table->timestamp('created_at')->useCurrent();

            $table->index('submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_uploads');
        Schema::dropIfExists('form_answers');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_sections');
    }
};
