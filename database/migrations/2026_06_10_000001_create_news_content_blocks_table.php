<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->enum('locale', ['en', 'ar']);
            $table->enum('type', ['text', 'image']);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->longText('content')->nullable();
            $table->string('image_path')->nullable();
            $table->string('caption_en')->nullable();
            $table->string('caption_ar')->nullable();
            $table->timestamps();

            $table->index(['news_post_id', 'locale', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_content_blocks');
    }
};
