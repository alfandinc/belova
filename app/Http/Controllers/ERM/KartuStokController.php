<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ERM\Obat;

class KartuStokController extends Controller
{
    public function index()
    {
        return view('erm.kartu_stok.index');
    }

    public function data(Request $request)
    {
        // Get date range from request
        $start = request('start');
        $end = request('end');

        $obatList = Obat::withInactive()->get();
        $obatIds = $obatList->pluck('id')->toArray();

        // Batch masuk
        $masukBatch = DB::table('erm_fakturbeli_items')
            ->select('obat_id', DB::raw('SUM(qty) as total_masuk'))
            ->whereIn('obat_id', $obatIds)
            ->where('qty', '>', 0);
        if ($start && $end) {
            $masukBatch->whereBetween('created_at', [$start, $end]);
        }
        $masukBatch = $masukBatch->groupBy('obat_id')->pluck('total_masuk', 'obat_id');

        // Batch keluar Obat
        $keluarObatBatch = DB::table('finance_invoice_items as ii')
            ->select('ii.billable_id as obat_id', DB::raw('SUM(ii.quantity) as total_keluar'))
            ->leftJoin('finance_invoices as i', 'ii.invoice_id', '=', 'i.id')
            ->where('ii.billable_type', 'App\\Models\\ERM\\Obat')
            ->whereIn('ii.billable_id', $obatIds);
        if ($start && $end) {
            $keluarObatBatch->whereBetween('i.created_at', [$start, $end]);
        }
        $keluarObatBatch = $keluarObatBatch->groupBy('ii.billable_id')->pluck('total_keluar', 'obat_id');

        // Batch keluar ResepFarmasi
        $keluarResepBatch = DB::table('finance_invoice_items as ii')
            ->select('rf.obat_id', DB::raw('SUM(ii.quantity) as total_keluar'))
            ->leftJoin('finance_invoices as i', 'ii.invoice_id', '=', 'i.id')
            ->leftJoin('erm_resepfarmasi as rf', function($join) {
                $join->on('ii.billable_id', '=', 'rf.id');
            })
            ->where('ii.billable_type', 'App\\Models\\ERM\\ResepFarmasi')
            ->whereIn('rf.obat_id', $obatIds);
        if ($start && $end) {
            $keluarResepBatch->whereBetween('i.created_at', [$start, $end]);
        }
        $keluarResepBatch = $keluarResepBatch->groupBy('rf.obat_id')->pluck('total_keluar', 'obat_id');

        $result = [];
        foreach ($obatList as $obat) {
            $masuk = $masukBatch[$obat->id] ?? 0;
            $keluarObat = $keluarObatBatch[$obat->id] ?? 0;
            $keluarResep = $keluarResepBatch[$obat->id] ?? 0;
            $keluar = $keluarObat + $keluarResep;
            $result[] = [
                'nama_obat' => $obat->nama,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok' => $obat->stok,
                'detail' => '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$obat->id.'">Detail</button>'
            ];
        }
        return response()->json(['data' => $result]);
    }

        public function detail(Request $request)
        {
            try {
                $obatId = $request->input('obat_id');
                $start = $request->input('start');
                $end = $request->input('end');

                // Get masuk transactions
                $masukQuery = DB::table('erm_fakturbeli_items as fi')
                    ->leftJoin('erm_fakturbeli as f', 'fi.fakturbeli_id', '=', 'f.id')
                    ->where('fi.obat_id', $obatId)
                    ->where('fi.qty', '>', 0);
                if ($start && $end) {
                    $masukQuery->whereBetween('fi.updated_at', [$start, $end]);
                }
                $masuk = $masukQuery
                    ->leftJoin('erm_pemasok as p', 'f.pemasok_id', '=', 'p.id')
                    ->select('fi.qty as jumlah', 'f.updated_at as created_at', 'f.no_faktur as no_ref', DB::raw("'Masuk' as tipe"), 'p.nama as nama_pemasok')
                    ->get();

                // Get keluar transactions from invoice items (Obat and ResepFarmasi)
                // 1. Obat: billable_type = Obat, billable_id = obatId
                $keluarObatQuery = DB::table('finance_invoice_items as ii')
                    ->leftJoin('finance_invoices as i', 'ii.invoice_id', '=', 'i.id')
                    ->where('ii.billable_type', 'App\\Models\\ERM\\Obat')
                    ->where('ii.billable_id', $obatId);
                if ($start && $end) {
                    $keluarObatQuery->whereBetween('i.created_at', [$start, $end]);
                }
                $keluarObat = $keluarObatQuery
                    ->leftJoin('erm_visitations as v', 'i.visitation_id', '=', 'v.id')
                    ->leftJoin('erm_pasiens as ps', 'v.pasien_id', '=', 'ps.id')
                    ->select('ii.quantity as jumlah', 'i.created_at as created_at', 'i.invoice_number as no_ref', DB::raw("'Keluar' as tipe"), 'ps.nama as nama_pasien')
                    ->get();

                $keluarResepQuery = DB::table('finance_invoice_items as ii')
                    ->leftJoin('finance_invoices as i', 'ii.invoice_id', '=', 'i.id')
                    ->leftJoin('erm_resepfarmasi as rf', function($join) {
                        $join->on('ii.billable_id', '=', 'rf.id');
                    })
                    ->where('ii.billable_type', 'App\\Models\\ERM\\ResepFarmasi')
                    ->where('rf.obat_id', $obatId);
                if ($start && $end) {
                    $keluarResepQuery->whereBetween('i.created_at', [$start, $end]);
                }
                $keluarResep = $keluarResepQuery
                    ->leftJoin('erm_visitations as v', 'i.visitation_id', '=', 'v.id')
                    ->leftJoin('erm_pasiens as ps', 'v.pasien_id', '=', 'ps.id')
                    ->select('ii.quantity as jumlah', 'i.created_at as created_at', 'i.invoice_number as no_ref', DB::raw("'Keluar' as tipe"), 'ps.nama as nama_pasien')
                    ->get();


                // DEBUG: Dump all invoice items for this obat

                $keluar = collect($keluarObat)->merge($keluarResep);

                // Merge and sort by date ascending for calculation
                    // Merge and sort by date descending for backward calculation
                    $transactions = collect($masuk)->merge($keluar)->sortByDesc('created_at')->values();

                    // Start from current stock
                    $currentStok = DB::table('erm_obat')->where('id', $obatId)->value('stok');
                    $runningStok = $currentStok;
                    $rows = [];
                    foreach ($transactions as $trx) {
                        $rows[] = [
                            'tipe' => $trx->tipe == 'Masuk'
                                ? '<span class="badge badge-success">Masuk</span>'
                                : '<span class="badge badge-danger">Keluar</span>',
                            'tanggal' => $trx->created_at,
                            'jumlah' => $trx->jumlah,
                            'no_ref' => $trx->no_ref,
                            'stok' => $runningStok,
                            'info' => $trx->tipe == 'Masuk'
                                ? ($trx->nama_pemasok ?? '-')
                                : ($trx->nama_pasien ?? '-')
                        ];
                        // Reverse the calculation: subtract for Masuk, add for Keluar
                        if ($trx->tipe == 'Masuk') {
                            $runningStok -= $trx->jumlah;
                        } else {
                            $runningStok += $trx->jumlah;
                        }
                    }

                $html = '';
                $html .= '<div class="table-responsive"><table class="table table-bordered table-striped">';
                $html .= '<thead><tr><th>Tipe</th><th>Tanggal</th><th>Jumlah</th><th>No Faktur/Resep</th><th>Stok Setelah</th><th>Info</th></tr></thead><tbody>';
                if (count($rows) === 0) {
                    $html .= '<tr><td colspan="6" class="text-center text-danger">Tidak ada transaksi ditemukan. (DEBUG)</td></tr>';
                } else {
                    foreach ($rows as $row) {
                        $html .= '<tr>';
                        $html .= '<td>' . $row['tipe'] . '</td>';
                        $html .= '<td>' . $row['tanggal'] . '</td>';
                        $html .= '<td>' . $row['jumlah'] . '</td>';
                        $html .= '<td>' . $row['no_ref'] . '</td>';
                        $html .= '<td>' . $row['stok'] . '</td>';
                        $html .= '<td>' . $row['info'] . '</td>';
                        $html .= '</tr>';
                    }
                }
                $html .= '</tbody></table></div>';
                return $html;
            } catch (\Exception $e) {
                return '<div class="text-danger">ERROR: ' . $e->getMessage() . '</div>';
            }
        }
}
