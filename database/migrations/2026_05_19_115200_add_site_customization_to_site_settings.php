<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('home_content')->nullable()->after('footer_desc_ar');
            $table->json('about_content')->nullable()->after('home_content');
            $table->string('hero_image_path', 500)->nullable()->after('about_content');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['home_content', 'about_content', 'hero_image_path']);
        });
    }
};
