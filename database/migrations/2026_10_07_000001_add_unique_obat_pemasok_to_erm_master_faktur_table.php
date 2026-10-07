<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Master Pembelian holds one price per obat + pemasok. The app already refuses duplicates
 * (MasterFakturController, MasterPembelianService); this makes the database enforce it too.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(fn () => $this->mergeDuplicates());

        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->unique(['obat_id', 'pemasok_id'], 'erm_master_faktur_obat_pemasok_unique');
        });
    }

    public function down(): void
    {
        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->dropUnique('erm_master_faktur_obat_pemasok_unique');
        });
    }

    /**
     * Older data has some obat + pemasok pairs more than once. Keep the most recently updated row
     * (the last price entered), fill its empty principal_id / notes from the others, delete the rest.
     * Nothing references erm_master_faktur rows, so deleting them is safe. Merged rows are logged.
     */
    private function mergeDuplicates(): void
    {
        $pairs = DB::table('erm_master_faktur')
            ->select('obat_id', 'pemasok_id')
            ->groupBy('obat_id', 'pemasok_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($pairs as $pair) {
            $rows = DB::table('erm_master_faktur')
                ->where('obat_id', $pair->obat_id)
                ->where('pemasok_id', $pair->pemasok_id)
                ->orderByRaw('updated_at IS NULL') // rows with a date first
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $keep = $rows->first();
            $others = $rows->slice(1);

            $fill = [];
            if (empty($keep->principal_id) && ($principalId = $others->pluck('principal_id')->filter()->first())) {
                $fill['principal_id'] = $principalId;
            }
            if (trim((string) $keep->notes) === '' && ($notes = $others->pluck('notes')->filter(fn ($n) => trim((string) $n) !== '')->first())) {
                $fill['notes'] = $notes;
            }
            if ($fill) {
                DB::table('erm_master_faktur')->where('id', $keep->id)->update($fill);
            }

            DB::table('erm_master_faktur')->whereIn('id', $others->pluck('id'))->delete();

            Log::info('erm_master_faktur duplicate merged', [
                'obat_id' => $pair->obat_id,
                'pemasok_id' => $pair->pemasok_id,
                'kept' => (array) $keep,
                'filled' => $fill,
                'deleted' => $others->map(fn ($r) => (array) $r)->values()->all(),
            ]);
        }
    }
};
