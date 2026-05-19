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
            $table->json('permissions')->nullable()->after('role');
        });

        // Migrate every news_subadmin user → subadmin + ['news_write'].
        // Preserves their existing drafts-only access exactly.
        DB::table('users')->where('role', 'news_subadmin')->update([
            'role'        => 'subadmin',
            'permissions' => json_encode(['news_write']),
        ]);

        // Any other unexpected non-admin role becomes a permission-less
        // subadmin (effectively no access) rather than silently keeping
        // the now-removed role string.
        DB::table('users')
            ->whereNotIn('role', ['admin', 'subadmin'])
            ->update(['role' => 'subadmin', 'permissions' => json_encode([])]);
    }

    public function down(): void
    {
        // Restore the old role string for users who had news_write.
        DB::table('users')->where('role', 'subadmin')->get()->each(function ($row) {
            $perms = json_decode($row->permissions ?? '[]', true) ?: [];
            DB::table('users')->where('id', $row->id)->update([
                'role' => in_array('news_write', $perms, true) ? 'news_subadmin' : 'admin',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
