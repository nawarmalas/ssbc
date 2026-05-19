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
            $table->json('contact_emails')->nullable()->after('contact_email');
            $table->json('contact_phones')->nullable()->after('contact_phone');
        });

        // Backfill existing single email / phone into the JSON arrays so the
        // admin form opens pre-filled and the public footer keeps rendering.
        foreach (DB::table('site_settings')->get() as $row) {
            DB::table('site_settings')->where('id', $row->id)->update([
                'contact_emails' => json_encode($row->contact_email ? [$row->contact_email] : []),
                'contact_phones' => json_encode($row->contact_phone ? [$row->contact_phone] : []),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_emails', 'contact_phones']);
        });
    }
};
