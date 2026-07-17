<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_mutasi_stok', function (Blueprint $table) {
            $table->date('tanggal_mutasi')->nullable()->after('jenis_mutasi');
            $table->timestamp('tanggal_input')->nullable()->after('tanggal_mutasi');
        });

        DB::table('erm_mutasi_stok')
            ->whereNull('tanggal_mutasi')
            ->update([
                'tanggal_mutasi' => DB::raw('DATE(created_at)'),
                'tanggal_input' => DB::raw('created_at'),
            ]);

        DB::statement('ALTER TABLE erm_mutasi_stok MODIFY tanggal_mutasi DATE NOT NULL');
        DB::statement('ALTER TABLE erm_mutasi_stok MODIFY tanggal_input TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        Schema::table('erm_mutasi_stok', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mutasi', 'tanggal_input']);
        });
    }
};