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

    /**
     * Export summary of stok terakhir (total per-obat across all batches up to end date)
     */
    public function exportStokTerakhir(Request $request)
    {
        $end = $request->input('end');
        $cutoff = $end ? (strpos($end, ' ') === false ? ($end . ' 23:59:59') : $end) : date('Y-m-d H:i:s');

        // Get all obat
        $obats = DB::table('erm_obat')->select('id', 'nama')->orderBy('nama')->get();

        $rows = [];
        $rows[] = ['Nama Obat', 'Total Stok Terakhir'];

        foreach ($obats as $obat) {
            $totalAll = 0;
            try {
                $batches = DB::table('erm_kartu_stok')
                    ->where('obat_id', $obat->id)
                    ->select('batch')
                    ->distinct()
                    ->pluck('batch');

                foreach ($batches as $batchVal) {
                    $batchQuery = DB::table('erm_kartu_stok')->where('obat_id', $obat->id);
                    if (is_null($batchVal) || $batchVal === '') {
                        $batchQuery->where(function($q) {
                            $q->whereNull('batch')->orWhere('batch', '');
                        });
                    } else {
                        $batchQuery->where('batch', $batchVal);
                    }

                    $batchQuery->where('tanggal', '<=', $cutoff);
                    $latest = $batchQuery->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->first();
                    if ($latest && isset($latest->stok_setelah)) {
                        $totalAll += (float)$latest->stok_setelah;
                    }
                }
            } catch (\Exception $e) {
                $totalAll = 0;
            }

            $rows[] = [$obat->nama, (int)$totalAll];
        }

        try {
            $export = new \App\Exports\ERM\KartuStokDetailExport($rows);
            $filename = 'stok_terakhir_' . date('Ymd_His') . '.xlsx';
            return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        // Build same dataset as the index/data + detail rows for each obat
        $start = $request->input('start');
        $end = $request->input('end');

        // Get all obat (we include obat without transactions)
        $obats = DB::table('erm_obat')->select('id', 'nama')->orderBy('nama')->get();

        $rows = [];
    // Header rows for excel
    $rows[] = ['Nama Obat', 'Tipe', 'Tanggal', 'Jumlah', 'Referensi', 'Stok Setelah (per batch)', 'Total Semua Batch', 'Batch', 'Keterangan', 'Gudang'];

        foreach ($obats as $obat) {
            // Add a separator / title row for this obat
            $rows[] = ["-- {$obat->nama} --", '', '', '', '', '', '', '', '', ''];

            // Fetch kartu stok transactions for this obat within date range
            // include id so we can tie-break on same-timestamp rows when computing totals
            $query = DB::table('erm_kartu_stok as ks')
                ->leftJoin('erm_gudang as g', 'ks.gudang_id', '=', 'g.id')
                ->where('ks.obat_id', $obat->id)
                ->select(
                    'ks.id',
                    'ks.gudang_id',
                    'ks.tipe',
                    'ks.qty as jumlah',
                    'ks.tanggal',
                    'ks.ref_type',
                    'ks.ref_id',
                    'ks.stok_setelah',
                    'ks.batch',
                    'ks.keterangan',
                    'g.nama as nama_gudang'
                )
                ->orderBy('ks.tanggal', 'desc')
                ->orderBy('ks.id', 'desc');

            if ($start && $end) {
                $query->whereBetween('ks.tanggal', [$start, $end]);
            }

            $transactions = $query->get();

            // get distinct batches for this obat (used to compute total across batches)
            $batches = DB::table('erm_kartu_stok')
                ->where('obat_id', $obat->id)
                ->select('batch')
                ->distinct()
                ->pluck('batch');

            if ($transactions->isEmpty()) {
                $rows[] = ['', 'Tidak ada transaksi', '', '', '', '', '', '', '', ''];
                continue;
            }

            foreach ($transactions as $t) {
                // Build reference number similar to detail() method
                $refNumber = '';
                if ($t->ref_type && $t->ref_id) {
                    try {
                        switch ($t->ref_type) {
                            case 'invoice_penjualan':
                            case 'invoice_return':
                                $invoice = DB::table('finance_invoices')->where('id', $t->ref_id)->first();
                                $refNumber = $invoice ? $invoice->invoice_number : '#' . $t->ref_id;
                                break;
                            case 'faktur_pembelian':
                                $faktur = DB::table('erm_fakturbeli')->where('id', $t->ref_id)->first();
                                $refNumber = $faktur ? $faktur->no_faktur : '#' . $t->ref_id;
                                break;
                            case 'mutasi_gudang':
                                $mutasi = DB::table('erm_mutasi_gudang')->where('id', $t->ref_id)->first();
                                $refNumber = $mutasi ? $mutasi->nomor_mutasi : '#' . $t->ref_id;
                                break;
                            case 'stok_opname':
                                $opname = DB::table('erm_stok_opname')->where('id', $t->ref_id)->first();
                                $refNumber = $opname ? 'OPNAME-' . $opname->periode_bulan . '-' . $opname->periode_tahun . ' (#' . $t->ref_id . ')' : '#' . $t->ref_id;
                                break;
                            default:
                                $refNumber = '#' . $t->ref_id;
                                break;
                        }
                    } catch (\Exception $e) {
                        $refNumber = '#' . $t->ref_id;
                    }
                }

                // compute total across batches for the same gudang as this transaction (same logic as detail view)
                $totalAll = 0;
                try {
                    foreach ($batches as $batchVal) {
                        $batchQuery = DB::table('erm_kartu_stok')->where('obat_id', $obat->id);
                        if (isset($t->gudang_id)) {
                            $batchQuery->where('gudang_id', $t->gudang_id);
                        } else {
                            $batchQuery->whereNull('gudang_id');
                        }
                        if (is_null($batchVal) || $batchVal === '') {
                            $batchQuery->where(function($q) {
                                $q->whereNull('batch')->orWhere('batch', '');
                            });
                        } else {
                            $batchQuery->where('batch', $batchVal);
                        }

                        $batchQuery->where(function($q) use ($t) {
                            $q->where('tanggal', '<', $t->tanggal)
                              ->orWhere(function($q2) use ($t) {
                                  $q2->where('tanggal', '=', $t->tanggal)->where('id', '<=', isset($t->id) ? $t->id : 0);
                              });
                        });

                        $latest = $batchQuery->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->first();
                        if ($latest && isset($latest->stok_setelah)) {
                            $totalAll += (float)$latest->stok_setelah;
                        }
                    }
                } catch (\Exception $e) {
                    $totalAll = (float)$t->stok_setelah;
                }

                $rows[] = [
                    $obat->nama,
                    $t->tipe,
                    $t->tanggal,
                    $t->jumlah,
                    $refNumber,
                    $t->stok_setelah,
                    $totalAll,
                    $t->batch,
                    $t->keterangan,
                    $t->nama_gudang
                ];
            }
        }

        // Use Maatwebsite Excel to export
        try {
            $export = new \App\Exports\ERM\KartuStokDetailExport($rows);
            $filename = 'kartu_stok_' . date('Ymd_His') . '.xlsx';
            return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
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

        // Determine cutoff datetime for computing "stok terakhir" (use end date end-of-day if provided)
        $cutoff = $end ? (strpos($end, ' ') === false ? ($end . ' 23:59:59') : $end) : date('Y-m-d H:i:s');

        // Helper: compute stok terakhir (sum of latest stok_setelah per batch) for a given obat up to cutoff
        $computeStokTerakhir = function($obatId) use ($cutoff) {
            $totalAll = 0;
            try {
                $batches = DB::table('erm_kartu_stok')
                    ->where('obat_id', $obatId)
                    ->select('batch')
                    ->distinct()
                    ->pluck('batch');

                foreach ($batches as $batchVal) {
                    $batchQuery = DB::table('erm_kartu_stok')->where('obat_id', $obatId);
                    if (is_null($batchVal) || $batchVal === '') {
                        $batchQuery->where(function($q) {
                            $q->whereNull('batch')->orWhere('batch', '');
                        });
                    } else {
                        $batchQuery->where('batch', $batchVal);
                    }

                    // Only consider records up to and including cutoff
                    $batchQuery->where('tanggal', '<=', $cutoff);

                    $latest = $batchQuery->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->first();
                    if ($latest && isset($latest->stok_setelah)) {
                        $totalAll += (float)$latest->stok_setelah;
                    }
                }
            } catch (\Exception $e) {
                // fallback: 0
                $totalAll = 0;
            }

            return (int)$totalAll;
        };

        // Add obat with transactions
        foreach ($kartuStokData as $data) {
            $stokTerakhir = $computeStokTerakhir($data->obat_id);
            $result[] = [
                'nama_obat' => $data->nama_obat,
                'masuk' => (int)$data->total_masuk,
                'keluar' => (int)$data->total_keluar,
                'stok_terakhir' => $stokTerakhir,
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
                $stokTerakhir = $computeStokTerakhir($obat->obat_id);
                $result[] = [
                    'nama_obat' => $obat->nama_obat,
                    'masuk' => 0,
                    'keluar' => 0,
                    'stok_terakhir' => $stokTerakhir,
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
                        'ks.id',
                            'ks.qty as jumlah', 
                            'ks.tanggal as created_at', 
                            'ks.tipe', 
                            'ks.keterangan',
                            'ks.ref_type',
                            'ks.ref_id',
                            'ks.batch',
                            'ks.stok_setelah',
                            'ks.gudang_id',
                            'g.nama as nama_gudang'
                    )
                    ->orderBy('ks.tanggal', 'desc')  // Terbaru di atas
                    ->orderBy('ks.id', 'desc')       // Jika tanggal sama, ID terbesar dulu
                    ->get();

                // Process kartu stok data
                $rows = [];

                // Get distinct batches for this obat to compute totals across batches
                $batches = DB::table('erm_kartu_stok')
                    ->where('obat_id', $obatId)
                    ->select('batch')
                    ->distinct()
                    ->pluck('batch');

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
                    
                    // Compute total stock across all batches for the SAME GUDANG as this transaction
                    // (previously this summed across all gudangs; we now limit to the transaction's gudang)
                    $totalAll = 0;
                    try {
                        foreach ($batches as $batchVal) {
                            // For null/empty batch values, match where batch is null OR empty string
                            $batchQuery = DB::table('erm_kartu_stok')->where('obat_id', $obatId);
                            // Filter by the same gudang as the current transaction row so the "Total semua batch"
                            // reflects stock within that gudang only.
                            if (isset($row->gudang_id)) {
                                $batchQuery->where('gudang_id', $row->gudang_id);
                            } else {
                                // transaction had no gudang_id (null), so restrict to null gudang_id records
                                $batchQuery->whereNull('gudang_id');
                            }
                            if (is_null($batchVal) || $batchVal === '') {
                                $batchQuery->where(function($q) {
                                    $q->whereNull('batch')->orWhere('batch', '');
                                });
                            } else {
                                $batchQuery->where('batch', $batchVal);
                            }

                            // Only consider records up to and including this transaction time (and id tie-breaker)
                            $batchQuery->where(function($q) use ($row) {
                                $q->where('tanggal', '<', $row->created_at)
                                  ->orWhere(function($q2) use ($row) {
                                      $q2->where('tanggal', '=', $row->created_at)->where('id', '<=', isset($row->id) ? $row->id : 0);
                                  });
                            });

                            $latest = $batchQuery->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->first();
                            if ($latest && isset($latest->stok_setelah)) {
                                $totalAll += (float)$latest->stok_setelah;
                            }
                        }
                    } catch (\Exception $e) {
                        // In case of any error, fall back to using the per-row stok_setelah only
                        $totalAll = (float)$row->stok_setelah;
                    }

                    // Format stok setelah (per-batch) and also show total across all batches
                    // Use centered layout and small muted label so alignment matches other cells
                    $stokFormatted = '<div class="d-flex flex-column align-items-center">';
                    $stokFormatted .= '<div><span class="badge badge-secondary">' . number_format($row->stok_setelah, 0) . '</span></div>';
                    $stokFormatted .= '<div class="mt-1 text-center">';
                    $stokFormatted .= '<small class="text-muted d-block">Total semua<br/>batch</small>';
                    $stokFormatted .= '<span class="badge badge-dark mt-1">' . number_format($totalAll, 0) . '</span>';
                    $stokFormatted .= '</div>';
                    $stokFormatted .= '</div>';
                    
                    // Format keterangan dengan gudang info (make gudang name more prominent and escape it)
                    $infoFormatted = $row->keterangan;
                    if ($row->nama_gudang) {
                        $infoFormatted .= '<br><div class="text-info"><i class="fas fa-warehouse"></i> <strong>' . e($row->nama_gudang) . '</strong></div>';
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
