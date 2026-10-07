<?php

use App\Models\ERM\PaketRacikan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Racikan rows remember which paket they were made from (id) and its name at that moment (nama),
 * so resep, etiket and billing history no longer guess the paket from obat + dosis.
 * paket_racikan_nama stays readable after the paket is renamed or deleted.
 */
return new class extends Migration
{
    private const TABLES = ['erm_resepdokter', 'erm_resepfarmasi'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('paket_racikan_id')->nullable()->after('racikan_ke')
                    ->constrained('erm_paket_racikan')->nullOnDelete();
                $t->string('paket_racikan_nama')->nullable()->after('paket_racikan_id');
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('paket_racikan_id');
                $t->dropColumn('paket_racikan_nama');
            });
        }
    }

    /**
     * Existing racikan: link the ones whose whole isi equals a paket's isi, the same rule the
     * history used to apply on the fly, so the names shown today are kept.
     */
    private function backfill(): void
    {
        $pakets = [];
        foreach (PaketRacikan::with('details')->get() as $paket) {
            if ($paket->details->isNotEmpty()) {
                $pakets[PaketRacikan::compositionKey($paket->details)] = $paket;
            }
        }
        if (empty($pakets)) {
            return;
        }

        foreach (self::TABLES as $table) {
            DB::table($table)
                ->whereNotNull('racikan_ke')
                ->select('visitation_id', 'racikan_ke')
                ->distinct()
                ->orderBy('visitation_id')
                ->orderBy('racikan_ke')
                ->chunk(500, function ($groups) use ($table, $pakets) {
                    foreach ($groups as $group) {
                        $rows = DB::table($table)
                            ->where('visitation_id', $group->visitation_id)
                            ->where('racikan_ke', $group->racikan_ke)
                            ->get(['obat_id', 'dosis'])
                            ->map(fn ($r) => ['obat_id' => $r->obat_id, 'dosis' => $r->dosis]);

                        $paket = $pakets[PaketRacikan::compositionKey($rows)] ?? null;
                        if ($paket) {
                            DB::table($table)
                                ->where('visitation_id', $group->visitation_id)
                                ->where('racikan_ke', $group->racikan_ke)
                                ->update(['paket_racikan_id' => $paket->id, 'paket_racikan_nama' => $paket->nama_paket]);
                        }
                    }
                });
        }
    }
};
