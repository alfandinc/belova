<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya konsultasi for several spesialisasi at once: one jenis konsultasi -> many spesialisasi (pivot), no rows =
 * every spesialisasi. Replaces the single erm_konsultasi.spesialisasi_id (2026_10_31_000003), whose values move here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_konsultasi_spesialisasi')) {
            Schema::create('erm_konsultasi_spesialisasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('konsultasi_id');
                $table->unsignedBigInteger('spesialisasi_id');
                $table->timestamps();
                $table->unique(['konsultasi_id', 'spesialisasi_id'], 'konsultasi_spesialisasi_unique');
                $table->index('spesialisasi_id');
            });
        }

        if (Schema::hasColumn('erm_konsultasi', 'spesialisasi_id')) {
            $now = now();
            $rows = DB::table('erm_konsultasi')->whereNotNull('spesialisasi_id')->get(['id', 'spesialisasi_id'])
                ->map(fn ($r) => ['konsultasi_id' => $r->id, 'spesialisasi_id' => $r->spesialisasi_id, 'created_at' => $now, 'updated_at' => $now])
                ->all();
            if ($rows) {
                DB::table('erm_konsultasi_spesialisasi')->insertOrIgnore($rows);
            }

            Schema::table('erm_konsultasi', function (Blueprint $table) {
                $table->dropIndex(['spesialisasi_id']);
                $table->dropColumn('spesialisasi_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('erm_konsultasi', 'spesialisasi_id')) {
            Schema::table('erm_konsultasi', function (Blueprint $table) {
                $table->unsignedBigInteger('spesialisasi_id')->nullable()->after('kategori')->index();
            });
        }
        // Only a single spesialisasi fits back: keep the first of each jenis
        if (Schema::hasTable('erm_konsultasi_spesialisasi')) {
            foreach (DB::table('erm_konsultasi_spesialisasi')->orderBy('id')->get()->groupBy('konsultasi_id') as $konsultasiId => $rows) {
                DB::table('erm_konsultasi')->where('id', $konsultasiId)->update(['spesialisasi_id' => $rows->first()->spesialisasi_id]);
            }
            Schema::dropIfExists('erm_konsultasi_spesialisasi');
        }
    }
};
