<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD = ['pending', 'under_review', 'approved', 'rejected'];
    private const NEW = ['pending', 'under_review', 'consultant', 'waiting_payment', 'approved', 'rejected'];

    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->enum('status', self::NEW)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Fold the removed statuses back into under_review so the narrower enum accepts every row.
        DB::table('form_submissions')
            ->whereIn('status', ['consultant', 'waiting_payment'])
            ->update(['status' => 'under_review']);

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->enum('status', self::OLD)->default('pending')->change();
        });
    }
};
