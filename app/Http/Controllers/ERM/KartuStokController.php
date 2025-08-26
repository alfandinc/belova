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
        $result = [];
        foreach ($obatList as $obat) {
            // Masuk
            $masukQuery = DB::table('erm_fakturbeli_items')
                ->where('obat_id', $obat->id)
                ->where('qty', '>', 0);
            if ($start && $end) {
                $masukQuery->whereBetween('created_at', [$start, $end]);
            }
            $masuk = $masukQuery->sum('qty');

            // Keluar
            $keluarQuery = DB::table('erm_resepfarmasi')
                ->where('obat_id', $obat->id);
            if ($start && $end) {
                $keluarQuery->whereBetween('created_at', [$start, $end]);
            }
            $keluar = $keluarQuery->sum('jumlah');

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
                    $masukQuery->whereBetween('fi.created_at', [$start, $end]);
                }
                $masuk = $masukQuery
                    ->select('fi.qty as jumlah', 'fi.created_at', 'f.no_faktur as no_ref', DB::raw("'Masuk' as tipe"))
                    ->get();

                // Get keluar transactions, join to erm_resepdetail for no_resep
                $keluarQuery = DB::table('erm_resepfarmasi as rf')
                    ->leftJoin('erm_resepdetail as rd', 'rf.visitation_id', '=', 'rd.visitation_id')
                    ->where('rf.obat_id', $obatId);
                if ($start && $end) {
                    $keluarQuery->whereBetween('rf.created_at', [$start, $end]);
                }
                $keluar = $keluarQuery
                    ->select('rf.jumlah as jumlah', 'rf.created_at', 'rd.no_resep as no_ref', DB::raw("'Keluar' as tipe"))
                    ->get();

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
                            'stok' => $runningStok
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
                $html .= '<thead><tr><th>Tipe</th><th>Tanggal</th><th>Jumlah</th><th>No Faktur/Resep</th><th>Stok Setelah</th></tr></thead><tbody>';
                if (count($rows) === 0) {
                    $html .= '<tr><td colspan="5" class="text-center text-danger">Tidak ada transaksi ditemukan. (DEBUG)</td></tr>';
                } else {
                    foreach ($rows as $row) {
                        $html .= '<tr>';
                        $html .= '<td>' . $row['tipe'] . '</td>';
                        $html .= '<td>' . $row['tanggal'] . '</td>';
                        $html .= '<td>' . $row['jumlah'] . '</td>';
                        $html .= '<td>' . $row['no_ref'] . '</td>';
                        $html .= '<td>' . $row['stok'] . '</td>';
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
