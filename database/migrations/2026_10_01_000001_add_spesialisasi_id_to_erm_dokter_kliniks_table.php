<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-klinik spesialisasi for a dokter. Null means fall back to erm_dokters.spesialisasi_id.
     */
    public function up(): void
    {
        Schema::table('erm_dokter_kliniks', function (Blueprint $table) {
            $table->foreignId('spesialisasi_id')
                ->nullable()
                ->after('klinik_id')
                ->constrained('erm_spesialisasis')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_dokter_kliniks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spesialisasi_id');
        });
    }
};
