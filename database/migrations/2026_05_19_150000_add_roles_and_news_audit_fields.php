<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('admin')->after('password')->index();
        });

        DB::table('users')->whereNull('role')->orWhere('role', '')->update(['role' => 'admin']);

        Schema::table('news_posts', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by_user_id');
            $table->dropConstrainedForeignId('created_by_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
