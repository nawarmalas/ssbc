<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('social_links')->nullable()->after('twitter_url');
        });

        foreach (DB::table('site_settings')->get() as $row) {
            $links = array_filter([
                'linkedin'  => $row->linkedin_url ?: null,
                'x'         => $row->twitter_url ?: null,
                'instagram' => null,
                'facebook'  => null,
            ]);

            DB::table('site_settings')->where('id', $row->id)->update([
                'social_links' => json_encode($links, JSON_UNESCAPED_SLASHES),
            ]);
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['linkedin_url', 'twitter_url']);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
        });

        foreach (DB::table('site_settings')->get() as $row) {
            $links = json_decode($row->social_links ?? '{}', true) ?: [];
            DB::table('site_settings')->where('id', $row->id)->update([
                'linkedin_url' => $links['linkedin'] ?? null,
                'twitter_url'  => $links['x'] ?? null,
            ]);
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });
    }
};
