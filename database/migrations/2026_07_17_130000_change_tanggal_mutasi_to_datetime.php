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

        DB::statement('ALTER TABLE erm_mutasi_stok MODIFY tanggal_mutasi DATETIME NULL');

        DB::statement("UPDATE erm_mutasi_stok SET tanggal_mutasi = CONCAT(DATE(tanggal_mutasi), ' ', TIME(COALESCE(tanggal_input, created_at))) WHERE tanggal_mutasi IS NOT NULL");

        DB::statement('ALTER TABLE erm_mutasi_stok MODIFY tanggal_mutasi DATETIME NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('erm_mutasi_stok')) {
            return;
        }

        DB::statement('ALTER TABLE erm_mutasi_stok MODIFY tanggal_mutasi DATE NULL');
        DB::statement('UPDATE erm_mutasi_stok SET tanggal_mutasi = DATE(tanggal_mutasi) WHERE tanggal_mutasi IS NOT NULL');
        DB::statement('ALTER TABLE erm_mutasi_stok MODIFY tanggal_mutasi DATE NOT NULL');
    }
};