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
        $start = $request->input('start');
        $end = $request->input('end');
        $onlyWithTransactions = $request->input('only_with_transactions', false);

        // Optimized query: Get all kartu stok data in one query with aggregation
        $kartuStokQuery = DB::table('erm_kartu_stok as ks')
            ->leftJoin('erm_obat as o', 'ks.obat_id', '=', 'o.id')
            ->select(
                'ks.obat_id',
                'o.nama as nama_obat',
                DB::raw('SUM(CASE WHEN ks.tipe = "masuk" THEN ks.qty ELSE 0 END) as total_masuk'),
                DB::raw('SUM(CASE WHEN ks.tipe = "keluar" THEN ks.qty ELSE 0 END) as total_keluar')
            );
            
        if ($start && $end) {
            $kartuStokQuery->whereBetween('ks.tanggal', [$start, $end]);
        }
        
        $kartuStokData = $kartuStokQuery
            ->groupBy('ks.obat_id', 'o.nama')
            ->orderBy('o.nama')
            ->get()
            ->keyBy('obat_id');

        $result = [];

        // Add obat with transactions
        foreach ($kartuStokData as $data) {
            $result[] = [
                'nama_obat' => $data->nama_obat,
                'masuk' => (int)$data->total_masuk,
                'keluar' => (int)$data->total_keluar,
                'detail' => '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$data->obat_id.'">Detail</button>'
            ];
        }

        // Only add obat without transactions if not filtering for transactions only
        if (!$onlyWithTransactions) {
            // Get all obat that don't have transactions in the period
            $obatWithoutTransactions = DB::table('erm_obat as o')
                ->select('o.id as obat_id', 'o.nama as nama_obat')
                ->whereNotIn('o.id', $kartuStokData->pluck('obat_id'))
                ->orderBy('o.nama')
                ->get();

            // Add obat without transactions (0 masuk, 0 keluar)
            foreach ($obatWithoutTransactions as $obat) {
                $result[] = [
                    'nama_obat' => $obat->nama_obat,
                    'masuk' => 0,
                    'keluar' => 0,
                    'detail' => '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$obat->obat_id.'">Detail</button>'
                ];
            }
        }

        return response()->json(['data' => $result]);
    }

        public function detail(Request $request)
        {
            try {
                $obatId = $request->input('obat_id');
                $start = $request->input('start');
                $end = $request->input('end');

                // Get transactions from kartu_stok table (more accurate and complete)
                $kartuStokQuery = DB::table('erm_kartu_stok as ks')
                    ->leftJoin('erm_gudang as g', 'ks.gudang_id', '=', 'g.id')
                    ->where('ks.obat_id', $obatId);
                    
                if ($start && $end) {
                    $kartuStokQuery->whereBetween('ks.tanggal', [$start, $end]);
                }
                
                $kartuStokData = $kartuStokQuery
                    ->select(
                        'ks.qty as jumlah', 
                        'ks.tanggal as created_at', 
                        'ks.tipe', 
                        'ks.keterangan',
                        'ks.ref_type',
                        'ks.ref_id',
                        'ks.batch',
                        'ks.stok_setelah',
                        'g.nama as nama_gudang'
                    )
                    ->orderBy('ks.tanggal', 'desc')  // Terbaru di atas
                    ->orderBy('ks.id', 'desc')       // Jika tanggal sama, ID terbesar dulu
                    ->get();

                // Process kartu stok data
                $rows = [];
                foreach ($kartuStokData as $row) {
                    // Format reference info dengan nomor dokumen asli
                    $refInfo = '';
                    if ($row->ref_type && $row->ref_id) {
                        $refTypeFormatted = ucfirst(str_replace('_', ' ', $row->ref_type));
                        $refNumber = '';
                        
                        // Get actual document number based on ref_type
                        try {
                            switch ($row->ref_type) {
                                case 'invoice_penjualan':
                                case 'invoice_return':
                                    $invoice = DB::table('finance_invoices')->where('id', $row->ref_id)->first();
                                    $refNumber = $invoice ? $invoice->invoice_number : '#' . $row->ref_id;
                                    break;
                                    
                                case 'faktur_pembelian':
                                    $faktur = DB::table('erm_fakturbeli')->where('id', $row->ref_id)->first();
                                    $refNumber = $faktur ? $faktur->no_faktur : '#' . $row->ref_id;
                                    break;
                                    
                                case 'mutasi_gudang':
                                    $mutasi = DB::table('erm_mutasi_gudang')->where('id', $row->ref_id)->first();
                                    $refNumber = $mutasi ? $mutasi->nomor_mutasi : '#' . $row->ref_id;
                                    break;
                                    
                                case 'stok_opname':
                                    $opname = DB::table('erm_stok_opname')->where('id', $row->ref_id)->first();
                                    $refNumber = $opname ? 'OPNAME-' . $opname->periode_bulan . '-' . $opname->periode_tahun . ' (#' . $row->ref_id . ')' : '#' . $row->ref_id;
                                    break;
                                    
                                default:
                                    $refNumber = '#' . $row->ref_id;
                                    break;
                            }
                        } catch (\Exception $e) {
                            $refNumber = '#' . $row->ref_id;
                        }
                        
                        // Only show 'Lihat' button for faktur pembelian and invoice types
                        $allowedViewTypes = ['faktur_pembelian', 'invoice_penjualan', 'invoice_return'];
                        $viewBtn = '';
                        if (in_array($row->ref_type, $allowedViewTypes)) {
                            $viewBtn = '<br><a href="#" class="btn btn-sm btn-outline-primary btn-view-ref mt-1" data-ref-type="' . e($row->ref_type) . '" data-ref-id="' . e($row->ref_id) . '">Lihat</a>';
                        }
                        $refInfo = '<small class="text-muted">' . $refTypeFormatted . '</small><br><strong>' . $refNumber . '</strong>' . $viewBtn;
                    } else {
                        $refInfo = '<span class="text-muted">-</span>';
                    }
                    
                    // Badge styling untuk tipe
                    $tipeDisplay = $row->tipe == 'masuk'
                        ? '<span class="badge badge-success"><i class="fas fa-arrow-up"></i> Masuk</span>'
                        : '<span class="badge badge-danger"><i class="fas fa-arrow-down"></i> Keluar</span>';
                    
                    // Format tanggal yang lebih baik
                    $tanggalFormatted = date('d/m/Y H:i', strtotime($row->created_at));
                    
                    // Format jumlah dengan styling
                    $jumlahFormatted = '<strong>' . number_format($row->jumlah, 0) . '</strong>';
                    
                    // Format stok setelah
                    $stokFormatted = '<span class="badge badge-secondary">' . number_format($row->stok_setelah, 0) . '</span>';
                    
                    // Format keterangan dengan gudang info
                    $infoFormatted = $row->keterangan;
                    if ($row->nama_gudang) {
                        $infoFormatted .= '<br><small class="text-info"><i class="fas fa-warehouse"></i> ' . $row->nama_gudang . '</small>';
                    }
                    
                    $rows[] = [
                        'tipe' => $tipeDisplay,
                        'tanggal' => $tanggalFormatted,
                        'jumlah' => $jumlahFormatted,
                        'no_ref' => $refInfo,
                        'stok' => $stokFormatted,
                        'batch' => $row->batch ? '<code>' . $row->batch . '</code>' : '<span class="text-muted">-</span>',
                        'info' => $infoFormatted
                    ];
                }

                // Enhanced HTML table with better styling
                $html = '';
                $html .= '<div class="table-responsive">';
                $html .= '<table class="table table-sm table-bordered table-striped table-hover">';
                $html .= '<thead class="thead-light">';
                $html .= '<tr>';
                $html .= '<th width="10%">Tipe</th>';
                $html .= '<th width="15%">Tanggal</th>';
                $html .= '<th width="10%">Jumlah</th>';
                $html .= '<th width="15%">Referensi</th>';
                $html .= '<th width="10%">Stok Setelah</th>';
                $html .= '<th width="10%">Batch</th>';
                $html .= '<th width="30%">Keterangan</th>';
                $html .= '</tr>';
                $html .= '</thead><tbody>';
                
                if (count($rows) === 0) {
                    $html .= '<tr><td colspan="7" class="text-center text-muted py-4">';
                    $html .= '<i class="fas fa-inbox fa-2x mb-2 d-block"></i>';
                    $html .= 'Tidak ada transaksi ditemukan dalam periode ini.';
                    $html .= '</td></tr>';
                } else {
                    foreach ($rows as $row) {
                        $html .= '<tr>';
                        $html .= '<td class="text-center">' . $row['tipe'] . '</td>';
                        $html .= '<td>' . $row['tanggal'] . '</td>';
                        $html .= '<td class="text-right">' . $row['jumlah'] . '</td>';
                        $html .= '<td>' . $row['no_ref'] . '</td>';
                        $html .= '<td class="text-center">' . $row['stok'] . '</td>';
                        $html .= '<td class="text-center">' . $row['batch'] . '</td>';
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
