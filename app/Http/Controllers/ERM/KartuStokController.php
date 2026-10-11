<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ERM\Obat;

/**
 * Kartu stok balances ("Sisa Stok") are derived from qty, anchored on the current stock in
 * erm_obat_stok_gudang: saldo after a row = stok now - every movement after that row.
 * The stored stok_setelah is a per-batch value and is wrong on part of the legacy data, so it
 * is not read here. Stok opname marker rows (tipe stok_opname, qty 0) are not movements.
 */
class KartuStokController extends Controller
{
    private const SIGNED_QTY = "CASE WHEN tipe = 'masuk' THEN qty WHEN tipe = 'keluar' THEN -qty ELSE 0 END";

    // Reference types with a document preview: ref_type => [route, mode]; iframe = printable page, html = partial view
    private const REF_PREVIEW = [
        'invoice_penjualan' => ['finance.invoice.print', 'iframe'],
        'invoice_return' => ['finance.invoice.print', 'iframe'],
        'faktur_pembelian' => ['erm.fakturbeli.print', 'iframe'],
        'retur_pembelian' => ['finance.retur-pembelian.print', 'iframe'],
        'mutasi_gudang' => ['erm.mutasi-gudang.print', 'iframe'],
        'mutasi_stok' => ['erm.mutasi-stok.print', 'iframe'],
        'mutasi_stok_cancel' => ['erm.mutasi-stok.print', 'iframe'],
        'retur' => ['erm.fakturretur.show', 'html'],
    ];

    // ref_type => [table, number column, prefix]
    private const REF_TABLES = [
        'faktur_pembelian' => ['erm_fakturbeli', 'no_faktur', ''],
        'obat_hibah' => ['erm_obat_hibah', 'nomor_hibah', ''],
        'mutasi_gudang' => ['erm_mutasi_gudang', 'nomor_mutasi', ''],
        'mutasi_stok' => ['erm_mutasi_stok', 'nomor_mutasi', ''],
        'mutasi_stok_cancel' => ['erm_mutasi_stok', 'nomor_mutasi', 'BATAL-'],
        'retur_pembelian' => ['finance_retur_pembelian', 'retur_number', ''],
        'retur' => ['erm_fakturretur', 'no_retur', ''],
    ];

    public function index()
    {
        return view('erm.kartu_stok.index');
    }

    /**
     * Export summary of stok terakhir (total per obat across all gudang and batches at the end date)
     */
    public function exportStokTerakhir(Request $request)
    {
        [, $cutoff] = $this->periode($request->input('start'), $request->input('end'), false);
        $stokMap = $this->stokPerObatPada($cutoff);

        $rows = [['Nama Obat', 'Total Stok Terakhir']];
        foreach (DB::table('erm_obat')->select('id', 'nama')->orderBy('nama')->get() as $obat) {
            $rows[] = [$obat->nama, round($stokMap[$obat->id] ?? 0, 4)];
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
        $hasRange = $request->input('start') && $request->input('end');
        [$startStr, $endStr] = $this->periode($request->input('start'), $request->input('end'), false);

        $obats = DB::table('erm_obat')->select('id', 'nama')->orderBy('nama')->get();

        $rows = [];
        $rows[] = ['Nama Obat', 'Tipe', 'Tanggal', 'Jumlah', 'Referensi', 'Sisa Stok Batch', 'Sisa Stok Gudang', 'Batch', 'Keterangan', 'Gudang'];

        foreach ($obats as $obat) {
            $rows[] = ["-- {$obat->nama} --", '', '', '', '', '', '', '', '', ''];

            $transactions = DB::table('erm_kartu_stok as ks')
                ->leftJoin('erm_gudang as g', 'ks.gudang_id', '=', 'g.id')
                ->where('ks.obat_id', $obat->id)
                ->when($hasRange, function ($q) use ($startStr, $endStr) {
                    $q->whereBetween('ks.tanggal', [$startStr, $endStr]);
                })
                ->select('ks.id', 'ks.gudang_id', 'ks.tipe', 'ks.qty', 'ks.tanggal', 'ks.ref_type', 'ks.ref_id', 'ks.batch', 'ks.keterangan', 'g.nama as nama_gudang')
                ->orderBy('ks.tanggal', 'desc')
                ->orderBy('ks.id', 'desc')
                ->get();

            if ($transactions->isEmpty()) {
                $rows[] = ['', 'Tidak ada transaksi', '', '', '', '', '', '', '', ''];
                continue;
            }

            $this->hitungSaldo($obat->id, null, $transactions, $endStr);
            $refs = $this->refNumbers($transactions);

            foreach ($transactions as $t) {
                $rows[] = [
                    $obat->nama,
                    $t->tipe,
                    $t->tanggal,
                    (float) $t->qty,
                    ($t->ref_type && $t->ref_id) ? $this->refNumber($refs, $t)['nomor'] : '',
                    round($t->sisa_batch, 4),
                    round($t->sisa_gudang, 4),
                    $t->batch,
                    $t->keterangan,
                    $t->nama_gudang,
                ];
            }
        }

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
        $hasRange = $request->input('start') && $request->input('end');
        [$startStr, $endStr] = $this->periode($request->input('start'), $request->input('end'), false);
        $onlyWithTransactions = $request->input('only_with_transactions', false);

        // Masuk/keluar per obat in the period, one aggregated query
        $kartuStokQuery = DB::table('erm_kartu_stok as ks')
            ->leftJoin('erm_obat as o', 'ks.obat_id', '=', 'o.id')
            ->select(
                'ks.obat_id',
                'o.nama as nama_obat',
                DB::raw('SUM(CASE WHEN ks.tipe = "masuk" THEN ks.qty ELSE 0 END) as total_masuk'),
                DB::raw('SUM(CASE WHEN ks.tipe = "keluar" THEN ks.qty ELSE 0 END) as total_keluar')
            );

        if ($hasRange) {
            $kartuStokQuery->whereBetween('ks.tanggal', [$startStr, $endStr]);
        }

        $kartuStokData = $kartuStokQuery
            ->groupBy('ks.obat_id', 'o.nama')
            ->orderBy('o.nama')
            ->get()
            ->keyBy('obat_id');

        // Stok terakhir = stok at the end of the period (all gudang)
        $stokMap = $this->stokPerObatPada($endStr);

        $result = [];

        foreach ($kartuStokData as $data) {
            $btnDetail = '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$data->obat_id.'">Detail</button>';
            $btnAnalytics = ' <button class="btn btn-outline-info btn-sm btn-analytics ml-2" title="Analytics" data-obat-id="'.$data->obat_id.'"><i class="fas fa-chart-line"></i> Analytics</button>';
            $result[] = [
                'nama_obat' => $data->nama_obat,
                'masuk' => (float)$data->total_masuk,
                'keluar' => (float)$data->total_keluar,
                'stok_terakhir' => round($stokMap[$data->obat_id] ?? 0, 4),
                'detail' => $btnDetail . $btnAnalytics
            ];
        }

        // Only add obat without transactions if not filtering for transactions only
        if (!$onlyWithTransactions) {
            $obatWithoutTransactions = DB::table('erm_obat as o')
                ->select('o.id as obat_id', 'o.nama as nama_obat')
                ->whereNotIn('o.id', $kartuStokData->pluck('obat_id'))
                ->orderBy('o.nama')
                ->get();

            foreach ($obatWithoutTransactions as $obat) {
                $btnDetail = '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$obat->obat_id.'">Detail</button>';
                $btnAnalytics = ' <button class="btn btn-outline-info btn-sm btn-analytics ml-2" title="Analytics" data-obat-id="'.$obat->obat_id.'"><i class="fas fa-chart-line"></i> Analytics</button>';
                $result[] = [
                    'nama_obat' => $obat->nama_obat,
                    'masuk' => 0.0,
                    'keluar' => 0.0,
                    'stok_terakhir' => round($stokMap[$obat->obat_id] ?? 0, 4),
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
            $obatId = (int) $request->input('obat_id');
            $gudangId = $request->input('gudang_id');
            $gudangId = ($gudangId === null || $gudangId === '') ? null : (int) $gudangId;

            // Default range: current month
            [$startStr, $endStr] = $this->periode($request->input('start'), $request->input('end'), true);
            $startYmd = substr($startStr, 0, 10);
            $endYmd = substr($endStr, 0, 10);

            $kartuStokData = DB::table('erm_kartu_stok as ks')
                ->where('ks.obat_id', $obatId)
                ->when($gudangId !== null, function ($q) use ($gudangId) {
                    $q->where('ks.gudang_id', $gudangId);
                })
                ->whereBetween('ks.tanggal', [$startStr, $endStr])
                ->select('ks.id', 'ks.qty', 'ks.tanggal', 'ks.tipe', 'ks.keterangan', 'ks.ref_type', 'ks.ref_id', 'ks.batch', 'ks.gudang_id')
                ->orderBy('ks.tanggal', 'desc')  // newest on top
                ->orderBy('ks.id', 'desc')
                ->get();

            $saldoAwal = $this->hitungSaldo($obatId, $gudangId, $kartuStokData, $endStr);
            $refs = $this->refNumbers($kartuStokData);

            $rowsHtml = '';
            foreach ($kartuStokData as $row) {
                $isOpname = $row->tipe === 'stok_opname';

                $refInfo = '<span class="text-muted">-</span>';
                if ($row->ref_type && $row->ref_id) {
                    $ref = $this->refNumber($refs, $row);
                    $refHtml = '<strong>' . e($ref['nomor']) . '</strong>';
                    if (!empty($ref['pasien'])) {
                        $refHtml .= '<br><small class="text-dark font-weight-bold">' . e($ref['pasien']) . '</small>';
                    }
                    if (!empty($ref['hilang'])) {
                        $refHtml .= ' <span class="badge badge-secondary" title="Dokumen sudah dihapus; nomor diambil dari keterangan kartu stok">dihapus</span>';
                    }
                    $preview = $this->refPreview($row->ref_type, $ref['id'] ?? null);
                    if ($preview) {
                        $refHtml = '<a href="' . e($preview['url']) . '" class="btn-ref-preview btn-view-ref" data-preview-url="' . e($preview['url']) . '" data-preview-mode="' . $preview['mode'] . '" data-ref-type="' . e($row->ref_type) . '" data-ref-id="' . e($ref['id']) . '" title="Lihat dokumen">' . $refHtml . ' <i class="fas fa-external-link-alt small"></i></a>';
                    }
                    $refInfo = '<small class="text-muted">' . e(ucfirst(str_replace('_', ' ', $row->ref_type))) . '</small><br>' . $refHtml;
                }
                if ($row->batch !== null && $row->batch !== '') {
                    $refInfo .= '<br><small class="text-muted">Batch</small> <code>' . e($row->batch) . '</code>';
                }

                $masuk = $row->tipe === 'masuk' ? '<strong>' . $this->angka($row->qty) . '</strong>' : '<span class="text-muted">-</span>';
                $keluar = $row->tipe === 'keluar' ? '<strong>' . $this->angka($row->qty) . '</strong>' : '<span class="text-muted">-</span>';

                $sisa = '<strong class="' . ($row->sisa_total < -0.0001 ? 'text-danger' : '') . '">' . $this->angka($row->sisa_total) . '</strong>';
                if ($isOpname) {
                    $sisa .= '<br><small class="text-muted">stok opname</small>';
                } elseif ($row->batch !== null && $row->batch !== '') {
                    $sisa .= '<br><small class="text-muted" title="Sisa stok batch ini">batch ' . $this->angka($row->sisa_batch) . '</small>';
                }

                $rowClass = $isOpname ? 'table-warning' : ($row->tipe === 'keluar' ? 'table-danger' : ($row->tipe === 'masuk' ? 'table-success' : ''));

                $rowsHtml .= '<tr' . ($rowClass ? ' class="' . $rowClass . '"' : '') . '>'
                    . '<td>' . e(date('d/m/Y H:i', strtotime($row->tanggal))) . '</td>'
                    . '<td>' . $refInfo . '</td>'
                    . '<td class="text-right">' . $masuk . '</td>'
                    . '<td class="text-right">' . $keluar . '</td>'
                    . '<td class="text-right">' . $sisa . '</td>'
                    . '<td>' . e($row->keterangan) . '</td>'
                    . '</tr>';
            }

            // Summary block: obat name, current stok and value, gudang and date range
            $obat = Obat::find($obatId);
            $stokSum = (float) DB::table('erm_obat_stok_gudang')
                ->where('obat_id', $obatId)
                ->when($gudangId !== null, function ($q) use ($gudangId) {
                    $q->where('gudang_id', $gudangId);
                })
                ->whereNull('deleted_at')
                ->sum('stok');
            $nilai = $stokSum * (float) ($obat->hpp ?? 0);
            $satuan = $obat ? trim((string) $obat->satuan_stok_label) : '';
            $satuan = $satuan !== '' ? mb_strtolower($satuan) : '';

            $gudangBadgeHtml = '';
            $gudangName = $gudangId !== null ? (string) DB::table('erm_gudang')->where('id', $gudangId)->value('nama') : '';
            if ($gudangName !== '') {
                $badgeMap = [
                    'apotek farmasi' => 'badge-primary',
                    'gudang utama farmasi' => 'badge-success',
                    'kabin gigi' => 'badge-info',
                    'kabin beautician' => 'badge-warning',
                    'kabin penyakit dalam' => 'badge-danger',
                ];
                $badgeClass = $badgeMap[mb_strtolower(trim($gudangName))] ?? 'badge-secondary';
                $gudangBadgeHtml = '<span class="badge ' . $badgeClass . '">' . e($gudangName) . '</span>';
            }

            $displayName = e($obat->nama ?? '') . ($satuan ? ' (' . e($satuan) . ')' : '');
            $html = '<div class="mb-3 p-3 border rounded">'
                . '<div class="d-flex justify-content-between">'
                    . '<div><strong>' . $displayName . '</strong>'
                        . '<div class="mt-2">'
                            . '<span class="mr-4">Stok <strong>' . number_format($stokSum, 2) . ($satuan ? ' ' . e($satuan) : '') . '</strong></span>'
                            . '<span>Nilai Stok <strong>Rp ' . number_format($nilai, 0, ',', '.') . '</strong></span>'
                        . '</div>'
                    . '</div>'
                    . '<div class="text-right">' . $gudangBadgeHtml
                        . '<div class="mt-2"><input type="text" id="kartu-detail-range-' . $obatId . '" class="form-control form-control-sm" value="' . e($startYmd) . ' - ' . e($endYmd) . '" readonly /></div>'
                    . '</div>'
                . '</div>'
                . '</div>';

            // Reload this panel when the date range changes
            $html .= '<script>$(function(){'
                . 'var input = $("#kartu-detail-range-' . $obatId . '");'
                . 'try {'
                . 'input.daterangepicker({ autoUpdateInput: true, locale: { format: "YYYY-MM-DD" }, startDate: "' . e($startYmd) . '", endDate: "' . e($endYmd) . '" });'
                . 'input.on("apply.daterangepicker", function(ev, picker) {'
                . 'var s = picker.startDate.format("YYYY-MM-DD"); var en = picker.endDate.format("YYYY-MM-DD");'
                . 'var $target = input.closest("#kartu-stok-panel, #detailModalContent");'
                . '$.ajax({ url: "' . route('erm.kartustok.detail') . '", type: "GET", data: { obat_id: ' . $obatId . ', gudang_id: ' . ($gudangId !== null ? $gudangId : '""') . ', start: s, end: en }, success: function(resp) {'
                . '$target.html(resp); $("[data-toggle=\\"tooltip\\"]").tooltip(); if (window.feather) feather.replace(); }, error: function() {'
                . 'if (window.Swal) { Swal.fire({ icon: "error", title: "Gagal", text: "Gagal memuat kartu stok." }); } else { alert("Gagal memuat kartu stok."); } } });'
                . '});'
                . '} catch(e) { /* daterangepicker not available */ }'
                . '});</script>';

            $sisaHeader = 'Sisa Stok' . ($gudangId === null ? '<br><small class="text-muted">semua gudang</small>' : '');
            $html .= '<div class="table-responsive">'
                . '<table class="table table-sm table-bordered table-striped table-hover">'
                . '<thead class="thead-light"><tr>'
                    . '<th width="14%">Tanggal</th>'
                    . '<th width="28%">Referensi</th>'
                    . '<th width="10%" class="text-right">Masuk</th>'
                    . '<th width="10%" class="text-right">Keluar</th>'
                    . '<th width="12%" class="text-right">' . $sisaHeader . '</th>'
                    . '<th width="26%">Keterangan</th>'
                . '</tr></thead><tbody>';

            if ($rowsHtml === '') {
                $html .= '<tr><td colspan="6" class="text-center text-muted py-4">'
                    . '<i class="fas fa-inbox fa-2x mb-2 d-block"></i>Tidak ada transaksi ditemukan dalam periode ini.'
                    . '</td></tr>';
            } else {
                $html .= $rowsHtml;
            }

            // Opening balance closes the list (newest on top)
            $html .= '<tr class="font-weight-bold bg-light">'
                . '<td>' . e(date('d/m/Y', strtotime($startStr))) . '</td>'
                . '<td colspan="3">Saldo awal periode</td>'
                . '<td class="text-right' . ($saldoAwal < -0.0001 ? ' text-danger' : '') . '">' . $this->angka($saldoAwal) . '</td>'
                . '<td></td>'
                . '</tr>';

            $html .= '</tbody></table></div>'
                . '<small class="text-muted d-block">Sisa Stok dihitung dari stok saat ini dikurangi mutasi sesudah transaksi tersebut, sehingga baris teratas selalu sama dengan stok nyata.</small>';

            return $html;
        } catch (\Exception $e) {
            return '<div class="text-danger">ERROR: ' . e($e->getMessage()) . '</div>';
        }
    }

    /**
     * [start, end] as 'Y-m-d H:i:s' covering whole days. Without a range: the current month when
     * $defaultMonth, otherwise everything up to now.
     */
    private function periode($start, $end, bool $defaultMonth): array
    {
        try {
            if ($start && $end) {
                return [Carbon::parse($start)->startOfDay()->format('Y-m-d H:i:s'), Carbon::parse($end)->endOfDay()->format('Y-m-d H:i:s')];
            }
            if ($end) {
                return ['1970-01-01 00:00:00', Carbon::parse($end)->endOfDay()->format('Y-m-d H:i:s')];
            }
        } catch (\Exception $e) {
            // fall through to the default range
        }

        return $defaultMonth
            ? [Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'), Carbon::now()->endOfMonth()->format('Y-m-d H:i:s')]
            : ['1970-01-01 00:00:00', Carbon::now()->format('Y-m-d H:i:s')];
    }

    /** Stok per obat (all gudang) at $cutoff: current stok minus movements after the cutoff. */
    private function stokPerObatPada(string $cutoff): array
    {
        $result = [];
        $now = DB::table('erm_obat_stok_gudang')
            ->whereNull('deleted_at')
            ->select('obat_id', DB::raw('SUM(stok) as stok'))
            ->groupBy('obat_id')
            ->pluck('stok', 'obat_id');
        foreach ($now as $obatId => $stok) {
            $result[$obatId] = (float) $stok;
        }

        $after = DB::table('erm_kartu_stok')
            ->where('tanggal', '>', $cutoff)
            ->select('obat_id', DB::raw('SUM(' . self::SIGNED_QTY . ') as qty'))
            ->groupBy('obat_id')
            ->pluck('qty', 'obat_id');
        foreach ($after as $obatId => $qty) {
            $result[$obatId] = ($result[$obatId] ?? 0) - (float) $qty;
        }

        return $result;
    }

    /**
     * Running balances for one obat. $rows are kartu stok rows of the period, newest first
     * (tanggal desc, id desc), with tipe, qty, gudang_id and batch; $endStr is the period end.
     * Sets sisa_batch, sisa_gudang (the row's gudang) and sisa_total (the whole scope) on each
     * row and returns the opening balance of the scope.
     */
    private function hitungSaldo(int $obatId, ?int $gudangId, $rows, string $endStr): float
    {
        $scope = function ($q) use ($obatId, $gudangId) {
            $q->where('obat_id', $obatId);
            if ($gudangId !== null) {
                $q->where('gudang_id', $gudangId);
            }
        };

        // Balance per gudang+batch at the end of the period
        $batch = [];
        $stokNow = DB::table('erm_obat_stok_gudang')->where($scope)->whereNull('deleted_at')
            ->select('gudang_id', 'batch', 'stok')->get();
        foreach ($stokNow as $s) {
            $key = $this->batchKey($s->gudang_id, $s->batch);
            $batch[$key] = ($batch[$key] ?? 0) + (float) $s->stok;
        }
        $after = DB::table('erm_kartu_stok')->where($scope)->where('tanggal', '>', $endStr)
            ->select('gudang_id', 'batch', DB::raw('SUM(' . self::SIGNED_QTY . ') as qty'))
            ->groupBy('gudang_id', 'batch')->get();
        foreach ($after as $a) {
            $key = $this->batchKey($a->gudang_id, $a->batch);
            $batch[$key] = ($batch[$key] ?? 0) - (float) $a->qty;
        }

        $gudang = [];
        foreach ($batch as $key => $value) {
            $gid = (int) strtok($key, '|');
            $gudang[$gid] = ($gudang[$gid] ?? 0) + $value;
        }
        $total = array_sum($gudang);

        // Walk back in time: the balance after a row, then undo that row's movement
        foreach ($rows as $row) {
            $key = $this->batchKey($row->gudang_id, $row->batch);
            $gid = (int) $row->gudang_id;
            $qty = $row->tipe === 'masuk' ? (float) $row->qty : ($row->tipe === 'keluar' ? -(float) $row->qty : 0.0);

            $row->sisa_batch = $batch[$key] ?? 0.0;
            $row->sisa_gudang = $gudang[$gid] ?? 0.0;
            $row->sisa_total = $total;

            $batch[$key] = $row->sisa_batch - $qty;
            $gudang[$gid] = $row->sisa_gudang - $qty;
            $total -= $qty;
        }

        return $total;
    }

    // NULL and '' are the same no-batch row; MySQL compares batch case-insensitively
    private function batchKey($gudangId, $batch): string
    {
        return (int) $gudangId . '|' . mb_strtolower(trim((string) $batch));
    }

    /** Document numbers for the rows' references, one query per reference type. */
    private function refNumbers($rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            if ($row->ref_type && $row->ref_id) {
                $ids[$row->ref_type][$row->ref_id] = true;
            }
        }

        $map = [];
        foreach ($ids as $type => $set) {
            $list = array_keys($set);
            try {
                if ($type === 'invoice_penjualan' || $type === 'invoice_return') {
                    $invoices = DB::table('finance_invoices as fi')
                        ->leftJoin('erm_visitations as v', 'fi.visitation_id', '=', 'v.id')
                        ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                        ->whereIn('fi.id', $list)
                        ->select('fi.id', 'fi.invoice_number', 'p.nama as pasien_nama')
                        ->get();
                    foreach ($invoices as $inv) {
                        $map[$type][$inv->id] = ['id' => $inv->id, 'nomor' => $inv->invoice_number, 'pasien' => $inv->pasien_nama];
                    }

                    // Deleted / re-created invoices: the kartu stok keterangan still holds the invoice number
                    $missing = [];
                    foreach ($rows as $row) {
                        if ($row->ref_type === $type && !isset($map[$type][$row->ref_id]) && preg_match('/INV-\d{8}-\d+/', (string) ($row->keterangan ?? ''), $m)) {
                            $missing[$row->ref_id] = $m[0];
                        }
                    }
                    if ($missing) {
                        $byNumber = DB::table('finance_invoices as fi')
                            ->leftJoin('erm_visitations as v', 'fi.visitation_id', '=', 'v.id')
                            ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                            ->whereIn('fi.invoice_number', array_values($missing))
                            ->select('fi.id', 'fi.invoice_number', 'p.nama as pasien_nama')
                            ->get()
                            ->keyBy('invoice_number');
                        foreach ($missing as $refId => $number) {
                            $inv = $byNumber->get($number);
                            $map[$type][$refId] = $inv
                                ? ['id' => $inv->id, 'nomor' => $inv->invoice_number, 'pasien' => $inv->pasien_nama]
                                : ['id' => null, 'nomor' => $number, 'pasien' => null, 'hilang' => true];
                        }
                    }
                } elseif ($type === 'stok_opname') {
                    $opnames = DB::table('erm_stok_opname')->whereIn('id', $list)->select('id', 'periode_bulan', 'periode_tahun')->get();
                    foreach ($opnames as $op) {
                        $map[$type][$op->id] = ['id' => $op->id, 'nomor' => 'OPNAME-' . $op->periode_bulan . '-' . $op->periode_tahun . ' (#' . $op->id . ')', 'pasien' => null];
                    }
                } elseif (isset(self::REF_TABLES[$type])) {
                    [$table, $column, $prefix] = self::REF_TABLES[$type];
                    foreach (DB::table($table)->whereIn('id', $list)->pluck($column, 'id') as $id => $nomor) {
                        $map[$type][$id] = ['id' => $id, 'nomor' => $prefix . $nomor, 'pasien' => null];
                    }
                }
            } catch (\Exception $e) {
                // unknown table or column: fall back to #id
            }
        }

        return $map;
    }

    private function refNumber(array $map, $row): array
    {
        return $map[$row->ref_type][$row->ref_id] ?? ['id' => null, 'nomor' => '#' . $row->ref_id, 'pasien' => null];
    }

    /** Preview link for a reference, only when the document exists and the user may open that page. */
    private function refPreview(?string $type, $id): ?array
    {
        if (!$id || !isset(self::REF_PREVIEW[$type])) {
            return null;
        }
        [$routeName, $mode] = self::REF_PREVIEW[$type];
        if (!$this->bolehBuka($routeName)) {
            return null;
        }
        // Partial views are read-only previews (no approve buttons)
        $url = route($routeName, $id) . ($mode === 'html' ? '?preview=1' : '');

        return ['url' => $url, 'mode' => $mode];
    }

    // Checks the role middleware of a named route against the current user (cached per request)
    private function bolehBuka(string $routeName): bool
    {
        static $cache = [];
        if (array_key_exists($routeName, $cache)) {
            return $cache[$routeName];
        }
        $route = app('router')->getRoutes()->getByName($routeName);
        $user = auth()->user();
        if (!$route || !$user) {
            return $cache[$routeName] = false;
        }
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && strpos($middleware, 'role:') === 0) {
                $roles = explode('|', explode(',', substr($middleware, 5))[0]);
                if (!$user->hasAnyRole($roles)) {
                    return $cache[$routeName] = false;
                }
            }
        }
        return $cache[$routeName] = true;
    }

    private function angka($value): string
    {
        $value = round((float) $value, 2);
        return number_format($value == 0 ? 0 : $value, 2);
    }
}
