<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan Pembelian: how much was bought per principal, pemasok or obat in a period.
 *
 * Purchases are the lines of approved fakturs (diapprove / diretur*) by received date, valued as on the
 * faktur: the line total (after its own discount and tax) plus its share of the faktur's global pajak and
 * minus its share of the global diskon. Approved returns in the same period (by retur date) are valued at
 * the same unit price and shown separately, so the netto is purchases minus returns.
 */
class LaporanPembelianController extends Controller
{
    private const DIMENSIONS = ['principal', 'pemasok', 'obat'];
    private const FAKTUR_STATUS = ['diapprove', 'diretur_sebagian', 'diretur'];

    public function index()
    {
        return view('erm.laporan-pembelian.index');
    }

    /** [start, end] (Y-m-d) of the requested period; default the current month. */
    private function periode(Request $request): array
    {
        try {
            $start = $request->filled('start') ? Carbon::parse($request->input('start'))->startOfDay() : now()->startOfMonth();
            $end = $request->filled('end') ? Carbon::parse($request->input('end'))->endOfDay() : now()->endOfMonth();
        } catch (\Exception $e) {
            [$start, $end] = [now()->startOfMonth(), now()->endOfMonth()];
        }
        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function dimension(Request $request): string
    {
        return in_array($request->input('dim'), self::DIMENSIONS, true) ? $request->input('dim') : 'principal';
    }

    // Key and label of a line for the chosen grouping
    private function groupColumns(string $dim): array
    {
        return [
            'principal' => ['COALESCE(fi.principal_id, o.principal_id, 0)', "COALESCE(pr.nama, 'Tanpa principal')"],
            'pemasok' => ['COALESCE(f.pemasok_id, 0)', "COALESCE(pm.nama, 'Tanpa pemasok')"],
            'obat' => ['fi.obat_id', 'o.nama'],
        ][$dim];
    }

    /**
     * Faktur lines with their value columns. $from / $to filter the faktur received date;
     * the principal / pemasok / obat filters narrow the lines.
     */
    private function lineQuery(Request $request)
    {
        $subtotal = DB::table('erm_fakturbeli_items as fi')
            ->select('fi.fakturbeli_id', DB::raw('SUM(' . self::LINE . ') as sub'))
            ->groupBy('fi.fakturbeli_id');

        return DB::table('erm_fakturbeli_items as fi')
            ->join('erm_fakturbeli as f', 'f.id', '=', 'fi.fakturbeli_id')
            ->joinSub($subtotal, 's', 's.fakturbeli_id', '=', 'f.id')
            ->join('erm_obat as o', 'o.id', '=', 'fi.obat_id')
            ->leftJoin('erm_pemasok as pm', 'pm.id', '=', 'f.pemasok_id')
            ->leftJoin('erm_principals as pr', 'pr.id', '=', DB::raw('COALESCE(fi.principal_id, o.principal_id)'))
            ->whereIn('f.status', self::FAKTUR_STATUS)
            ->where('fi.qty', '>', 0)
            ->when($request->filled('principal_id'), fn ($q) => $q->whereRaw('COALESCE(fi.principal_id, o.principal_id) = ?', [(int) $request->input('principal_id')]))
            ->when($request->filled('pemasok_id'), fn ($q) => $q->where('f.pemasok_id', (int) $request->input('pemasok_id')))
            ->when($request->filled('obat_id'), fn ($q) => $q->where('fi.obat_id', (int) $request->input('obat_id')));
    }

    // Line total as on the faktur; old lines have no total_amount, so it is rebuilt from qty, price, discount and tax
    private const LINE = "COALESCE(fi.total_amount, GREATEST(fi.qty * fi.harga"
        . " - CASE WHEN fi.diskon_type = 'percent' THEN fi.qty * fi.harga * COALESCE(fi.diskon, 0) / 100 ELSE COALESCE(fi.diskon, 0) END"
        . " + CASE WHEN fi.tax_type = 'percent' THEN fi.qty * fi.harga * COALESCE(fi.tax, 0) / 100 ELSE COALESCE(fi.tax, 0) END, 0))";
    // The line's share of the faktur's global diskon / pajak (by line value)
    private const DPP = '(' . self::LINE . ' - COALESCE(f.global_diskon, 0) * COALESCE(' . self::LINE . ' / NULLIF(s.sub, 0), 0))';
    private const PPN = '(COALESCE(f.global_pajak, 0) * COALESCE(' . self::LINE . ' / NULLIF(s.sub, 0), 0))';
    // Global pajak above this share of the subtotal is almost surely an input error (e.g. a percent typed as Rupiah)
    private const PAJAK_WAJAR_MAKS = 0.2;

    /** Purchases (and returns) grouped by the chosen dimension for one period. */
    private function aggregate(Request $request, string $dim, Carbon $start, Carbon $end): array
    {
        [$key, $label] = $this->groupColumns($dim);

        $beli = $this->lineQuery($request)
            ->whereBetween('f.received_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("{$key} as k, {$label} as nama")
            ->selectRaw('COUNT(DISTINCT f.id) as faktur')
            ->selectRaw('SUM(fi.qty) as qty')
            ->selectRaw('SUM(' . self::DPP . ') as dpp')
            ->selectRaw('SUM(' . self::PPN . ') as ppn')
            ->groupByRaw("{$key}, {$label}")
            ->get()
            ->keyBy('k');

        // Returns approved in the period, valued at the purchase unit price of the returned line
        $retur = $this->lineQuery($request)
            ->join('erm_fakturretur_items as ri', 'ri.fakturbeli_item_id', '=', 'fi.id')
            ->join('erm_fakturretur as r', 'r.id', '=', 'ri.fakturretur_id')
            ->where('r.status', 'diapprove')
            ->whereBetween('r.tanggal_retur', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("{$key} as k, {$label} as nama")
            ->selectRaw('SUM(ri.qty) as qty')
            ->selectRaw('SUM(ri.qty * (' . self::DPP . ' + ' . self::PPN . ') / NULLIF(fi.qty, 0)) as nilai')
            ->groupByRaw("{$key}, {$label}")
            ->get()
            ->keyBy('k');

        $rows = [];
        foreach ($beli->keys()->merge($retur->keys())->unique() as $k) {
            $b = $beli->get($k);
            $r = $retur->get($k);
            $total = $b ? (float) $b->dpp + (float) $b->ppn : 0.0;
            $returNilai = $r ? (float) $r->nilai : 0.0;
            $rows[] = [
                'key' => (int) $k,
                'nama' => $b->nama ?? $r->nama,
                'faktur' => (int) ($b->faktur ?? 0),
                'qty' => (float) ($b->qty ?? 0),
                'dpp' => round((float) ($b->dpp ?? 0), 2),
                'ppn' => round((float) ($b->ppn ?? 0), 2),
                'total' => round($total, 2),
                'retur_qty' => (float) ($r->qty ?? 0),
                'retur' => round($returNilai, 2),
                'netto' => round($total - $returNilai, 2),
            ];
        }
        usort($rows, fn ($a, $b) => $b['netto'] <=> $a['netto']);

        return $rows;
    }

    public function data(Request $request)
    {
        [$start, $end] = $this->periode($request);
        $dim = $this->dimension($request);
        $rows = $this->aggregate($request, $dim, $start, $end);

        // Same length right before, for the comparison on the summary
        $days = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();
        $prev = $this->aggregate($request, $dim, $prevStart, $prevEnd);
        $prevByKey = collect($prev)->keyBy('key');

        $sum = fn ($list, $field) => round(array_sum(array_column($list, $field)), 2);
        $totalNetto = $sum($rows, 'netto');
        foreach ($rows as &$row) {
            $row['persen'] = $totalNetto > 0 ? round($row['netto'] / $totalNetto * 100, 1) : 0;
            $row['netto_sebelumnya'] = (float) optional($prevByKey->get($row['key']))['netto'];
        }
        unset($row);

        $fakturCount = (clone $this->lineQuery($request))
            ->whereBetween('f.received_date', [$start->toDateString(), $end->toDateString()])
            ->distinct()->count('f.id');

        $anomali = (clone $this->lineQuery($request))
            ->whereBetween('f.received_date', [$start->toDateString(), $end->toDateString()])
            ->whereRaw('COALESCE(f.global_pajak, 0) > s.sub * ?', [self::PAJAK_WAJAR_MAKS])
            ->select('f.id', 'f.no_faktur', 'f.global_pajak', 's.sub')
            ->distinct()
            ->limit(20)
            ->get()
            ->map(fn ($a) => ['id' => $a->id, 'no_faktur' => $a->no_faktur, 'pajak' => (float) $a->global_pajak, 'subtotal' => round((float) $a->sub, 2)]);

        return response()->json([
            'anomali_pajak' => $anomali,
            'periode' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'periode_sebelumnya' => ['start' => $prevStart->toDateString(), 'end' => $prevEnd->toDateString()],
            'dim' => $dim,
            'rows' => $rows,
            'summary' => [
                'dpp' => $sum($rows, 'dpp'),
                'ppn' => $sum($rows, 'ppn'),
                'total' => $sum($rows, 'total'),
                'retur' => $sum($rows, 'retur'),
                'netto' => $totalNetto,
                'faktur' => $fakturCount,
                'jumlah_grup' => count($rows),
                'netto_sebelumnya' => $sum($prev, 'netto'),
            ],
        ]);
    }

    /**
     * Faktur lines of the period under the current filters; with `key`, only those behind one row of the report.
     */
    private function detailQuery(Request $request)
    {
        [$start, $end] = $this->periode($request);
        [$key] = $this->groupColumns($this->dimension($request));

        return $this->lineQuery($request)
            ->leftJoin('erm_gudang as g', 'g.id', '=', 'fi.gudang_id')
            ->whereBetween('f.received_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('key'), fn ($q) => $q->whereRaw("{$key} = ?", [(int) $request->input('key')]))
            ->select(
                'f.id as faktur_id', 'f.no_faktur', 'f.no_permintaan', 'f.received_date', 'f.due_date', 'f.status',
                'pm.nama as pemasok', 'o.kode_obat', 'o.nama as obat', 'pr.nama as principal', 'g.nama as gudang',
                'fi.qty', 'fi.harga', 'fi.diskon', 'fi.diskon_type', 'fi.tax', 'fi.tax_type', 'fi.batch', 'fi.expiration_date',
                'f.global_pajak', 's.sub as faktur_subtotal'
            )
            ->selectRaw(self::DPP . ' as dpp')
            ->selectRaw(self::PPN . ' as ppn')
            ->orderByDesc('f.received_date')
            ->orderBy('f.no_faktur')
            ->orderBy('o.nama');
    }

    /** Faktur lines behind one row of the report. */
    public function detail(Request $request)
    {
        $lines = $this->detailQuery($request)
            ->limit(1000)
            ->get()
            ->map(function ($l) {
                $total = round((float) $l->dpp + (float) $l->ppn, 2);
                return [
                    'faktur_id' => $l->faktur_id,
                    'no_faktur' => $l->no_faktur,
                    'no_permintaan' => $l->no_permintaan,
                    'received_date' => $l->received_date,
                    'pemasok' => $l->pemasok,
                    'obat' => $l->obat,
                    'kode_obat' => $l->kode_obat,
                    'principal' => $l->principal,
                    'gudang' => $l->gudang,
                    'batch' => $l->batch,
                    'expiration_date' => $l->expiration_date,
                    'qty' => (float) $l->qty,
                    'harga' => (float) $l->harga,
                    'diskon' => (float) $l->diskon,
                    'diskon_type' => $l->diskon_type,
                    'dpp' => round((float) $l->dpp, 2),
                    'ppn' => round((float) $l->ppn, 2),
                    'total' => $total,
                    // Cost per unit actually paid (after discounts, incl. PPN)
                    'per_unit' => (float) $l->qty > 0 ? round($total / (float) $l->qty, 2) : 0,
                    'pajak_tidak_wajar' => (float) $l->global_pajak > (float) $l->faktur_subtotal * self::PAJAK_WAJAR_MAKS,
                ];
            });

        return response()->json(['lines' => $lines, 'dibatasi' => $lines->count() >= 1000]);
    }

    /**
     * Excel of the faktur lines: one row of the report (key) or every line under the current filters.
     */
    public function exportDetail(Request $request)
    {
        [$start, $end] = $this->periode($request);
        $dim = $this->dimension($request);
        $statusLabel = ['diapprove' => 'Disetujui', 'diretur_sebagian' => 'Diretur sebagian', 'diretur' => 'Diretur'];
        $diskonLabel = fn ($value, $type) => (float) $value > 0 ? ($type === 'percent' ? rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . '%' : (float) $value) : '';

        $rows = [];
        $totals = ['qty' => 0, 'dpp' => 0, 'ppn' => 0];
        foreach ($this->detailQuery($request)->cursor() as $l) {
            $dpp = round((float) $l->dpp, 2);
            $ppn = round((float) $l->ppn, 2);
            $totals['qty'] += (float) $l->qty;
            $totals['dpp'] += $dpp;
            $totals['ppn'] += $ppn;
            $rows[] = [
                $l->received_date ? Carbon::parse($l->received_date)->format('d/m/Y') : '',
                $l->no_faktur,
                $l->no_permintaan,
                $l->pemasok,
                $l->principal ?? 'Tanpa principal',
                $l->kode_obat,
                $l->obat,
                $l->gudang,
                $l->batch,
                $l->expiration_date ? Carbon::parse($l->expiration_date)->format('d/m/Y') : '',
                (float) $l->qty,
                (float) $l->harga,
                $diskonLabel($l->diskon, $l->diskon_type),
                $diskonLabel($l->tax, $l->tax_type),
                $dpp,
                $ppn,
                round($dpp + $ppn, 2),
                $l->due_date ? Carbon::parse($l->due_date)->format('d/m/Y') : '',
                $statusLabel[$l->status] ?? $l->status,
            ];
        }
        $rows[] = ['TOTAL', '', '', '', '', '', '', '', '', '', $totals['qty'], '', '', '', round($totals['dpp'], 2), round($totals['ppn'], 2), round($totals['dpp'] + $totals['ppn'], 2), '', ''];

        $headings = ['Tanggal Terima', 'No Faktur', 'No Permintaan', 'Pemasok', 'Principal', 'Kode Obat', 'Obat', 'Gudang', 'Batch', 'Kadaluarsa',
            'Qty', 'Harga Satuan', 'Diskon Item', 'Pajak Item', 'Sebelum PPN', 'PPN', 'Total', 'Jatuh Tempo', 'Status Faktur'];

        $export = new class($rows, $headings) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\ShouldAutoSize, \Maatwebsite\Excel\Concerns\WithStrictNullComparison {
            public function __construct(private array $rows, private array $headings) {}
            public function array(): array { return $this->rows; }
            public function headings(): array { return $this->headings; }
        };

        // File name tells what was exported: the selected row's name, or "semua"
        $nama = 'semua';
        if ($request->filled('key')) {
            $nama = $dim . '_' . \Illuminate\Support\Str::slug((string) $request->input('nama', $request->input('key')), '_');
        }

        return Excel::download($export, 'detail_pembelian_' . $nama . '_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.xlsx');
    }

    public function export(Request $request)
    {
        [$start, $end] = $this->periode($request);
        $dim = $this->dimension($request);
        $rows = $this->aggregate($request, $dim, $start, $end);
        $label = ['principal' => 'Principal', 'pemasok' => 'Pemasok', 'obat' => 'Obat'][$dim];

        $data = array_map(fn ($r) => [
            $r['nama'], $r['faktur'], $r['qty'], $r['dpp'], $r['ppn'], $r['total'], $r['retur'], $r['netto'],
        ], $rows);
        $data[] = [
            'TOTAL',
            '',
            array_sum(array_column($rows, 'qty')),
            array_sum(array_column($rows, 'dpp')),
            array_sum(array_column($rows, 'ppn')),
            array_sum(array_column($rows, 'total')),
            array_sum(array_column($rows, 'retur')),
            array_sum(array_column($rows, 'netto')),
        ];
        $headings = [$label, 'Jumlah Faktur', 'Qty', 'Sebelum PPN', 'PPN', 'Total Pembelian', 'Retur', 'Netto'];

        $export = new class($data, $headings) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\ShouldAutoSize, \Maatwebsite\Excel\Concerns\WithStrictNullComparison {
            public function __construct(private array $rows, private array $headings) {}
            public function array(): array { return $this->rows; }
            public function headings(): array { return $this->headings; }
        };

        return Excel::download($export, 'laporan_pembelian_per_' . $dim . '_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.xlsx');
    }
}
