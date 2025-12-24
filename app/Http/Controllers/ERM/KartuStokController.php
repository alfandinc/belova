<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

            $rows[] = [$obat->nama, (float)$totalAll];
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

        // Efficient computation of stok_terakhir for all obat in one query:
        // For each (obat_id, batch) take the row with MAX(id) where tanggal <= cutoff, then sum stok_setelah per obat.
        // Subquery: get max id per obat_id+batch up to cutoff
        $sub = DB::table('erm_kartu_stok')
            ->select(DB::raw('obat_id, batch, MAX(id) as max_id'))
            ->where('tanggal', '<=', $cutoff)
            ->groupBy('obat_id', 'batch');

        // Join back to get stok_setelah values for those max_id rows
    $stokRows = DB::table('erm_kartu_stok as ks')
            ->joinSub($sub, 's', function($join) {
                $join->on('ks.id', '=', 's.max_id');
            })
            ->select('ks.obat_id', DB::raw('SUM(ks.stok_setelah) as stok_terakhir'))
            ->groupBy('ks.obat_id')
            ->get();

        // Map obat_id => stok_terakhir
        $stokMap = [];
        foreach ($stokRows as $r) {
            $stokMap[$r->obat_id] = (float)$r->stok_terakhir;
        }

        // Add obat with transactions
        foreach ($kartuStokData as $data) {
            $stokTerakhir = isset($stokMap[$data->obat_id]) ? $stokMap[$data->obat_id] : 0;
            $btnDetail = '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$data->obat_id.'">Detail</button>';
            $btnAnalytics = ' <button class="btn btn-outline-info btn-sm btn-analytics ml-2" title="Analytics" data-obat-id="'.$data->obat_id.'"><i class="fas fa-chart-line"></i> Analytics</button>';
            $result[] = [
                'nama_obat' => $data->nama_obat,
                'masuk' => (float)$data->total_masuk,
                'keluar' => (float)$data->total_keluar,
                'stok_terakhir' => $stokTerakhir,
                'detail' => $btnDetail . $btnAnalytics
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
                $stokTerakhir = isset($stokMap[$obat->obat_id]) ? $stokMap[$obat->obat_id] : 0;
                $btnDetail = '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$obat->obat_id.'">Detail</button>';
                $btnAnalytics = ' <button class="btn btn-outline-info btn-sm btn-analytics ml-2" title="Analytics" data-obat-id="'.$obat->obat_id.'"><i class="fas fa-chart-line"></i> Analytics</button>';
                $result[] = [
                    'nama_obat' => $obat->nama_obat,
                    'masuk' => 0.0,
                    'keluar' => 0.0,
                    'stok_terakhir' => $stokTerakhir,
                    'detail' => $btnDetail . $btnAnalytics
                ];
            }
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Analytics: monthly breakdown of masuk (pembelian) and keluar (penjualan) for an obat
     * Returns an HTML fragment suitable for injecting into a modal.
     */
    public function analytics(Request $request)
    {
        try {
            $obatId = $request->input('obat_id');
            $start = $request->input('start');
            $end = $request->input('end');

            if (!$obatId) {
                return '<div class="text-danger">Parameter obat_id diperlukan.</div>';
            }

            // default to last 12 months if no range provided
            if (!$start || !$end) {
                $endDt = Carbon::now()->endOfMonth();
                $startDt = Carbon::now()->subMonths(11)->startOfMonth();
            } else {
                // If user provided a range, expand to full calendar years spanning the range
                // so the analytics table shows all months in those years (Jan..Dec)
                $startDt = Carbon::parse($start)->startOfYear();
                $endDt = Carbon::parse($end)->endOfYear();
            }

            $startStr = $startDt->format('Y-m-d H:i:s');
            $endStr = $endDt->format('Y-m-d H:i:s');

            $raw = DB::table('erm_kartu_stok')
                ->select(DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as ym"), 'tipe', DB::raw('SUM(qty) as total'))
                ->where('obat_id', $obatId)
                ->whereBetween('tanggal', [$startStr, $endStr])
                ->groupBy('ym', 'tipe')
                ->orderBy('ym')
                ->get();

            // pivot
            $map = [];
            foreach ($raw as $r) {
                $ym = $r->ym;
                if (!isset($map[$ym])) $map[$ym] = ['masuk' => 0, 'keluar' => 0];
                if ($r->tipe === 'masuk') {
                    $map[$ym]['masuk'] = (float)$r->total;
                } else {
                    $map[$ym]['keluar'] = (float)$r->total;
                }
            }

            // build list of months between start and end
            $months = [];
            $cursor = $startDt->copy();
            while ($cursor->lte($endDt)) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            $totalMasuk = 0; $totalKeluar = 0;
            foreach ($months as $m) {
                $totalMasuk += ($map[$m]['masuk'] ?? 0);
                $totalKeluar += ($map[$m]['keluar'] ?? 0);
            }

            $avgKeluar = count($months) ? round($totalKeluar / count($months), 2) : 0;

            // render HTML
            $html = '<div class="mb-3">';
            $html .= '<div class="d-flex justify-content-between align-items-center">';
            $html .= '<div><strong>Periode:</strong> ' . e($startDt->format('d/m/Y')) . ' - ' . e($endDt->format('d/m/Y')) . '</div>';
            $html .= '<div><small class="text-muted">Total Masuk: <strong>' . number_format($totalMasuk, 2) . '</strong> &nbsp;|&nbsp; Total Keluar: <strong>' . number_format($totalKeluar, 2) . '</strong> &nbsp;|&nbsp; Avg Keluar/bln: <strong>' . number_format($avgKeluar, 2) . '</strong></small></div>';
            $html .= '</div></div>';

            if (empty($months)) {
                $html .= '<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Tidak ada data untuk periode ini.</div>';
                return $html;
            }

            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-sm table-bordered">';
            $html .= '<thead class="thead-light"><tr><th>Bulan</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Net (Masuk - Keluar)</th></tr></thead><tbody>';

            foreach ($months as $m) {
                $label = Carbon::createFromFormat('Y-m', $m)->format('M Y');
                $masuk = $map[$m]['masuk'] ?? 0;
                $keluar = $map[$m]['keluar'] ?? 0;
                $net = $masuk - $keluar;
                $html .= '<tr>';
                $html .= '<td>' . e($label) . '</td>';
                $html .= '<td class="text-right">' . number_format($masuk, 2) . '</td>';
                $html .= '<td class="text-right">' . number_format($keluar, 2) . '</td>';
                $html .= '<td class="text-right">' . number_format($net, 2) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';

            // Prepare chart canvas and data payload (labels + masuk/keluar series)
            $labels = [];
            $masukSeries = [];
            $keluarSeries = [];
            foreach ($months as $m) {
                $labels[] = Carbon::createFromFormat('Y-m', $m)->format('M Y');
                $masukSeries[] = $map[$m]['masuk'] ?? 0;
                $keluarSeries[] = $map[$m]['keluar'] ?? 0;
            }

            // Canvas for chart
            $html .= '<div class="mt-3"><canvas id="analyticsChart" height="120"></canvas></div>';

            // suggestion note for fast/slow moving - simple hint
            $html .= '<div class="mt-3"><small class="text-muted">Catatan: Gunakan angka "Keluar" rata-rata per bulan sebagai indikasi pergerakan. Obat dengan rata-rata keluar tinggi relatif terhadap stok perlu dipertimbangkan sebagai fast-moving.</small></div>';

            // Embed JSON payload for client-side chart rendering
            $payload = json_encode(['labels' => $labels, 'masuk' => $masukSeries, 'keluar' => $keluarSeries, 'totalMasuk' => $totalMasuk, 'totalKeluar' => $totalKeluar, 'avgKeluar' => $avgKeluar]);
            $html .= "<script type=\"application/json\" id=\"analytics-data\">" . $payload . "</script>";

            return $html;
        } catch (\Exception $e) {
            return '<div class="text-danger">ERROR: ' . e($e->getMessage()) . '</div>';
        }
    }

        public function detail(Request $request)
        {
            try {
                $obatId = $request->input('obat_id');
                $start = $request->input('start');
                $end = $request->input('end');

                // Default date range: current month if not provided
                if (!$start || !$end) {
                    $startDt = Carbon::now()->startOfMonth();
                    $endDt = Carbon::now()->endOfMonth();
                } else {
                    try {
                        $startDt = Carbon::parse($start)->startOfDay();
                    } catch (\Exception $e) {
                        $startDt = Carbon::now()->startOfMonth();
                    }
                    try {
                        $endDt = Carbon::parse($end)->endOfDay();
                    } catch (\Exception $e) {
                        $endDt = Carbon::now()->endOfMonth();
                    }
                }

                $startStr = $startDt->format('Y-m-d H:i:s');
                $endStr = $endDt->format('Y-m-d H:i:s');
                $startYmd = $startDt->format('Y-m-d');
                $endYmd = $endDt->format('Y-m-d');

                // Get transactions from kartu_stok table (more accurate and complete)
                $kartuStokQuery = DB::table('erm_kartu_stok as ks')
                    ->leftJoin('erm_gudang as g', 'ks.gudang_id', '=', 'g.id')
                    ->where('ks.obat_id', $obatId);

                // If a gudang is specified, limit kartu stok to that gudang only
                $gudangId = $request->input('gudang_id');
                if ($gudangId !== null && $gudangId !== '') {
                    $kartuStokQuery->where('ks.gudang_id', $gudangId);
                }
                    
                // Always filter by the determined date range
                $kartuStokQuery->whereBetween('ks.tanggal', [$startStr, $endStr]);
                
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

                // Get distinct batches for this obat (and gudang if provided) to compute totals across batches
                $batchesQuery = DB::table('erm_kartu_stok')
                    ->where('obat_id', $obatId);
                if ($gudangId !== null && $gudangId !== '') {
                    $batchesQuery->where('gudang_id', $gudangId);
                }
                $batches = $batchesQuery->select('batch')->distinct()->pluck('batch');

                foreach ($kartuStokData as $row) {
                    // Format reference info dengan nomor dokumen asli
                    $refInfo = '';
                    if ($row->ref_type && $row->ref_id) {
                        $refTypeFormatted = ucfirst(str_replace('_', ' ', $row->ref_type));
                        $refNumber = '';
                        $refNumberHtml = null; // when set, contains pre-rendered HTML for reference (no further escaping)
                        
                        // Get actual document number based on ref_type
                        try {
                            switch ($row->ref_type) {
                                case 'invoice_penjualan':
                                case 'invoice_return':
                                    $invoice = DB::table('finance_invoices as fi')
                                        ->leftJoin('erm_visitations as v', 'fi.visitation_id', '=', 'v.id')
                                        ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                                        ->where('fi.id', $row->ref_id)
                                        ->select('fi.invoice_number', 'p.nama as pasien_nama')
                                        ->first();
                                    if ($invoice) {
                                        // keep a plain value for fallback but prepare HTML that includes pasien name
                                        $refNumber = $invoice->invoice_number;
                                        $refNumberHtml = '<strong>' . e($invoice->invoice_number) . '</strong>';
                                        if (!empty($invoice->pasien_nama)) {
                                            $refNumberHtml .= '<br><small class="text-dark font-weight-bold">' . e($invoice->pasien_nama) . '</small>';
                                        }
                                    } else {
                                        $refNumber = '#' . $row->ref_id;
                                    }
                                    break;
                                    
                                case 'faktur_pembelian':
                                    $faktur = DB::table('erm_fakturbeli')->where('id', $row->ref_id)->first();
                                    $refNumber = $faktur ? $faktur->no_faktur : '#' . $row->ref_id;
                                    break;
                                    
                                case 'mutasi_gudang':
                                    $mutasi = DB::table('erm_mutasi_gudang')->where('id', $row->ref_id)->first();
                                    $refNumber = $mutasi ? $mutasi->nomor_mutasi : '#' . $row->ref_id;
                                    break;

                                    case 'retur_pembelian':
                                        $retur = DB::table('finance_retur_pembelian')->where('id', $row->ref_id)->first();
                                        $refNumber = $retur ? $retur->retur_number : '#' . $row->ref_id;
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
                        
                        // Make the reference text clickable for certain document types
                        $allowedViewTypes = ['faktur_pembelian', 'invoice_penjualan', 'invoice_return', 'retur_pembelian'];
                        if (isset($refNumberHtml) && $refNumberHtml !== null) {
                            $refNumberEsc = $refNumberHtml;
                        } else {
                            $refNumberEsc = '<strong>' . e($refNumber) . '</strong>';
                        }
                        if (in_array($row->ref_type, $allowedViewTypes)) {
                            // Wrap the reference text in a link that existing JS (selector `.btn-view-ref`) can handle
                            $refNumberEsc = '<a href="#" class="btn-view-ref" data-ref-type="' . e($row->ref_type) . '" data-ref-id="' . e($row->ref_id) . '">' . $refNumberEsc . '</a>';
                        }
                        $refInfo = '<small class="text-muted">' . $refTypeFormatted . '</small><br>' . $refNumberEsc;
                    } else {
                        $refInfo = '<span class="text-muted">-</span>';
                    }
                    
                    // Format tanggal yang lebih baik
                    $tanggalFormatted = date('d/m/Y H:i', strtotime($row->created_at));
                    
                    // Compute total stock across all batches for the SAME GUDANG as this transaction
                    // Use a single aggregated SQL: pick the latest kartu_stok row per (obat_id,batch)
                    // up to this transaction time (and same gudang), then sum their stok_setelah.
                    $totalAll = 0;
                    try {
                        $sub = DB::table('erm_kartu_stok')
                            ->select(DB::raw('MAX(id) as max_id'))
                            ->where('obat_id', $obatId)
                            ->where('tanggal', '<=', $row->created_at);

                        if (isset($row->gudang_id)) {
                            $sub->where('gudang_id', $row->gudang_id);
                        } else {
                            $sub->whereNull('gudang_id');
                        }

                        // group by batch so we take latest per-batch
                        $sub->groupBy('batch');

                        $sumRow = DB::table('erm_kartu_stok as ks2')
                            ->joinSub($sub, 's2', function($join) {
                                $join->on('ks2.id', '=', 's2.max_id');
                            })
                            ->select(DB::raw('SUM(ks2.stok_setelah) as total_all'))
                            ->first();

                        $totalAll = $sumRow && isset($sumRow->total_all) ? (float)$sumRow->total_all : 0;
                    } catch (\Exception $e) {
                        // fallback to per-row value
                        $totalAll = (float)$row->stok_setelah;
                    }

                    // Prefer to display live values from `erm_obat_stok_gudang` (current gudang stock)
                    // Only include batches that exist and have stok > 0. Fall back to kartu_stok.stok_setelah when missing.
                    try {
                        $perBatchStok = null;
                        if (isset($row->gudang_id)) {
                            $stokQuery = DB::table('erm_obat_stok_gudang')
                                ->where('obat_id', $obatId)
                                ->where('gudang_id', $row->gudang_id);
                        } else {
                            $stokQuery = DB::table('erm_obat_stok_gudang')
                                ->where('obat_id', $obatId)
                                ->whereNull('gudang_id');
                        }

                        if ($row->batch === null || $row->batch === '') {
                            $stokQuery->where(function($q) { $q->whereNull('batch')->orWhere('batch', ''); });
                        } else {
                            $stokQuery->where('batch', $row->batch);
                        }

                        $stokRow = $stokQuery->first();
                        if ($stokRow && isset($stokRow->stok)) {
                            $perBatchStok = (float)$stokRow->stok;
                        }

                        // Total across batches for same gudang: sum stok where stok > 0
                        if (isset($row->gudang_id)) {
                            $totalAllRow = DB::table('erm_obat_stok_gudang')
                                ->where('obat_id', $obatId)
                                ->where('gudang_id', $row->gudang_id)
                                ->where('stok', '>', 0)
                                ->select(DB::raw('SUM(stok) as total_all'))
                                ->first();
                        } else {
                            $totalAllRow = DB::table('erm_obat_stok_gudang')
                                ->where('obat_id', $obatId)
                                ->whereNull('gudang_id')
                                ->where('stok', '>', 0)
                                ->select(DB::raw('SUM(stok) as total_all'))
                                ->first();
                        }

                        $totalAllFromGudang = $totalAllRow && isset($totalAllRow->total_all) ? (float)$totalAllRow->total_all : 0;
                    } catch (\Exception $e) {
                        $perBatchStok = null;
                        $totalAllFromGudang = 0;
                    }

                    // Prefer the historical `stok_setelah` value recorded on the kartu_stok row
                    // because that represents the stock AFTER that transaction. Only fall
                    // back to the current gudang stock table when the historical value
                    // is missing (for example, legacy rows without stok_setelah).
                    $displayPerBatch = isset($row->stok_setelah) ? (float)$row->stok_setelah : (isset($perBatchStok) ? $perBatchStok : 0);

                    // For the "total all batches" value prefer the historical computed
                    // total ($totalAll) which sums `stok_setelah` up to the transaction
                    // moment. If that is not available (zero/unknown), fall back to the
                    // current gudang aggregate ($totalAllFromGudang).
                    $displayTotalAll = ($totalAll > 0) ? $totalAll : ($totalAllFromGudang > 0 ? $totalAllFromGudang : 0);

                    // Format stok setelah
                    // For stok_opname rows, show the TOTAL across all batches as the main badge (snapshot/aggregate view)
                    // For other rows, show the per-batch stok as main badge and total across batches as secondary
                        if ($row->ref_type === 'stok_opname') {
                        $stokFormatted = '<div class="d-flex flex-column align-items-center">';
                        $stokFormatted .= '<div><span class="badge badge-dark">' . number_format($displayTotalAll, 2) . '</span></div>';
                        $stokFormatted .= '<div class="mt-1 text-center">';
                        $stokFormatted .= '<small class="text-muted d-block">Total semua<br/>batch</small>';
                        $stokFormatted .= '</div>';
                        $stokFormatted .= '</div>';
                    } else {
                        $stokFormatted = '<div class="d-flex flex-column align-items-center">';
                        $stokFormatted .= '<div><span class="badge badge-secondary">' . number_format($displayPerBatch, 2) . '</span></div>';
                        $stokFormatted .= '<div class="mt-1 text-center">';
                        $stokFormatted .= '<small class="text-muted d-block">Total semua<br/>batch</small>';
                        $stokFormatted .= '<span class="badge badge-dark mt-1">' . number_format($displayTotalAll, 2) . '</span>';
                        $stokFormatted .= '</div>';
                        $stokFormatted .= '</div>';
                    }
                    
                    // Format keterangan (do NOT include per-row gudang name; summary badge covers selected gudang)
                    $infoFormatted = $row->keterangan;
                    
                    // For stok_opname rows, display all batches in the same gudang that have stok > 0
                    // so user can see batch-level details even when a specific batch didn't change.
                    try {
                        $batchDisplay = '';
                        if ($row->ref_type === 'stok_opname') {
                            $batchQuery = DB::table('erm_obat_stok_gudang')
                                ->where('obat_id', $obatId)
                                ->where('stok', '>', 0);

                            if (isset($row->gudang_id)) {
                                $batchQuery->where('gudang_id', $row->gudang_id);
                            } else {
                                $batchQuery->whereNull('gudang_id');
                            }

                            $batchRows = $batchQuery->select('batch', 'stok')->orderBy('batch')->get();

                            if ($batchRows && $batchRows->count() > 0) {
                                $parts = [];
                                foreach ($batchRows as $b) {
                                    $batchName = $b->batch ? e($b->batch) : '<em>no-batch</em>';
                                    $parts[] = '<code>' . $batchName . '</code> <small class="text-muted">(' . number_format($b->stok, 2) . ')</small>';
                                }
                                $batchDisplay = implode('<br>', $parts);
                            } else {
                                $batchDisplay = $row->batch ? '<code>' . e($row->batch) . '</code>' : '<span class="text-muted">-</span>';
                            }
                        } else {
                            $batchDisplay = $row->batch ? '<code>' . e($row->batch) . '</code>' : '<span class="text-muted">-</span>';
                        }
                    } catch (\Exception $e) {
                        $batchDisplay = $row->batch ? '<code>' . e($row->batch) . '</code>' : '<span class="text-muted">-</span>';
                    }

                    // Put jumlah into either 'masuk' or 'keluar' column depending on tipe
                    $masukVal = $row->tipe === 'masuk' ? (float)$row->jumlah : 0.0;
                    $keluarVal = $row->tipe === 'keluar' ? (float)$row->jumlah : 0.0;

                    // Determine row class: stok_opname (warning) takes precedence,
                    // otherwise green for masuk and red for keluar.
                    $rowClass = '';
                    if (isset($row->ref_type) && $row->ref_type === 'stok_opname') {
                        $rowClass = 'table-warning';
                    } elseif (isset($row->tipe) && $row->tipe === 'keluar') {
                        $rowClass = 'table-danger';
                    } elseif (isset($row->tipe) && $row->tipe === 'masuk') {
                        $rowClass = 'table-success';
                    }

                    // Format numeric display: bold non-zero values
                    $masukDisplay = number_format($masukVal, 2);
                    $keluarDisplay = number_format($keluarVal, 2);
                    if ((float)$masukVal != 0.0) {
                        $masukDisplay = '<strong>' . $masukDisplay . '</strong>';
                    }
                    if ((float)$keluarVal != 0.0) {
                        $keluarDisplay = '<strong>' . $keluarDisplay . '</strong>';
                    }

                    $rows[] = [
                        'tanggal' => $tanggalFormatted,
                        'referensi' => $refInfo,
                        'masuk' => $masukDisplay,
                        'keluar' => $keluarDisplay,
                        'keterangan' => $infoFormatted,
                        'row_class' => $rowClass
                    ];
                }

                // Build summary block (obat name, stok, nilai stok) to display above the table
                $summaryHtml = '';
                try {
                    $obat = Obat::find($obatId);
                    $obatNama = $obat ? $obat->nama : '';
                    $gudangId = $request->input('gudang_id');
                    if ($gudangId) {
                        $stokSum = DB::table('erm_obat_stok_gudang')
                            ->where('obat_id', $obatId)
                            ->where('gudang_id', $gudangId)
                            ->select(DB::raw('COALESCE(SUM(stok),0) as stok_sum'))
                            ->value('stok_sum');
                    } else {
                        $stokSum = DB::table('erm_obat_stok_gudang')
                            ->where('obat_id', $obatId)
                            ->select(DB::raw('COALESCE(SUM(stok),0) as stok_sum'))
                            ->value('stok_sum');
                    }
                    $stokSum = $stokSum !== null ? (float)$stokSum : 0.0;
                    $hpp = ($obat && isset($obat->hpp)) ? (float)$obat->hpp : 0.0;
                    $nilai = $stokSum * $hpp;
                    $satuan = $obat && isset($obat->satuan) ? trim($obat->satuan) : '';
                    if ($satuan !== '') {
                        $satuan = function_exists('mb_strtolower') ? mb_strtolower($satuan) : strtolower($satuan);
                    }

                    // Name on top-left, gudang on top-right, then a horizontal row with Stok and Nilai Stok below
                    // Get gudang name (if provided)
                    $gudangName = '';
                    try {
                        $gudangIdLookup = $request->input('gudang_id');
                        if ($gudangIdLookup !== null && $gudangIdLookup !== '') {
                            $gudangName = DB::table('erm_gudang')->where('id', $gudangIdLookup)->value('nama') ?? '';
                        }
                    } catch (\Exception $e) {
                        $gudangName = '';
                    }

                    $summaryHtml .= '<div class="mb-3 p-3 border rounded">';
                    // Render gudang name as a colored badge (different color per gudang)
                    $gudangBadgeHtml = '';
                    if ($gudangName) {
                        $key = function_exists('mb_strtolower') ? mb_strtolower(trim($gudangName)) : strtolower(trim($gudangName));
                        $badgeMap = [
                            'apotek farmasi' => 'badge-primary',
                            'gudang utama farmasi' => 'badge-success',
                            'kabin gigi' => 'badge-info',
                            'kabin beautician' => 'badge-warning',
                            'kabin penyakit dalam' => 'badge-danger',
                        ];
                        $badgeClass = isset($badgeMap[$key]) ? $badgeMap[$key] : 'badge-secondary';
                        $gudangBadgeHtml = '<span class="badge ' . $badgeClass . '">' . e($gudangName) . '</span>';
                    }

                    // Layout: left = obat name + stok/nilai, right = gudang badge + daterange under it
                    $summaryHtml .= '<div class="d-flex justify-content-between">';
                    // Left column: name and stock/value
                    $summaryHtml .= '<div>';
                    $displayName = e($obatNama);
                    if ($satuan) {
                        $displayName .= ' (' . e($satuan) . ')';
                    }
                    $summaryHtml .= '<strong>' . $displayName . '</strong>';
                    $summaryHtml .= '<div class="mt-2">';
                    $summaryHtml .= '<span class="mr-4">Stok <strong>' . number_format($stokSum, 2) . ($satuan ? ' ' . e($satuan) : '') . '</strong></span>';
                    $summaryHtml .= '<span>Nilai Stok <strong>Rp ' . number_format($nilai, 0, ',', '.') . '</strong></span>';
                    $summaryHtml .= '</div>'; // .mt-2
                    $summaryHtml .= '</div>';

                    // Right column: badge on top, daterange picker underneath
                    $summaryHtml .= '<div class="text-right">';
                    $summaryHtml .= $gudangBadgeHtml;
                    $summaryHtml .= '<div class="mt-2"><input type="text" id="kartu-detail-range-'.intval($obatId).'" class="form-control form-control-sm" value="'.e($startYmd).' - '.e($endYmd).'" readonly /></div>';
                    $summaryHtml .= '</div>';

                    $summaryHtml .= '</div>'; // .d-flex
                    $summaryHtml .= '</div>'; // outer rounded
                } catch (\Exception $e) {
                    $summaryHtml = '';
                }

                // Small script to initialize daterangepicker and reload detail on apply
                $script = '<script>';
                $script .= '$(function(){';
                $script .= 'var input = $("#kartu-detail-range-'.intval($obatId).'");';
                $script .= 'try {';
                $script .= 'input.daterangepicker({ autoUpdateInput: true, locale: { format: "YYYY-MM-DD" }, startDate: "'.e($startYmd).'", endDate: "'.e($endYmd).'" });';
                $script .= 'input.on("apply.daterangepicker", function(ev, picker) {';
                $script .= 'var s = picker.startDate.format("YYYY-MM-DD"); var en = picker.endDate.format("YYYY-MM-DD");';
                $script .= '$.ajax({ url: "'.route('erm.kartustok.detail').'", type: "GET", data: { obat_id: '.intval($obatId).', gudang_id: '.($gudangId ? intval($gudangId) : '""').', start: s, end: en }, success: function(resp) {';
                $script .= '$("#kartu-stok-panel").html(resp); $("[data-toggle=\\"tooltip\\"]").tooltip(); feather.replace(); }, error: function() { alert("Gagal memuat kartu stok."); } });';
                $script .= '});';
                $script .= '} catch(e) { /* daterangepicker not available */ }';
                $script .= '});';
                $script .= '</script>';

                // Enhanced HTML table with better styling
                $html = $summaryHtml . $script;
                $html .= '<div class="table-responsive">';
                $html .= '<table class="table table-sm table-bordered table-striped table-hover">';
                $html .= '<thead class="thead-light">';
                $html .= '<tr>';
                $html .= '<th width="15%">Tanggal</th>';
                $html .= '<th width="35%">Referensi</th>';
                $html .= '<th width="10%" class="text-right">Masuk</th>';
                $html .= '<th width="10%" class="text-right">Keluar</th>';
                $html .= '<th width="30%">Keterangan</th>';
                $html .= '</tr>';
                $html .= '</thead><tbody>';
                
                    if (count($rows) === 0) {
                    $html .= '<tr><td colspan="6" class="text-center text-muted py-4">';
                    $html .= '<i class="fas fa-inbox fa-2x mb-2 d-block"></i>';
                    $html .= 'Tidak ada transaksi ditemukan dalam periode ini.';
                    $html .= '</td></tr>';
                } else {
                    foreach ($rows as $row) {
                        $trClass = isset($row['row_class']) && $row['row_class'] ? ' class="' . $row['row_class'] . '"' : '';
                        $html .= '<tr' . $trClass . '>';
                        $html .= '<td>' . ($row['tanggal'] ?? '') . '</td>';
                        $html .= '<td>' . ($row['referensi'] ?? '') . '</td>';
                        $html .= '<td class="text-right">' . ($row['masuk'] ?? '0.00') . '</td>';
                        $html .= '<td class="text-right">' . ($row['keluar'] ?? '0.00') . '</td>';
                        $html .= '<td>' . ($row['keterangan'] ?? '') . '</td>';
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
