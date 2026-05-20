<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->after('section_id');
            $table->json('conditional_logic')->nullable()->after('file_config');
            $table->boolean('is_system_managed')->default(false)->after('is_active');
            $table->unique(['section_id', 'code'], 'form_fields_section_id_code_unique');
        });

        Schema::table('sectors', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->unique()->after('id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropUnique('form_fields_section_id_code_unique');
            $table->dropColumn(['code', 'conditional_logic', 'is_system_managed']);
        });

        Schema::table('sectors', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('slug');
        });
    }
};
