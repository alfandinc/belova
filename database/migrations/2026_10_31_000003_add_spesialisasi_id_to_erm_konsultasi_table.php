<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya konsultasi per spesialisasi: each jenis konsultasi belongs to one spesialisasi (NULL = all spesialisasi).
 * It replaced matching the free-text kategori against the spesialisasi name, so existing rows whose kategori names
 * a spesialisasi (e.g. "Konsultasi Penyakit Dalam") get that spesialisasi; the others stay "Semua".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('erm_konsultasi', 'spesialisasi_id')) {
            Schema::table('erm_konsultasi', function (Blueprint $table) {
                $table->unsignedBigInteger('spesialisasi_id')->nullable()->after('kategori')->index();
            });
        }

        // Longest names first, so "Penyakit Dalam" wins over a shorter name contained in it
        $spesialisasis = DB::table('erm_spesialisasis')->get(['id', 'nama'])
            ->sortByDesc(fn ($s) => mb_strlen($s->nama));
        foreach (DB::table('erm_konsultasi')->whereNull('spesialisasi_id')->whereNotNull('kategori')->get(['id', 'kategori']) as $row) {
            $kategori = mb_strtolower($row->kategori);
            $match = $spesialisasis->first(fn ($s) => $s->nama !== '' && str_contains($kategori, mb_strtolower($s->nama)));
            if ($match) {
                DB::table('erm_konsultasi')->where('id', $row->id)->update(['spesialisasi_id' => $match->id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_konsultasi', 'spesialisasi_id')) {
            Schema::table('erm_konsultasi', function (Blueprint $table) {
                $table->dropIndex(['spesialisasi_id']);
                $table->dropColumn('spesialisasi_id');
            });
        }
    }
};
