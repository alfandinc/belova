<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spesialisasi chosen at pendaftaran (a dokter can hold several in one klinik).
     * Existing visits are filled from the dokter + klinik as resolved before this change.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('erm_visitations', 'spesialisasi_id')) {
            Schema::table('erm_visitations', function (Blueprint $table) {
                $table->foreignId('spesialisasi_id')
                    ->nullable()
                    ->after('dokter_id')
                    ->constrained('erm_spesialisasis')
                    ->nullOnDelete();
            });
        }

        // Freeze the spesialisasi of existing visits, so adding a spesialisasi to a dokter later
        // does not change the asesmen / CPPT form of past visits. Only the new column is written
        // (raw SQL: updated_at untouched); a spesialisasi missing from the master is skipped (stays null).
        // Runs again on a re-run, so a failure after the column was added does not skip it.
        DB::statement('
            UPDATE erm_visitations v
            JOIN erm_dokters d ON d.id = v.dokter_id
            JOIN erm_spesialisasis s ON s.id = COALESCE(
                (SELECT MIN(dks.spesialisasi_id) FROM erm_dokter_klinik_spesialisasis dks
                 WHERE dks.dokter_id = v.dokter_id AND dks.klinik_id = v.klinik_id),
                d.spesialisasi_id
            )
            SET v.spesialisasi_id = s.id
            WHERE v.spesialisasi_id IS NULL
        ');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('erm_visitations', 'spesialisasi_id')) {
            return;
        }

        Schema::table('erm_visitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spesialisasi_id');
        });
    }
};
