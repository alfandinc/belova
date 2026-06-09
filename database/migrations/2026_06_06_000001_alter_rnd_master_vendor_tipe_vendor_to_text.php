<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rnd_master_vendor')) {
            return;
        }

        $rows = DB::table('rnd_master_vendor')->select('id', 'tipe_vendor')->get();

        DB::statement("ALTER TABLE rnd_master_vendor MODIFY tipe_vendor TEXT NOT NULL");

        foreach ($rows as $row) {
            $value = $row->tipe_vendor;
            $decoded = json_decode((string) $value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $normalized = array_values(array_filter($decoded, fn ($item) => filled($item)));
            } elseif (filled($value)) {
                $normalized = [$value];
            } else {
                $normalized = [];
            }

            DB::table('rnd_master_vendor')
                ->where('id', $row->id)
                ->update(['tipe_vendor' => json_encode($normalized)]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('rnd_master_vendor')) {
            return;
        }

        $rows = DB::table('rnd_master_vendor')->select('id', 'tipe_vendor')->get();

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->tipe_vendor, true);
            $fallback = is_array($decoded) && !empty($decoded) ? (string) $decoded[0] : 'produsen';

            DB::table('rnd_master_vendor')
                ->where('id', $row->id)
                ->update(['tipe_vendor' => $fallback]);
        }

        DB::statement("ALTER TABLE rnd_master_vendor MODIFY tipe_vendor ENUM('produsen','kemasan','desain') NOT NULL");
    }
};