<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\InvoiceEditLog;
use App\Models\Finance\ReturPembelian;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use App\Support\Dashboard\RevenueTargets;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Laporan Omset: omset = total of invoices transacted in the period (paid / piutang / asuransi), dated by
 * finance_invoices.transaction_date shifted back by the clinic cut off (erm_klinik.report_cutoff_time), minus
 * approved returs on their approval day. Same definition as the dashboard widgets (App\Support\Dashboard\Revenue).
 */
class OmsetController extends Controller
{
    /** Longer ranges are charted per month instead of per day. */
    private const DAILY_MAX_DAYS = 62;

    public function index()
    {
        // klinik_id => 'HH:MM' (transactions before this hour count for the previous day)
        $cutoffs = DB::table('erm_klinik')->pluck('report_cutoff_time', 'id')
            ->map(fn ($time) => substr((string) ($time ?: '00:00:00'), 0, 5));

        return view('finance.omset.index', [
            'kliniks' => Clinics::all()->values(),
            'cutoffs' => $cutoffs,
        ]);
    }

    public function data(Request $request)
    {
        [$from, $to, $klinikId] = $this->filters($request);

        // previous period of the same length, right before this one
        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $prevTo = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);

        $summary = $this->summary($from, $to, $klinikId);
        $previous = $this->summary($prevFrom, $prevTo, $klinikId);

        return response()->json([
            'period' => [
                'label' => $this->periodLabel($from, $to),
                'previous_label' => $this->periodLabel($prevFrom, $prevTo),
            ],
            'summary' => $summary + [
                'trend' => Fmt::trend($summary['total'], $previous['total']),
                'previous_total' => $previous['total'],
            ],
            'chart' => $this->chart($from, $to, $klinikId),
            'klinik' => $this->byKlinik($from, $to, $klinikId, $summary['total']),
            'kategori' => $this->byCategory($from, $to, $klinikId, $summary['total']),
            'metode_bayar' => $this->byMetodeBayar($from, $to, $klinikId, $summary['total']),
            'returs' => $this->returs($from, $to, $klinikId),
        ]);
    }

    public function invoices(Request $request)
    {
        [$from, $to, $klinikId] = $this->filters($request);

        $query = $this->base($from, $to, $klinikId)
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
            ->leftJoin('erm_metode_bayar as mb', 'mb.id', '=', 'v.metode_bayar_id')
            ->select([
                'fi.id', 'fi.invoice_number', 'fi.transaction_date', 'fi.total_amount', 'fi.amount_paid',
                'fi.payment_method', 'fi.status', 'v.klinik_id', 'p.nama as pasien_nama', 'p.id as pasien_id',
                'k.nama as klinik_nama', 'mb.nama as metode_bayar_nama',
            ])
            ->selectRaw('LEAST(COALESCE(fi.amount_paid, 0), fi.total_amount) as diterima')
            // approved returs of the invoice (any period: they come off omset on their own approval day)
            ->selectRaw('COALESCE(fi.retur_amount, 0) as retur')
            ->selectRaw("(SELECT MAX(COALESCE(rr.processed_date, rr.created_at)) FROM finance_retur_pembelian rr
                WHERE rr.invoice_id = fi.id AND rr.deleted_at IS NULL AND rr.status = 'approved') as retur_date")
            // the day the omset counts on (differs from the transaction date for service after midnight)
            ->selectRaw(Revenue::reportDateSql() . ' as tanggal_omset');

        return DataTables::of($query)
            ->filterColumn('pasien_nama', fn ($q, $kw) => $q->where(fn ($w) => $w->where('p.nama', 'like', "%{$kw}%")->orWhere('p.id', 'like', "%{$kw}%")))
            ->filterColumn('invoice_number', fn ($q, $kw) => $q->where('fi.invoice_number', 'like', "%{$kw}%"))
            ->filterColumn('klinik_nama', fn ($q, $kw) => $q->where('k.nama', 'like', "%{$kw}%"))
            ->filterColumn('metode_bayar_nama', fn ($q, $kw) => $q->where('mb.nama', 'like', "%{$kw}%"))
            ->orderColumn('transaction_date', 'fi.transaction_date $1')
            ->orderColumn('total_amount', 'fi.total_amount $1')
            ->editColumn('transaction_date', fn ($row) => Carbon::parse($row->transaction_date)->format('Y-m-d H:i'))
            ->addColumn('sisa', fn ($row) => max(0, (float) $row->total_amount - (float) $row->diterima))
            // received after the refund went back to the patient
            ->editColumn('diterima', fn ($row) => (float) $row->diterima - (float) $row->retur)
            ->editColumn('retur_date', fn ($row) => $row->retur_date ? Carbon::parse($row->retur_date)->toDateString() : null)
            ->addColumn('klinik_short', fn ($row) => Clinics::find($row->klinik_id)->short)
            ->make(true);
    }

    /**
     * Admin / Finance: move an invoice's transaction date (which day its omset counts on). Logged with a reason
     * in the invoice edit log, next to the price / discount edits.
     */
    public function updateTransactionDate(Request $request, $id)
    {
        $data = $request->validate([
            'transaction_date' => 'required|date_format:Y-m-d\TH:i|before_or_equal:now',
            'reason' => 'required|string|min:5|max:1000',
        ], [
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh di masa depan.',
        ]);

        $invoice = Invoice::findOrFail($id);
        if (empty($invoice->transaction_date)) {
            return response()->json(['success' => false, 'message' => 'Invoice belum ditransaksikan.'], 422);
        }

        $old = $invoice->transaction_date->format('Y-m-d H:i');
        $new = Carbon::createFromFormat('Y-m-d\TH:i', $data['transaction_date']);
        if ($old === $new->format('Y-m-d H:i')) {
            return response()->json(['success' => false, 'message' => 'Tidak ada perubahan.'], 422);
        }

        DB::transaction(function () use ($invoice, $new, $old, $data) {
            $invoice->transaction_date = $new;
            $invoice->save();

            InvoiceEditLog::create([
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'reason' => $data['reason'],
                'old_values' => ['transaction_date' => $old],
                'new_values' => ['transaction_date' => $new->format('Y-m-d H:i')],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tanggal transaksi ' . $invoice->invoice_number . ' diubah ke ' . $new->translatedFormat('j F Y H:i') . '.',
        ]);
    }

    /**
     * Admin / Finance: move an approved retur's date (processed_date: the day it comes off omset). The refund's
     * cash-out transaction moves along so kas and omset agree. approved_at keeps when it was really approved.
     * Logged with a reason in the invoice edit log.
     */
    public function updateReturDate(Request $request, $id)
    {
        $data = $request->validate([
            'retur_date' => 'required|date_format:Y-m-d\TH:i|before_or_equal:now',
            'reason' => 'required|string|min:5|max:1000',
        ], [
            'retur_date.before_or_equal' => 'Tanggal retur tidak boleh di masa depan.',
        ]);

        $retur = ReturPembelian::with('invoice')->findOrFail($id);
        if ($retur->status !== ReturPembelian::STATUS_APPROVED) {
            return response()->json(['success' => false, 'message' => 'Hanya retur yang sudah di-approve yang bisa diubah tanggalnya.'], 422);
        }

        $new = Carbon::createFromFormat('Y-m-d\TH:i', $data['retur_date']);
        $soldAt = optional($retur->invoice)->transaction_date;
        if ($soldAt && $new->lt($soldAt->copy()->startOfMinute())) {
            return response()->json(['success' => false, 'message' => 'Tanggal retur tidak boleh sebelum tanggal transaksi invoice (' . $soldAt->format('Y-m-d H:i') . ').'], 422);
        }

        $old = ($retur->processed_date ?? $retur->created_at)->format('Y-m-d H:i');
        if ($old === $new->format('Y-m-d H:i')) {
            return response()->json(['success' => false, 'message' => 'Tidak ada perubahan.'], 422);
        }

        DB::transaction(function () use ($retur, $new, $old, $data) {
            $retur->processed_date = $new;
            $retur->save();

            // the refund's cash-out, recorded on approval as "No retur: <number> | ..."
            FinanceTransaction::where('invoice_id', $retur->invoice_id)
                ->where('jenis_transaksi', 'out')
                ->where('deskripsi', 'like', 'No retur: ' . $retur->retur_number . '%')
                ->update(['tanggal' => $new]);

            InvoiceEditLog::create([
                'invoice_id' => $retur->invoice_id,
                'user_id' => Auth::id(),
                'reason' => $data['reason'],
                'old_values' => ['retur_number' => $retur->retur_number, 'retur_date' => $old],
                'new_values' => ['retur_number' => $retur->retur_number, 'retur_date' => $new->format('Y-m-d H:i')],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tanggal retur ' . $retur->retur_number . ' diubah ke ' . $new->translatedFormat('j F Y H:i') . '.',
        ]);
    }

    /** [from, to, klinik_id|null]; default: this month. */
    private function filters(Request $request): array
    {
        try {
            $from = Carbon::parse($request->input('start_date', now()->startOfMonth()->toDateString()))->startOfDay();
            $to = Carbon::parse($request->input('end_date', now()->toDateString()))->endOfDay();
        } catch (\Exception $e) {
            $from = now()->startOfMonth();
            $to = now()->endOfDay();
        }
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        $klinikId = $request->filled('klinik_id') ? (int) $request->input('klinik_id') : null;

        return [$from, $to, $klinikId];
    }

    /** Transacted invoices of the period, joined to their visit (v), optionally of one clinic. */
    private function base(Carbon $from, Carbon $to, ?int $klinikId)
    {
        return Revenue::between($from, $to)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->when($klinikId, fn ($q) => $q->where('v.klinik_id', $klinikId));
    }

    /** Approved returs of the period (by approval day), joined to their invoice (fi) and visit (v). */
    private function returBase(Carbon $from, Carbon $to, ?int $klinikId)
    {
        return Revenue::returBetween($from, $to)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->when($klinikId, fn ($q) => $q->where('v.klinik_id', $klinikId));
    }

    private function summary(Carbon $from, Carbon $to, ?int $klinikId): array
    {
        $row = $this->base($from, $to, $klinikId)
            ->selectRaw('COUNT(*) as invoices, COALESCE(SUM(fi.total_amount), 0) as total')
            ->selectRaw('COALESCE(SUM(LEAST(COALESCE(fi.amount_paid, 0), fi.total_amount)), 0) as diterima')
            ->first();
        $retur = $this->returBase($from, $to, $klinikId)
            ->selectRaw('COUNT(*) as returs, COALESCE(SUM(r.total_amount), 0) as total')
            ->first();

        $gross = (float) $row->total;
        $returTotal = (float) $retur->total;
        $total = $gross - $returTotal;
        $invoices = (int) $row->invoices;
        // the refund went back to the patient, so it is no longer received either
        $diterima = (float) $row->diterima - $returTotal;

        return [
            'total' => $total,
            'gross' => $gross,
            'retur' => $returTotal,
            'returs' => (int) $retur->returs,
            'invoices' => $invoices,
            'average' => $invoices > 0 ? $gross / $invoices : 0,
            'diterima' => $diterima,
            'belum_diterima' => max(0, $total - $diterima),
            'diterima_pct' => Fmt::share($diterima, $total),
        ];
    }

    /** Omset per day (or per month for long ranges), every bucket present. */
    private function chart(Carbon $from, Carbon $to, ?int $klinikId): array
    {
        $monthly = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1 > self::DAILY_MAX_DAYS;
        $reportDate = Revenue::reportDateSql();
        $bucketSql = $monthly ? "DATE_FORMAT({$reportDate}, '%Y-%m')" : $reportDate;

        $rows = $this->base($from, $to, $klinikId)
            ->selectRaw("{$bucketSql} as bucket, SUM(fi.total_amount) as total, COUNT(*) as invoices")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $returDate = Revenue::returDateSql();
        $returs = $this->returBase($from, $to, $klinikId)
            ->selectRaw(($monthly ? "DATE_FORMAT({$returDate}, '%Y-%m')" : $returDate) . ' as bucket, SUM(r.total_amount) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $period = $monthly
            ? CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->startOfMonth())
            : CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay());

        $points = [];
        foreach ($period as $date) {
            $key = $monthly ? $date->format('Y-m') : $date->toDateString();
            $row = $rows->get($key);
            $points[] = [
                'key' => $key,
                'label' => $monthly ? $date->translatedFormat('M Y') : $date->translatedFormat('j M'),
                'title' => $monthly ? $date->translatedFormat('F Y') : $date->translatedFormat('l, j F Y'),
                'total' => ($row ? (float) $row->total : 0.0) - (float) ($returs[$key] ?? 0),
                'retur' => (float) ($returs[$key] ?? 0),
                'invoices' => $row ? (int) $row->invoices : 0,
            ];
        }

        return ['granularity' => $monthly ? 'month' : 'day', 'points' => $points];
    }

    private function byKlinik(Carbon $from, Carbon $to, ?int $klinikId, float $total): array
    {
        $rows = $this->base($from, $to, $klinikId)
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, COUNT(*) as invoices, SUM(fi.total_amount) as total')
            ->selectRaw('SUM(LEAST(COALESCE(fi.amount_paid, 0), fi.total_amount)) as diterima')
            ->get();

        // klinik_id => retur ('' = invoice without a visit)
        $returs = $this->returBase($from, $to, $klinikId)
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, SUM(r.total_amount) as total')
            ->pluck('total', 'klinik_id')
            ->map(fn ($v) => (float) $v);

        $targets = RevenueTargets::forPeriod($from, $to);

        $clinics = $klinikId ? collect([Clinics::find($klinikId)]) : Clinics::all()->values();
        $result = $clinics->map(function ($klinik) use ($rows, $returs, $targets, $total, $from, $to) {
            $row = $rows->first(fn ($r) => (int) $r->klinik_id === (int) $klinik->id);
            $retur = $returs[$klinik->id] ?? 0.0;
            $omset = ($row ? (float) $row->total : 0.0) - $retur;
            $target = (float) ($targets['byKlinik'][$klinik->id] ?? 0);

            return [
                'id' => $klinik->id,
                'nama' => $klinik->short,
                'logo' => $klinik->logo,
                'color' => $klinik->color,
                'invoices' => $row ? (int) $row->invoices : 0,
                'total' => $omset,
                'retur' => $retur,
                'diterima' => ($row ? (float) $row->diterima : 0.0) - $retur,
                'pct' => Fmt::share($omset, $total),
                'target' => $target,
                'target_pct' => $target > 0 ? $omset / $target * 100 : null,
                // still needed (total + per day over the days left in the target's months)
                'target_gap' => RevenueTargets::gap($omset, $target, $from, $to),
            ];
        });
        $targetTotal = (float) $result->sum('target');
        $clinicOmset = (float) $result->sum('total'); // before "Tanpa klinik" is added: it has no target

        // invoices without a clinic (e.g. direct sales), only when showing every clinic
        $isNone = fn ($id) => $id === null || $id === '' || !Clinics::all()->has((int) $id);
        $none = $klinikId ? collect() : $rows->filter(fn ($r) => $isNone($r->klinik_id));
        $noneRetur = $klinikId ? 0.0 : (float) $returs->filter(fn ($v, $id) => $isNone($id))->sum();
        if ($none->isNotEmpty() || $noneRetur > 0) {
            $noneTotal = (float) $none->sum('total') - $noneRetur;
            $result->push([
                'id' => null, 'nama' => Clinics::none()->short, 'logo' => null, 'color' => Clinics::none()->color,
                'invoices' => (int) $none->sum('invoices'), 'total' => $noneTotal, 'retur' => $noneRetur,
                'diterima' => (float) $none->sum('diterima') - $noneRetur,
                'pct' => Fmt::share($noneTotal, $total), 'target' => 0, 'target_pct' => null, 'target_gap' => null,
            ]);
        }

        return [
            'rows' => $result->values()->all(),
            'target_label' => $targets['label'],
            // all clinics together, only when several are shown
            'target_total_gap' => $clinics->count() > 1 ? RevenueTargets::gap($clinicOmset, $targetTotal, $from, $to) : null,
        ];
    }

    private function byCategory(Carbon $from, Carbon $to, ?int $klinikId, float $total): array
    {
        return Revenue::byCategory($from, $to, $klinikId)
            ->map(fn ($value, $key) => [
                'key' => $key,
                'label' => Revenue::CATEGORIES[$key],
                'total' => (float) $value,
                'pct' => Fmt::share($value, $total),
            ])
            ->values()
            ->all();
    }

    private function byMetodeBayar(Carbon $from, Carbon $to, ?int $klinikId, float $total): array
    {
        $rows = Revenue::withMetodeBayar($this->base($from, $to, $klinikId)
            ->selectRaw('SUM(fi.total_amount) as total, COUNT(*) as invoices'))
            ->get()
            ->concat(Revenue::returByMetodeBayar($from, $to, $klinikId));

        return Revenue::metodeBayarSplit($rows, $total);
    }

    /** The period's approved returs (they come off omset on their approval day), newest first. */
    private function returs(Carbon $from, Carbon $to, ?int $klinikId): array
    {
        return $this->returBase($from, $to, $klinikId)
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->select(['r.id', 'r.retur_number', 'r.total_amount', 'fi.invoice_number', 'fi.transaction_date', 'v.klinik_id', 'p.nama as pasien_nama', 'p.id as pasien_id'])
            ->selectRaw('COALESCE(r.processed_date, r.created_at) as retur_date')
            ->orderByDesc('retur_date')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'retur_number' => $r->retur_number,
                'retur_date' => Carbon::parse($r->retur_date)->format('Y-m-d H:i'),
                'invoice_number' => $r->invoice_number,
                'invoice_date' => $r->transaction_date ? Carbon::parse($r->transaction_date)->format('Y-m-d') : null,
                'pasien_nama' => $r->pasien_nama,
                'pasien_id' => $r->pasien_id,
                'klinik_short' => Clinics::find($r->klinik_id)->short,
                'total' => (float) $r->total_amount,
            ])
            ->all();
    }

    private function periodLabel(Carbon $from, Carbon $to): string
    {
        if ($from->isSameDay($to)) {
            return $from->translatedFormat('j F Y');
        }

        return $from->translatedFormat($from->year === $to->year ? 'j M' : 'j M Y') . ' – ' . $to->translatedFormat('j M Y');
    }
}
