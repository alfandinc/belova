<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A jenis konsultasi without spesialisasi is now offered nowhere (it used to mean "all spesialisasi"). So that the
 * dokter keeps the same choices after deploying, every konsultasi without a mapping gets all spesialisasi; Marketing
 * then narrows them down in Biaya Konsultasi. Only rows the dokter can pick (name contains "konsultasi").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_konsultasi_spesialisasi')) {
            return;
        }

        $spesialisasiIds = DB::table('erm_spesialisasis')->pluck('id');
        $unmapped = DB::table('erm_konsultasi')
            ->whereRaw('LOWER(nama) LIKE ?', ['%konsultasi%'])
            ->whereNotExists(fn ($q) => $q->from('erm_konsultasi_spesialisasi as ks')->whereColumn('ks.konsultasi_id', 'erm_konsultasi.id'))
            ->pluck('id');

        $now = now();
        $rows = [];
        foreach ($unmapped as $konsultasiId) {
            foreach ($spesialisasiIds as $spesialisasiId) {
                $rows[] = ['konsultasi_id' => $konsultasiId, 'spesialisasi_id' => $spesialisasiId, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('erm_konsultasi_spesialisasi')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        // Data only: which mapping came from here can't be told apart from mapping made later in the master
    }
};
