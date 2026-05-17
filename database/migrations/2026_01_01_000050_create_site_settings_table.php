<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->text('address_en');
            $table->text('address_ar');
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->text('footer_desc_en')->nullable();
            $table->text('footer_desc_ar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
