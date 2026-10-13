<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One Satuan master (erm_satuan) feeds every unit dropdown: satuan stok & satuan dosis obat and sediaan produk R&D.
 * Values stay stored as text on the rows (erm_obat.satuan / satuan_stok, rnd_produk.sediaan); the master only lists
 * the allowed options so all modules use the same spelling.
 *
 * Also replaces rnd_produk.sediaan_id (FK to rnd_master_sediaan) with the text column rnd_produk.sediaan.
 */
return new class extends Migration
{
    // Seed lists: the previous Obat::SATUAN_STOK_LIST / SATUAN_DOSIS_LIST first (same order), then common extras
    private const STOK = [
        'Tablet', 'Kapsul', 'Kaplet', 'Botol', 'Tube', 'Pot', 'Ampul', 'Vial', 'Sachet', 'Strip',
        'Pcs', 'Pack', 'Box', 'Softbag', 'Pen', 'Pump', 'g', 'IU',
        'Jar', 'Dus', 'Kit', 'Set', 'Lembar', 'Roll', 'Pasang', 'Unit', 'Suppositoria', 'Ovula',
    ];
    private const DOSIS = [
        'mg', 'mcg', 'g', 'mL', 'IU', '%',
        'kg', 'L', 'mEq', 'mmol', 'Unit', 'Tetes',
    ];

    // Same spellings as Obat::SATUAN_ALIASES (copied so this migration does not change with the model)
    private const ALIASES = [
        'mg' => 'mg', 'mcg' => 'mcg', 'g' => 'g', 'gr' => 'g', 'gram' => 'g', 'ml' => 'mL', 'iu' => 'IU', '%' => '%',
        'tablet' => 'Tablet', 'tab' => 'Tablet', 'kapsul' => 'Kapsul', 'kaplet' => 'Kaplet', 'botol' => 'Botol',
        'plaboth' => 'Botol', 'tube' => 'Tube', 'pot' => 'Pot', 'ampul' => 'Ampul', 'vial' => 'Vial',
        'sachet' => 'Sachet', 'strip' => 'Strip', 'pcs' => 'Pcs', 'pack' => 'Pack', 'box' => 'Box',
        'softbag' => 'Softbag', 'pen' => 'Pen', 'pump' => 'Pump',
    ];

    public function up(): void
    {
        Schema::create('erm_satuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->boolean('untuk_stok')->default(false);
            $table->boolean('untuk_dosis')->default(false);
            $table->timestamps();
        });

        $satuan = []; // nama => [stok, dosis], insertion order = dropdown order
        // $onlyNew: values found on obat rows only add missing units (a stok unit used as dosis keeps its flags,
        // dosis already accepts every stok unit)
        $add = function (string $nama, bool $stok, bool $dosis, bool $onlyNew = false) use (&$satuan) {
            $nama = trim($nama);
            if ($nama === '') {
                return;
            }
            // Case-insensitive: one row per unit
            foreach ($satuan as $existing => $flags) {
                if (strcasecmp($existing, $nama) === 0) {
                    if (!$onlyNew) {
                        $satuan[$existing] = [$flags[0] || $stok, $flags[1] || $dosis];
                    }
                    return;
                }
            }
            $satuan[$nama] = [$stok, $dosis];
        };

        foreach (self::STOK as $nama) {
            $add($nama, true, false);
        }
        foreach (self::DOSIS as $nama) {
            $add($nama, false, true);
        }
        // Values already stored on obat that the old lists did not cover (e.g. "Lainnya") keep working
        foreach (DB::table('erm_obat')->whereNotNull('satuan_stok')->distinct()->pluck('satuan_stok') as $nama) {
            $add((string) $this->normalize($nama), true, false, true);
        }
        foreach (DB::table('erm_obat')->whereNotNull('satuan')->distinct()->pluck('satuan') as $nama) {
            $add((string) $this->normalize($nama), false, true, true);
        }

        $now = now();
        DB::table('erm_satuan')->insert(collect($satuan)->map(fn ($flags, $nama) => [
            'nama' => $nama,
            'untuk_stok' => $flags[0],
            'untuk_dosis' => $flags[1],
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());

        // R&D sediaan: id -> text
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->string('sediaan', 50)->nullable()->after('sediaan_id');
        });

        if (Schema::hasTable('rnd_master_sediaan')) {
            foreach (DB::table('rnd_master_sediaan')->get(['id', 'nama_sediaan']) as $sediaan) {
                $nama = $this->normalize($sediaan->nama_sediaan);
                if ($nama === null) {
                    continue;
                }
                $nama = $this->ensureSatuan($nama, $now);
                DB::table('rnd_produk')->where('sediaan_id', $sediaan->id)->update(['sediaan' => $nama]);
            }
        }

        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->dropForeign('rnd_produk_sediaan_id_foreign');
            $table->dropColumn('sediaan_id');
        });
        Schema::dropIfExists('rnd_master_sediaan');
    }

    public function down(): void
    {
        Schema::create('rnd_master_sediaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sediaan');
            $table->timestamps();
        });

        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->unsignedBigInteger('sediaan_id')->nullable()->after('sediaan');
        });

        $now = now();
        foreach (DB::table('rnd_produk')->whereNotNull('sediaan')->distinct()->pluck('sediaan') as $nama) {
            $id = DB::table('rnd_master_sediaan')->insertGetId(['nama_sediaan' => $nama, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('rnd_produk')->where('sediaan', $nama)->update(['sediaan_id' => $id]);
        }

        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->foreign('sediaan_id', 'rnd_produk_sediaan_id_foreign')
                ->references('id')->on('rnd_master_sediaan')->cascadeOnUpdate()->restrictOnDelete();
            $table->dropColumn('sediaan');
        });

        Schema::dropIfExists('erm_satuan');
    }

    private function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return self::ALIASES[strtolower($value)] ?? $value;
    }

    /** Existing master spelling for $nama, adding it (as a dosis/netto unit) when missing. */
    private function ensureSatuan(string $nama, $now): string
    {
        $existing = DB::table('erm_satuan')->whereRaw('LOWER(nama) = ?', [strtolower($nama)])->value('nama');
        if ($existing !== null) {
            return $existing;
        }
        DB::table('erm_satuan')->insert([
            'nama' => $nama, 'untuk_stok' => false, 'untuk_dosis' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return $nama;
    }
};
