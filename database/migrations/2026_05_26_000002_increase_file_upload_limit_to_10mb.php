<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update all file fields that have max_size_mb = 5
        DB::table('form_fields')
            ->whereNotNull('file_config')
            ->get()
            ->each(function ($row) {
                $config = json_decode($row->file_config, true);
                if (isset($config['max_size_mb']) && $config['max_size_mb'] == 5) {
                    $config['max_size_mb'] = 10;
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
                if (isset($config['max_size_mb']) && $config['max_size_mb'] == 10) {
                    $config['max_size_mb'] = 5;
                    DB::table('form_fields')
                        ->where('id', $row->id)
                        ->update(['file_config' => json_encode($config)]);
                }
            });
    }
};
