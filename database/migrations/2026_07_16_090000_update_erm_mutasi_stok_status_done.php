<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_mutasi_stok')) {
            return;
        }

        DB::table('erm_mutasi_stok')
            ->where('status', 'posted')
            ->update(['status' => 'done']);

        DB::statement("ALTER TABLE erm_mutasi_stok MODIFY status VARCHAR(255) NOT NULL DEFAULT 'done'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('erm_mutasi_stok')) {
            return;
        }

        DB::table('erm_mutasi_stok')
            ->where('status', 'done')
            ->update(['status' => 'posted']);

        DB::statement("ALTER TABLE erm_mutasi_stok MODIFY status VARCHAR(255) NOT NULL DEFAULT 'posted'");
    }
};