<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only check: every stok row should equal the stok_setelah of its latest kartu stok entry
 * (same obat, gudang and batch). A difference means stok changed without kartu stok
 * (direct database edit, a bug, an old migration) and needs a stok opname or investigation.
 */
class RekonsiliasiStok extends Command
{
    protected $signature = 'stok:rekonsiliasi {--limit=50 : Jumlah selisih yang ditampilkan} {--gudang= : Hanya gudang ini (id)}';

    protected $description = 'Bandingkan stok per batch dengan saldo terakhir kartu stok (hanya membaca, tidak mengubah data)';

    public function handle(): int
    {
        // Latest movement per obat + gudang + batch; the qty-0 stok opname markers are not movements
        $latest = DB::table('erm_kartu_stok')
            ->whereIn('tipe', ['masuk', 'keluar'])
            ->select('obat_id', 'gudang_id', 'batch', DB::raw('MAX(id) as last_id'))
            ->groupBy('obat_id', 'gudang_id', 'batch');

        $rows = DB::table('erm_obat_stok_gudang as s')
            ->whereNull('s.deleted_at')
            ->when($this->option('gudang'), function ($q, $gudangId) {
                $q->where('s.gudang_id', $gudangId);
            })
            ->leftJoinSub($latest, 'l', function ($join) {
                $join->on('l.obat_id', '=', 's.obat_id')
                    ->on('l.gudang_id', '=', 's.gudang_id')
                    ->whereRaw('l.batch <=> s.batch');
            })
            ->leftJoin('erm_kartu_stok as k', 'k.id', '=', 'l.last_id')
            ->leftJoin('erm_obat as o', 'o.id', '=', 's.obat_id')
            ->leftJoin('erm_gudang as g', 'g.id', '=', 's.gudang_id')
            ->select('s.id', 'o.nama as obat', 'g.nama as gudang', 's.batch', 's.stok', 'k.stok_setelah', 'k.tanggal')
            ->get();

        $selisih = $rows->filter(function ($r) {
            $kartu = $r->stok_setelah === null ? 0.0 : (float) $r->stok_setelah;
            return abs((float) $r->stok - $kartu) > 0.009;
        })->sortByDesc(function ($r) {
            return abs((float) $r->stok - (float) $r->stok_setelah);
        })->values();

        $this->info("Baris stok diperiksa: {$rows->count()}. Tidak cocok dengan kartu stok: {$selisih->count()}.");

        if ($selisih->isNotEmpty()) {
            $this->table(
                ['ID stok', 'Obat', 'Gudang', 'Batch', 'Stok', 'Saldo kartu', 'Selisih', 'Kartu terakhir'],
                $selisih->take((int) $this->option('limit'))->map(function ($r) {
                    $kartu = $r->stok_setelah === null ? null : (float) $r->stok_setelah;
                    return [
                        $r->id,
                        $r->obat,
                        $r->gudang,
                        $r->batch ?? '-',
                        (float) $r->stok,
                        $kartu === null ? '(tidak ada)' : $kartu,
                        round((float) $r->stok - (float) $kartu, 2),
                        $r->tanggal ?? '-',
                    ];
                })->all()
            );
        }

        return self::SUCCESS;
    }
}
