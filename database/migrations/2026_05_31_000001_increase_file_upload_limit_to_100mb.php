<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bump file fields still on the previous 10 MB default up to 100 MB.
        // Custom values set by admins (anything other than 10) are left untouched.
        DB::table('form_fields')
            ->whereNotNull('file_config')
            ->get()
            ->each(function ($row) {
                $config = json_decode($row->file_config, true);
                if (isset($config['max_size_mb']) && $config['max_size_mb'] == 10) {
                    $config['max_size_mb'] = 100;
                    DB::table('form_fields')
                        ->where('id', $row->id)
                        ->update(['file_config' => json_encode($config)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('form_fields')
            ->whereNotNull('file_config')
            ->get()
            ->each(function ($row) {
                $config = json_decode($row->file_config, true);
                if (isset($config['max_size_mb']) && $config['max_size_mb'] == 100) {
                    $config['max_size_mb'] = 10;
                    DB::table('form_fields')
                        ->where('id', $row->id)
                        ->update(['file_config' => json_encode($config)]);
                }
            });
    }
};
