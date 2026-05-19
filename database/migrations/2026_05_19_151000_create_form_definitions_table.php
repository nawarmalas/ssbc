<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('form_id', 64)->unique();
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private')->index();
            $table->string('access_token', 80)->nullable()->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        DB::table('form_definitions')->insert([
            'form_id' => 'join-us',
            'slug' => 'join-us',
            'title_en' => 'Join Us',
            'title_ar' => 'انضم إلينا',
            'description_en' => null,
            'description_ar' => null,
            'visibility' => 'public',
            'access_token' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('form_definitions');
    }
};
