<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * R&D "Bahan Aktif" master is merged into the shared Zat Aktif master (erm_zataktif).
 *
 * Each bahan aktif is matched to an existing zat aktif with the same name (same rule as ZatAktif::compareKey:
 * letters and digits only, case-insensitive); unmatched names become new zat aktif (ZatAktif::cleanNama form).
 * Product links move to rnd_produk_zat_aktif, then the old master and pivot are dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_produk_zat_aktif', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('zataktif_id');
            $table->timestamps();

            $table->unique(['produk_id', 'zataktif_id'], 'rnd_produk_zat_aktif_unique');
            $table->foreign('produk_id', 'rnd_produk_zat_aktif_produk_fk')
                ->references('id')->on('rnd_produk')->cascadeOnDelete();
            // Restrict: a zat aktif used by an R&D product must not disappear silently
            $table->foreign('zataktif_id', 'rnd_produk_zat_aktif_zat_fk')
                ->references('id')->on('erm_zataktif')->restrictOnDelete();
        });

        if (Schema::hasTable('rnd_master_bahan_aktif')) {
            $now = now();
            $zatByKey = [];
            foreach (DB::table('erm_zataktif')->orderBy('id')->get(['id', 'nama']) as $zat) {
                $zatByKey[$this->compareKey((string) $zat->nama)] ??= $zat->id;
            }

            // bahan_aktif id => zataktif id
            $map = [];
            foreach (DB::table('rnd_master_bahan_aktif')->orderBy('id')->get() as $bahan) {
                $nama = $this->cleanNama((string) $bahan->nama_bahan_aktif);
                $key = $this->compareKey($nama);

                if ($key !== '' && isset($zatByKey[$key])) {
                    $map[$bahan->id] = $zatByKey[$key];
                    continue;
                }

                $zatId = DB::table('erm_zataktif')->insertGetId([
                    'nama' => $nama,
                    'created_at' => $bahan->created_at ?? $now,
                    'updated_at' => $now,
                ]);
                $map[$bahan->id] = $zatId;
                if ($key !== '') {
                    $zatByKey[$key] = $zatId;
                }
            }

            $rows = [];
            if (Schema::hasTable('rnd_produk_bahan_aktif')) {
                foreach (DB::table('rnd_produk_bahan_aktif')->orderBy('id')->get() as $link) {
                    $zatId = $map[$link->bahan_aktif_id] ?? null;
                    if ($zatId === null) {
                        continue;
                    }
                    // Two bahan aktif can map to one zat aktif: keep one link per product
                    $rows[$link->produk_id . ':' . $zatId] = [
                        'produk_id' => $link->produk_id,
                        'zataktif_id' => $zatId,
                        'created_at' => $link->created_at ?? $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (!empty($rows)) {
                DB::table('rnd_produk_zat_aktif')->insert(array_values($rows));
            }

            Log::info('Merged rnd_master_bahan_aktif into erm_zataktif', ['bahan_to_zat' => $map, 'links' => count($rows)]);
        }

        Schema::dropIfExists('rnd_produk_bahan_aktif');
        Schema::dropIfExists('rnd_master_bahan_aktif');
    }

    public function down(): void
    {
        Schema::create('rnd_master_bahan_aktif', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan_aktif');
            $table->timestamps();
        });

        Schema::create('rnd_produk_bahan_aktif', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('bahan_aktif_id');
            $table->timestamps();

            $table->unique(['produk_id', 'bahan_aktif_id'], 'rnd_produk_bahan_aktif_unique');
            $table->foreign('produk_id', 'rnd_produk_bahan_aktif_produk_fk')
                ->references('id')->on('rnd_produk')->cascadeOnDelete();
            $table->foreign('bahan_aktif_id', 'rnd_produk_bahan_aktif_bahan_fk')
                ->references('id')->on('rnd_master_bahan_aktif')->cascadeOnDelete();
        });

        // Rebuild bahan aktif from the zat aktif used by R&D products (zat aktif rows created by up() are kept)
        $now = now();
        $bahanByZat = [];
        $links = DB::table('rnd_produk_zat_aktif as p')
            ->join('erm_zataktif as z', 'z.id', '=', 'p.zataktif_id')
            ->orderBy('p.id')
            ->get(['p.produk_id', 'p.zataktif_id', 'z.nama']);
        foreach ($links as $link) {
            $bahanByZat[$link->zataktif_id] ??= DB::table('rnd_master_bahan_aktif')->insertGetId([
                'nama_bahan_aktif' => $link->nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('rnd_produk_bahan_aktif')->insert([
                'produk_id' => $link->produk_id,
                'bahan_aktif_id' => $bahanByZat[$link->zataktif_id],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::dropIfExists('rnd_produk_zat_aktif');
    }

    // Copies of ZatAktif::cleanNama / compareKey, so this migration does not change if the model does

    private function cleanNama(string $nama): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $nama)));
    }

    private function compareKey(string $nama): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($nama));
    }
};
