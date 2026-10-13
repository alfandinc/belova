<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spesialisasi a dokter practises as in a klinik (one or more). No rows for a dokter + klinik
     * means the dokter's default spesialisasi. Replaces erm_dokter_kliniks.spesialisasi_id (one only).
     */
    public function up(): void
    {
        if (!Schema::hasTable('erm_dokter_klinik_spesialisasis')) {
            Schema::create('erm_dokter_klinik_spesialisasis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dokter_id')->constrained('erm_dokters')->cascadeOnDelete();
                $table->foreignId('klinik_id')->constrained('erm_klinik')->cascadeOnDelete();
                $table->foreignId('spesialisasi_id')->constrained('erm_spesialisasis')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['dokter_id', 'klinik_id', 'spesialisasi_id'], 'erm_dks_unique');
            });
        }

        if (Schema::hasColumn('erm_dokter_kliniks', 'spesialisasi_id')) {
            $rows = DB::table('erm_dokter_kliniks')
                ->whereNotNull('spesialisasi_id')
                ->get(['dokter_id', 'klinik_id', 'spesialisasi_id'])
                ->map(fn ($row) => [
                    'dokter_id' => $row->dokter_id,
                    'klinik_id' => $row->klinik_id,
                    'spesialisasi_id' => $row->spesialisasi_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            if (!empty($rows)) {
                DB::table('erm_dokter_klinik_spesialisasis')->insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_dokter_klinik_spesialisasis');
    }
};
