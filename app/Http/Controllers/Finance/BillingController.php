<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\Visitation;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use App\Models\Finance\Piutang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\ERM\Gudang;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\ObatStokGudang;
use App\Services\ERM\StokService;
use App\Models\ERM\PaketRacikan;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\KartuStok;


class BillingController extends Controller
{
    /**
     * Endpoint statistik harian untuk AJAX
     */
    public function statistikPendapatanAjax(Request $request)
    {
        $startDate = $request->input('start_date') ?? date('Y-m-d');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $klinikId = $request->input('klinik_id');

        // Prepare array of dates in range
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        $dailyPendapatan = [];
        foreach ($dates as $date) {
            $query = Invoice::whereHas('visitation', function($q) use ($date, $klinikId) {
                $q->whereDate('tanggal_visitation', $date);
                if ($klinikId) $q->where('klinik_id', $klinikId);
            });
            $dailyPendapatan[] = [
                'date' => $date,
                'pendapatan' => $query->sum('amount_paid'),
            ];
        }

        // Total pendapatan, nota, kunjungan
        $invoiceQuery = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
            ->where('finance_invoices.amount_paid', '>', 0);
        if ($klinikId) {
            $invoiceQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        // Debug: Log raw SQL and invoice IDs
        \Illuminate\Support\Facades\Log::info('Finance statistikPendapatanAjax SQL:', ['sql' => $invoiceQuery->toSql(), 'bindings' => $invoiceQuery->getBindings()]);
        $invoiceIds = $invoiceQuery->pluck('finance_invoices.id');
        \Illuminate\Support\Facades\Log::info('Finance statistikPendapatanAjax Invoice IDs:', ['ids' => $invoiceIds]);
        $pendapatan = $invoiceQuery->sum('finance_invoices.total_amount');
        $jumlahNota = $invoiceQuery->count();

        $kunjunganQuery = \App\Models\ERM\Visitation::whereBetween('tanggal_visitation', [$startDate, $endDate]);
        if ($klinikId) $kunjunganQuery->where('klinik_id', $klinikId);
        $jumlahKunjungan = $kunjunganQuery->count();

        // Perubahan pendapatan dibandingkan periode sebelumnya (periode sama sebelum startDate)
        $prevStart = date('Y-m-d', strtotime($startDate . ' -' . (strtotime($endDate) - strtotime($startDate) + 86400) . ' seconds'));
        $prevEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $invoicePrevQuery = Invoice::whereHas('visitation', function($q) use ($prevStart, $prevEnd, $klinikId) {
            $q->whereBetween('tanggal_visitation', [$prevStart, $prevEnd]);
            if ($klinikId) $q->where('klinik_id', $klinikId);
        });
        $pendapatanPrev = $invoicePrevQuery->sum('amount_paid');
        $persen = $pendapatanPrev > 0 ? (($pendapatan - $pendapatanPrev) / $pendapatanPrev) * 100 : null;

        return response()->json([
            'pendapatan' => $pendapatan,
            'jumlahNota' => $jumlahNota,
            'jumlahKunjungan' => $jumlahKunjungan,
            'persen' => $persen,
            'dailyPendapatan' => $dailyPendapatan,
        ]);
    }
    /**
     * Menampilkan form rekap penjualan dan tombol download
     */
    public function rekapPenjualanForm(Request $request)
    {
        // Ambil filter
        $date = $request->input('date') ?? date('Y-m-d');
        $klinikId = $request->input('klinik_id');

        // Query invoice hari ini
        $invoiceQuery = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->whereDate('erm_visitations.tanggal_visitation', $date)
            ->where('finance_invoices.amount_paid', '>', 0);
        if ($klinikId) {
            $invoiceQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        // Debug: Log raw SQL and invoice IDs
        \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm SQL:', ['sql' => $invoiceQuery->toSql(), 'bindings' => $invoiceQuery->getBindings()]);
        $invoiceIds = $invoiceQuery->pluck('finance_invoices.id');
        \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Invoice IDs:', ['ids' => $invoiceIds]);
            $invoiceIds = $invoiceQuery->pluck('finance_invoices.id');
            \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Invoice IDs:', ['ids' => $invoiceIds]);
            $pendapatan = $invoiceQuery->sum('finance_invoices.total_amount');
            $jumlahNota = $invoiceQuery->count();

        // Query kunjungan hari ini
        $kunjunganQuery = \App\Models\ERM\Visitation::whereDate('tanggal_visitation', $date);
        if ($klinikId) $kunjunganQuery->where('klinik_id', $klinikId);
        $jumlahKunjungan = $kunjunganQuery->count();

        // Query invoice kemarin
        $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
        $invoiceYesterdayQuery = Invoice::whereHas('visitation', function($q) use ($yesterday, $klinikId) {
            $q->whereDate('tanggal_visitation', $yesterday);
            if ($klinikId) $q->where('klinik_id', $klinikId);
        });
            $invoiceYesterdayQuery = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
                ->whereDate('erm_visitations.tanggal_visitation', $yesterday)
                ->where('finance_invoices.amount_paid', '>', 0);
            if ($klinikId) {
                $invoiceYesterdayQuery->where('erm_visitations.klinik_id', $klinikId);
            }
            // Debug: Log raw SQL and invoice IDs for yesterday
            \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Yesterday SQL:', ['sql' => $invoiceYesterdayQuery->toSql(), 'bindings' => $invoiceYesterdayQuery->getBindings()]);
            $invoiceYesterdayIds = $invoiceYesterdayQuery->pluck('finance_invoices.id');
            \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Yesterday Invoice IDs:', ['ids' => $invoiceYesterdayIds]);
            $pendapatanKemarin = $invoiceYesterdayQuery->sum('finance_invoices.total_amount');

        // Hitung persentase perubahan
        $persen = $pendapatanKemarin > 0 ? (($pendapatan - $pendapatanKemarin) / $pendapatanKemarin) * 100 : null;

        // Ambil daftar klinik dan dokter
        $kliniks = \App\Models\ERM\Klinik::select('id', 'nama')->orderBy('nama')->get();
        $dokters = \App\Models\ERM\Dokter::with('user')->orderBy('id')->get();

        return view('finance.billing.rekap_penjualan_form', compact(
            'pendapatan', 'jumlahNota', 'jumlahKunjungan', 'persen', 'date', 'klinikId', 'kliniks', 'dokters'
        ));
    }

    /**
     * Mendownload file Excel rekap penjualan
     */
    public function downloadRekapPenjualanExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $klinikId = $request->input('klinik_id');
        $dokterId = $request->input('dokter_id');
    // Ensure RekapPenjualanExport uses total_amount for revenue calculation
    return (new \App\Exports\Finance\RekapPenjualanExport($startDate, $endDate, $klinikId, $dokterId, 'total_amount'))->download('rekap-penjualan.xlsx');
    }

    /**
     * Preview data for Rekap Penjualan (DataTables AJAX)
     */
    public function previewRekapPenjualan(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $klinikId = $request->input('klinik_id');
        $dokterId = $request->input('dokter_id');

        $query = InvoiceItem::query()
            ->whereHas('invoice.visitation', function($q) use ($startDate, $endDate, $klinikId, $dokterId) {
                $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                if ($klinikId) $q->where('klinik_id', $klinikId);
                if ($dokterId) $q->where('dokter_id', $dokterId);
            })
            ->with(['invoice.visitation.pasien', 'invoice.visitation.dokter.user', 'invoice.visitation.klinik', 'invoice.piutangs', 'invoice']);

        return DataTables::of($query)
            ->addColumn('tanggal_visit', function($item){
                return optional(optional($item->invoice)->visitation)->tanggal_visitation;
            })
            ->addColumn('tanggal_invoice', function($item){
                return optional($item->invoice)->updated_at;
            })
            ->addColumn('no_rm', function($item){
                return optional(optional($item->invoice)->visitation->pasien)->id ?? '-';
            })
            ->addColumn('nama_pasien', function($item){
                return optional(optional($item->invoice)->visitation->pasien)->nama ?? '-';
            })
            ->addColumn('nama_dokter', function($item){
                $dok = optional(optional($item->invoice)->visitation->dokter)->user->name ?? null;
                return $dok;
            })
            ->addColumn('nama_klinik', function($item){
                return optional(optional($item->invoice)->visitation->klinik)->nama ?? null;
            })
            ->addColumn('jenis', function($item){
                $billableType = $item->billable_type ?? '';
                $itemNameLower = strtolower($item->name ?? '');
                if (stripos($billableType, 'Resep') !== false || stripos($billableType, 'Obat') !== false || str_contains($itemNameLower, 'obat')) return 'Obat/Produk';
                if (stripos($billableType, 'Tindakan') !== false) return 'Tindakan';
                if (stripos($billableType, 'Lab') !== false) return 'Laboratorium';
                return 'Lain-lain';
            })
            ->addColumn('nama_item', function($item){ return $item->name; })
            ->addColumn('qty', function($item){ return $item->quantity ?? 1; })
            ->addColumn('harga', function($item){ return $item->unit_price ?? 0; })
            ->addColumn('harga_sebelum_diskon', function($item){ return ($item->quantity ?? 1) * ($item->unit_price ?? 0); })
            ->addColumn('diskon_nominal', function($item){
                $qty = $item->quantity ?? 1; $unit = $item->unit_price ?? 0; $total = $qty * $unit;
                $diskon = $item->discount ?? 0; $type = strtolower(trim((string)($item->discount_type ?? 'nominal')));
                $isPercent = in_array($type, ['persen','percent','%'], true);
                return $isPercent ? ($total * $diskon / 100) : $diskon;
            })
            ->addColumn('diskon', function($item){ return $item->discount; })
            ->addColumn('harga_setelah_diskon', function($item){
                $qty = $item->quantity ?? 1; $unit = $item->unit_price ?? 0; $total = $qty * $unit;
                $diskon = $item->discount ?? 0; $type = strtolower(trim((string)($item->discount_type ?? 'nominal')));
                $isPercent = in_array($type, ['persen','percent','%'], true);
                $diskonNominal = $isPercent ? ($total * $diskon / 100) : $diskon;
                return $total - $diskonNominal;
            })
            ->addColumn('status', function($item){
                $invoice = $item->invoice;
                $total = floatval($invoice->total_amount ?? 0); $paid = floatval($invoice->amount_paid ?? 0);
                if ($total > 0 && $paid >= $total) return 'Sudah Dibayar';
                if ($paid > 0 && $paid < $total) return 'Belum Lunas';
                return 'Belum Dibayar';
            })
            ->addColumn('payment_method', function($item){
                // re-use export logic: prefer piutang payment method when applicable
                $invoice = $item->invoice;
                $paidMethod = $invoice ? $invoice->payment_method : null;
                $piutangs = $invoice && $invoice->relationLoaded('piutangs') ? $invoice->piutangs : ($invoice ? $invoice->piutangs : collect());
                $latestPiutang = $piutangs ? $piutangs->sortByDesc(function($p){ return $p->payment_date ?? $p->updated_at ?? $p->created_at; })->first() : null;
                if ($invoice && $invoice->payment_method === 'piutang') {
                    $settled = $piutangs ? $piutangs->first(function($pi){
                        if (!$pi) return false; $status = strtolower((string)($pi->payment_status ?? '')); if (in_array($status, ['paid','lunas','sudah bayar','sudah dibayar'], true)) return true; $amount = floatval($pi->amount ?? 0); $paid = floatval($pi->paid_amount ?? 0); return $amount>0 && $paid>= $amount; }) : null;
                    if ($settled) {
                        $pm = $piutangs->filter(function($p){ return $p && !empty($p->payment_method); })->sortByDesc(function($p){ return $p->payment_date ?? $p->updated_at ?? $p->created_at; })->first();
                        if ($pm && !empty($pm->payment_method)) return $pm->payment_method;
                    } else {
                        if ($latestPiutang && !empty($latestPiutang->payment_method)) return $latestPiutang->payment_method;
                    }
                }
                return $paidMethod;
            })
            ->addColumn('notes', function($item){
                $invoice = $item->invoice;
                if (!$invoice) return '';
                $total = floatval($invoice->total_amount ?? 0);
                $paid = floatval($invoice->amount_paid ?? 0);
                $invoiceRemaining = max(0, $total - $paid);

                $piutangs = $invoice->relationLoaded('piutangs') ? ($invoice->piutangs ?? collect()) : ($invoice->piutangs ?? collect());
                $latestPiutang = $piutangs ? $piutangs->sortByDesc(function($p){ return $p->payment_date ?? $p->updated_at ?? $p->created_at; })->first() : null;
                $piutangAmount = $latestPiutang ? floatval($latestPiutang->amount ?? 0) : 0;
                $piutangPaid = $latestPiutang ? floatval($latestPiutang->paid_amount ?? 0) : 0;
                $piutangRemaining = max(0, $piutangAmount - $piutangPaid);
                $piutangStatus = $latestPiutang ? strtolower(trim((string)($latestPiutang->payment_status ?? ''))) : '';

                $settledPiutang = $piutangs->first(function($piutang){
                    if (!$piutang) return false;
                    $status = strtolower(trim((string)($piutang->payment_status ?? '')));
                    if (in_array($status, ['paid','lunas','sudah bayar','sudah dibayar'], true)) return true;
                    $amount = floatval($piutang->amount ?? 0);
                    $paidAmount = floatval($piutang->paid_amount ?? 0);
                    return $amount > 0 && $paidAmount >= $amount;
                });

                if ($invoice->payment_method === 'piutang') {
                    if ($settledPiutang || ($total > 0 && $paid >= $total)) {
                        return 'Lunas via piutang';
                    }
                    if (in_array($piutangStatus, ['unpaid','belum bayar','belum dibayar',''], true) && $piutangPaid <= 0) {
                        return 'Piutang belum bayar';
                    }
                    $remaining = $piutangRemaining > 0 ? $piutangRemaining : $invoiceRemaining;
                    return 'Kekurangan: Rp ' . number_format($remaining, 0, ',', '.');
                }

                if ($total > 0 && $paid < $total) {
                    return 'Kekurangan: Rp ' . number_format($invoiceRemaining, 0, ',', '.');
                }
                return '';
            })
            ->make(true);
    }

    /**
     * Preview data for Invoice export (DataTables AJAX)
     */
    public function previewInvoiceExport(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $klinikId = $request->input('klinik_id');
        $dokterId = $request->input('dokter_id');

        $query = Invoice::query()
            ->whereHas('visitation', function($q) use ($startDate, $endDate, $klinikId, $dokterId) {
                $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                if ($klinikId) $q->where('klinik_id', $klinikId);
                if ($dokterId) $q->where('dokter_id', $dokterId);
            })
            ->with(['visitation.pasien', 'visitation.dokter.user', 'visitation.klinik', 'piutangs']);

        return DataTables::of($query)
            ->addColumn('tanggal_visit', function($invoice){ return optional($invoice->visitation)->tanggal_visitation; })
            ->addColumn('tanggal_dibayar', function($invoice){ return $invoice->payment_date; })
            ->addColumn('no_rm', function($invoice){ return optional(optional($invoice->visitation)->pasien)->id; })
            ->addColumn('nama_pasien', function($invoice){ return optional(optional($invoice->visitation)->pasien)->nama; })
            ->addColumn('nama_dokter', function($invoice){ return optional(optional($invoice->visitation)->dokter->user)->name ?? null; })
            ->addColumn('nama_klinik', function($invoice){ return optional(optional($invoice->visitation)->klinik)->nama ?? null; })
            ->addColumn('subtotal', function($invoice){ return $invoice->subtotal; })
            ->addColumn('discount', function($invoice){ return $invoice->discount; })
            ->addColumn('tax', function($invoice){ return $invoice->tax; })
            ->addColumn('total_amount', function($invoice){ return $invoice->total_amount; })
            ->addColumn('amount_paid', function($invoice){ return $invoice->amount_paid; })
            ->addColumn('change_amount', function($invoice){ return $invoice->change_amount; })
            ->addColumn('payment_method', function($invoice){
                $paidMethod = $invoice->payment_method;
                $piutangs = $invoice->piutangs ?? collect();
                $latestPiutang = $piutangs->sortByDesc(function($p){ return $p->payment_date ?? $p->updated_at ?? $p->created_at; })->first();
                $settled = $piutangs->first(function($pi){ if (!$pi) return false; $status = strtolower((string)($pi->payment_status ?? '')); if (in_array($status, ['paid','lunas','sudah bayar','sudah dibayar'], true)) return true; $amount = floatval($pi->amount ?? 0); $paid = floatval($pi->paid_amount ?? 0); return $amount>0 && $paid>= $amount; });
                if ($invoice && $invoice->payment_method === 'piutang') {
                    if ($settled) {
                        $pm = $piutangs->filter(function($p){ return $p && !empty($p->payment_method); })->sortByDesc(function($p){ return $p->payment_date ?? $p->updated_at ?? $p->created_at; })->first();
                        if ($pm && !empty($pm->payment_method)) return $pm->payment_method;
                    } else {
                        if ($latestPiutang && !empty($latestPiutang->payment_method)) return $latestPiutang->payment_method;
                    }
                }
                return $paidMethod;
            })
            ->make(true);
    }
    public function index()
    {
        $visitations = Visitation::with(['pasien','klinik'])->get();

        // dd($visitations);
        return view('finance.billing.index', compact('visitations'));
    }

    /**
     * Send notification from Billing view to all Farmasi users
     */
    public function sendNotifToFarmasi(Request $request)
    {
        // Only allow finance roles (Kasir or Admin) to send from billing view
        $user = Auth::user();
        if (!($user && ($user->hasRole('Kasir') || $user->hasRole('Admin')))) {
            return response()->json(['success' => false], 403);
        }

        $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $message = $request->message;
        $farmasis = \App\Models\User::role('Farmasi')->get();

        Log::info('Billing -> sendNotifToFarmasi called by user: ' . ($user->id ?? 'unknown') . ' (' . ($user->name ?? '') . ')', ['farmasi_count' => $farmasis->count()]);

        $sent = 0;
        $failed = [];
        foreach ($farmasis as $farmasi) {
            try {
                // Avoid duplicate unread notifications with same message
                $already = false;
                // track if we applied a promo-derived discount so we can store nominal equivalent
                $appliedPromo = false;
                $promoBase = null;
                $promoPercent = null;

                try {
                    $already = $farmasi->unreadNotifications()->where('data->message', $message)->exists();
                } catch (\Exception $ex) {
                    // Fallback for DB that doesn't support JSON where: do a PHP-level check
                    $already = $farmasi->unreadNotifications->contains(function($n) use ($message) {
                        return isset($n->data['message']) && $n->data['message'] === $message;
                    });
                }

                if (!$already) {
                    $farmasi->notify(new \App\Notifications\BillingToFarmasiNotification($message));
                    $sent++;
                } else {
                    Log::info('Skipping duplicate notification for Farmasi user ID: ' . $farmasi->id);
                }

                // Also set the cache keys used by ERM NotificationController so ERM pages pick it up
                $indexKey = 'farmasi_notification_index_' . $farmasi->id;
                Cache::put($indexKey, [
                    'message' => $message,
                    'type' => 'billing',
                    'timestamp' => time()
                ], 300);

                $createKey = 'farmasi_notification_create_' . $farmasi->id;
                Cache::put($createKey, [
                    'message' => $message,
                    'type' => 'billing_create',
                    'timestamp' => time()
                ], 300);

            } catch (\Exception $e) {
                Log::error('Failed to notify Farmasi user ID: ' . $farmasi->id . ' Error: ' . $e->getMessage());
                $failed[] = $farmasi->id;
            }
        }

        Log::info('Billing -> sendNotifToFarmasi result', ['total' => $farmasis->count(), 'sent' => $sent, 'failed' => $failed]);

        return response()->json(['success' => true, 'total' => $farmasis->count(), 'sent' => $sent, 'failed' => $failed]);
    }

    /**
     * Poll for unread notifications for Farmasi
     */
    public function getNotif(Request $request)
    {
        $user = Auth::user();
        // Allow both Farmasi and Kasir users (and keep behavior for Farmasi)
        if (!$user || (! $user->hasRole('Farmasi') && ! $user->hasRole('Kasir'))) {
            return response()->json(['new' => false]);
        }
        $notif = $user->unreadNotifications()->latest()->first();
        if ($notif) {
            $notif->markAsRead();
            return response()->json(['new' => true, 'message' => $notif->data['message'] ?? '', 'sender' => $notif->data['sender'] ?? '']);
        }
        return response()->json(['new' => false]);
    }

    /**
     * Return the stock info modal HTML (used for lazy-loading on billing create page).
     */
    public function stockInfoModal()
    {
        return view('finance.billing.partials.stock-info-modal');
    }

    public function riwayatTindakanObats(Request $request)
    {
        $riwayatTindakanId = $request->query('riwayat_tindakan_id');
        if (!$riwayatTindakanId) {
            return response()->json([
                'message' => 'riwayat_tindakan_id is required',
                'data' => []
            ], 422);
        }

        $suggestedGudangId = null;
        try {
            $rt = \App\Models\ERM\RiwayatTindakan::with('tindakan')->find($riwayatTindakanId);
            if ($rt && $rt->tindakan && !empty($rt->tindakan->spesialis_id)) {
                $mapping = GudangMapping::resolveGudangForTransaction('kode_tindakan', 'spesialisasi', $rt->tindakan->spesialis_id);
                if ($mapping && !empty($mapping->gudang_id)) {
                    $suggestedGudangId = $mapping->gudang_id;
                }
            }
        } catch (\Exception $e) {
            $suggestedGudangId = null;
        }

        $rows = DB::table('erm_riwayat_tindakan_obat as rto')
            ->leftJoin('erm_obat as o', 'o.id', '=', 'rto.obat_id')
            ->select([
                'rto.obat_id',
                DB::raw('COALESCE(o.nama, "Unknown") as obat_nama'),
                DB::raw('SUM(COALESCE(rto.qty, 0)) as qty')
            ])
            ->where('rto.riwayat_tindakan_id', $riwayatTindakanId)
            ->groupBy('rto.obat_id', 'o.nama')
            ->get();

        return response()->json([
            'suggested_gudang_id' => $suggestedGudangId,
            'data' => $rows
        ]);
    }

    public function create(Request $request, $visitation_id)
{
    if ($request->ajax()) {
        $isLight = $request->boolean('light');

        // If payment was processed for this visitation's latest invoice, the billing view must be immutable.
        // Serve invoice item snapshots so changes in master data (obat/tindakan/promo) do not affect the paid invoice.
        try {
            $lockedInvoice = Invoice::with(['items', 'piutangs'])
                ->where('visitation_id', $visitation_id)
                ->orderByDesc('id')
                ->first();

            $hasPaymentProcessed = $lockedInvoice && !empty($lockedInvoice->payment_method);
            $hasStockLedger = $lockedInvoice && KartuStok::where('ref_type', 'invoice_penjualan')
                ->where('ref_id', $lockedInvoice->id)
                ->exists();

            if ($hasPaymentProcessed || $hasStockLedger) {
                $invoiceItems = $lockedInvoice ? $lockedInvoice->items()->get() : collect();

                $dt = DataTables::of($invoiceItems)->addIndexColumn();

                // Used by the frontend to refresh billingData from snapshot
                $dt->with('locked_invoice_items', 1);
                // Paid/locked invoice should never be considered "needs update" from billing
                $dt->with('invoice_needs_update', 0);

                if (!$isLight) {
                    $dt->addColumn('is_out_of_stock', function () {
                        return 0;
                    });
                }

                return $dt
                    ->addColumn('is_racikan', function () {
                        return 0;
                    })
                    ->addColumn('racikan_components', function () {
                        return [];
                    })
                    ->addColumn('racikan_obat_ids', function () {
                        return [];
                    })
                    ->addColumn('racikan_obat_list', function () {
                        return [];
                    })
                    ->addColumn('racikan_bungkus', function () {
                        return null;
                    })
                    ->addColumn('obat_id', function () {
                        return null;
                    })
                    ->addColumn('nama_item', function ($row) {
                        try {
                            return $row->name ?? '-';
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
                    ->addColumn('jumlah_raw', function ($row) {
                        return floatval($row->unit_price ?? 0);
                    })
                    ->addColumn('diskon_raw', function ($row) {
                        return floatval($row->discount ?? 0);
                    })
                    ->addColumn('diskon_type', function ($row) {
                        return $row->discount_type ?? null;
                    })
                    ->addColumn('jumlah', function ($row) {
                        $val = floatval($row->unit_price ?? 0);
                        return 'Rp ' . number_format($val, 0, ',', '.');
                    })
                    ->addColumn('qty', function ($row) {
                        return intval($row->quantity ?? 1);
                    })
                    ->addColumn('harga_akhir_raw', function ($row) {
                        return floatval($row->final_amount ?? 0);
                    })
                    ->addColumn('harga_akhir', function ($row) {
                        $val = floatval($row->final_amount ?? 0);
                        return 'Rp ' . number_format($val, 0, ',', '.');
                    })
                    ->addColumn('diskon', function ($row) {
                        $disc = floatval($row->discount ?? 0);
                        if ($disc <= 0) return '-';
                        $type = $row->discount_type ?? null;
                        if ($type === '%') {
                            return number_format($disc, 2) . '%';
                        }
                        return 'Rp ' . number_format($disc, 0, ',', '.');
                    })
                    ->addColumn('deskripsi', function ($row) {
                        try {
                            return $row->description ?? '-';
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
                    ->make(true);
            }
        } catch (\Exception $e) {
            // Non-fatal: fallback to dynamic billing list
        }

        // Eager-load billable (morphTo) to avoid N+1 queries on every refresh.
        // Always order deterministically to keep grouped rows stable across refreshes.
        $billings = Billing::with('billable')
            ->where('visitation_id', $visitation_id)
            ->orderBy('id')
            ->get();
        try {
            $billings->loadMorph('billable', [
                \App\Models\ERM\ResepFarmasi::class => ['obat:id,nama,harga_net,harga_diskon'],
                \App\Models\ERM\LabPermintaan::class => ['labTest:id,nama'],
                \App\Models\ERM\RadiologiPermintaan::class => ['radiologiTest:id,nama'],
                \App\Models\ERM\RiwayatTindakan::class => ['tindakan:id,spesialis_id'],
            ]);
        } catch (\Exception $e) {
            // Non-fatal: fallback to lazy-loading if morph eager-loading fails.
        }

        // Extract racikan items, pharmacy fees, and regular items
        $racikanGroups = [];
        $pharmacyFeeItems = []; 
        $regularBillings = [];

        foreach ($billings as $billing) {
            // Case 1: Pharmacy fee items (tuslah & embalase)
            if (
    $billing->billable_type == 'App\Models\ERM\JasaFarmasi' || 
    (isset($billing->keterangan) && preg_match('/(tuslah|embalase)/i', $billing->keterangan)) ||
    (isset($billing->nama_item) && preg_match('/(tuslah|embalase)/i', $billing->nama_item))
) {
    $pharmacyFeeItems[] = $billing;
}
            // Case 2: Racikan medication items
            else if (
                $billing->billable_type == 'App\Models\ERM\ResepFarmasi' &&
                optional($billing->billable)->racikan_ke != null &&
                optional($billing->billable)->racikan_ke > 0
            ) {
                $racikanKey = optional($billing->billable)->racikan_ke;
                if (!isset($racikanGroups[$racikanKey])) {
                    $racikanGroups[$racikanKey] = [];
                }
                $racikanGroups[$racikanKey][] = $billing;
            }
            // Case 3: Skip bundled obat items (don't show in billing list)
            else if (
                $billing->billable_type == 'App\Models\ERM\Obat' &&
                isset($billing->keterangan) && 
                str_contains($billing->keterangan, 'Obat Bundled:')
            ) {
                // Skip bundled obat - don't add to any display array
                continue;
            }
            // Case 4: Regular billing items
            else {
                $regularBillings[] = $billing;
            }
        }

        // Create processed billing items (start with regular items)
        $processedBillings = $regularBillings;

        // Preload active PaketRacikans with details for matching
        $activePaketRacikans = PaketRacikan::with(['details' => function($q){ $q->select('id','paket_racikan_id','obat_id','dosis'); }])
            ->where('is_active', true)
            ->get(['id','nama_paket','is_active']);

        // Apply active promos to processed billing items: set diskon (%) when promo matches
        try {
            $today = \Carbon\Carbon::today()->format('Y-m-d');

            // Collect all candidate IDs first, then query promo items once.
            $rowCandidatesByIndex = []; // pbIndex => [candidateId...]
            $allCandidateIds = [];

            foreach ($processedBillings as $pbIndex => $pb) {
                // skip racikan and pharmacy fee rows
                if (isset($pb->is_racikan) || isset($pb->is_pharmacy_fee)) continue;

                // If user already set a manual discount on this billing row, never override it with promo.
                // This prevents the UI from "reverting" discount values after refresh/update.
                if (floatval($pb->diskon ?? 0) > 0) {
                    continue;
                }

                // Skip promo application for zero-priced billing rows (e.g., gratis items)
                if (floatval($pb->jumlah ?? 0) <= 0) {
                    continue;
                }

                // Collect candidate IDs to match PromoItem.item_id.
                // Keep behavior compatible with previous implementation.
                $candidates = [];
                if (isset($pb->billable_id)) $candidates[] = intval($pb->billable_id);
                try {
                    if (isset($pb->billable) && isset($pb->billable->obat) && isset($pb->billable->obat->id)) $candidates[] = intval($pb->billable->obat->id);
                    if (isset($pb->billable) && isset($pb->billable->tindakan_id)) $candidates[] = intval($pb->billable->tindakan_id);
                    if (isset($pb->billable) && isset($pb->billable->id)) $candidates[] = intval($pb->billable->id);
                } catch (\Exception $e) {
                    // ignore
                }
                $candidates = array_values(array_filter(array_unique($candidates)));
                if (empty($candidates)) continue;

                $rowCandidatesByIndex[$pbIndex] = $candidates;
                $allCandidateIds = array_merge($allCandidateIds, $candidates);
            }

            $allCandidateIds = array_values(array_unique(array_filter($allCandidateIds)));
            if (!empty($allCandidateIds)) {
                $promoItems = \App\Models\Marketing\PromoItem::whereIn('item_id', $allCandidateIds)
                    ->whereIn('item_type', ['tindakan', 'obat'])
                    ->whereHas('promo', function($q) use ($today){
                        $q->where(function($q2) use ($today){
                            $q2->whereNotNull('start_date')->whereNotNull('end_date')
                                ->where('start_date','<=',$today)
                                ->where('end_date','>=',$today);
                        })->orWhere(function($q2) use ($today){
                            $q2->whereNotNull('start_date')->whereNull('end_date')
                                ->where('start_date','<=',$today);
                        })->orWhere(function($q2) use ($today){
                            $q2->whereNull('start_date')->whereNotNull('end_date')
                                ->where('end_date','>=',$today);
                        });
                    })
                    ->get(['id', 'item_id', 'item_type', 'discount_percent']);

                if (!$promoItems->isEmpty()) {
                    // Group promo items by (type|id) for fast lookup
                    $promoByKey = []; // 'obat|123' => [PromoItem...]
                    $promoObatIds = [];
                    $promoTindakanIds = [];

                    foreach ($promoItems as $pi) {
                        $type = (string)($pi->item_type ?? '');
                        $id = intval($pi->item_id ?? 0);
                        if (!$type || !$id) continue;
                        $k = $type . '|' . $id;
                        if (!isset($promoByKey[$k])) $promoByKey[$k] = [];
                        $promoByKey[$k][] = $pi;
                        if ($type === 'obat') $promoObatIds[] = $id;
                        if ($type === 'tindakan') $promoTindakanIds[] = $id;
                    }

                    // Prefetch base prices in bulk
                    $promoObatIds = array_values(array_unique(array_filter($promoObatIds)));
                    $promoTindakanIds = array_values(array_unique(array_filter($promoTindakanIds)));

                    $obatPriceById = [];
                    if (!empty($promoObatIds)) {
                        $obats = \App\Models\ERM\Obat::withInactive()
                            ->whereIn('id', $promoObatIds)
                            ->get(['id', 'harga_net', 'harga_diskon']);
                        foreach ($obats as $o) {
                            $obatPriceById[intval($o->id)] = $o->harga_diskon ?? $o->harga_net ?? null;
                        }
                    }

                    $tindakanPriceById = [];
                    if (!empty($promoTindakanIds)) {
                        $tindakans = \App\Models\ERM\Tindakan::whereIn('id', $promoTindakanIds)
                            ->get(['id', 'harga', 'harga_diskon']);
                        foreach ($tindakans as $t) {
                            $tindakanPriceById[intval($t->id)] = $t->harga_diskon ?? $t->harga ?? null;
                        }
                    }

                    // Apply promos per row by choosing the highest discount_percent among matching candidates
                    foreach ($rowCandidatesByIndex as $pbIndex => $candidates) {
                        $pb = $processedBillings[$pbIndex] ?? null;
                        if (!$pb) continue;

                        // Respect manual discount (do not apply promo)
                        if (floatval($pb->diskon ?? 0) > 0) {
                            continue;
                        }

                        $bestPromo = null;
                        $bestPercent = 0;

                        foreach ($candidates as $candidateIdRaw) {
                            $candidateId = intval($candidateIdRaw);
                            if (!$candidateId) continue;

                            foreach (['obat', 'tindakan'] as $type) {
                                $k = $type . '|' . $candidateId;
                                if (empty($promoByKey[$k])) continue;
                                foreach ($promoByKey[$k] as $pi) {
                                    $p = floatval($pi->discount_percent ?? 0);
                                    if ($p > $bestPercent) {
                                        $bestPercent = $p;
                                        $bestPromo = $pi;
                                    }
                                }
                            }
                        }

                        if ($bestPromo && $bestPercent > 0) {
                            $basePrice = null;
                            $promoItemId = intval($bestPromo->item_id ?? 0);
                            $promoType = (string)($bestPromo->item_type ?? '');

                            if ($promoType === 'tindakan') {
                                $basePrice = $tindakanPriceById[$promoItemId] ?? null;
                            } elseif ($promoType === 'obat') {
                                $basePrice = $obatPriceById[$promoItemId] ?? null;
                            }

                            // Common fallbacks
                            if (!$basePrice && isset($pb->billable)) {
                                $basePrice = $pb->billable->harga_diskon ?? $pb->billable->unit_price ?? null;
                            }
                            if (!$basePrice) {
                                $basePrice = $pb->jumlah ?? 0;
                            }

                            // Only apply promo discount when we have a valid base price (> 0)
                            if ($basePrice && $basePrice > 0) {
                                $pb->diskon = $bestPercent;
                                $pb->diskon_type = '%';
                                $pb->promo_price_base = $basePrice;
                            }
                        }

                        // write back
                        $processedBillings[$pbIndex] = $pb;
                    }
                }
            }
        } catch (\Exception $e) {
            // if promo application fails, continue without promo discounts
            Log::warning('Failed to apply promos to billing rows: '.$e->getMessage());
        }

        // Process each racikan group
        foreach ($racikanGroups as $racikanKey => $racikanItems) {
            // Use the first item as base
            $firstItem = $racikanItems[0];

            // Calculate total price for the racikan
            $totalPrice = 0;
            $obatList = [];
            $obatIds = [];
            $bungkus = 0;

            // Build component list using ResepFarmasi table as the source of truth.
            // Rule: all ResepFarmasi rows sharing (visitation_id + racikan_ke) belong to the same racikan.
            $visitationIdForRacikan = $firstItem->visitation_id ?? $visitation_id;
            $racikanReseps = collect();
            try {
                $racikanReseps = ResepFarmasi::with(['obat:id,nama'])
                    ->where('visitation_id', $visitationIdForRacikan)
                    ->where('racikan_ke', $racikanKey)
                    ->orderBy('id')
                    ->get();
            } catch (\Exception $e) {
                $racikanReseps = collect();
            }

            $components = [];
            if (!$racikanReseps->isEmpty()) {
                foreach ($racikanReseps as $rf) {
                    $obatId = $rf->obat_id ?? null;
                    if (!empty($obatId)) {
                        $obatIds[] = $obatId;
                    }

                    $nama = null;
                    try {
                        $nama = optional($rf->obat)->nama;
                    } catch (\Exception $e) {
                        $nama = null;
                    }
                    $nama = ($nama ?: (!empty($obatId) ? ('Obat #' . $obatId) : 'Obat Tidak Diketahui'));
                    $obatList[] = $nama;

                    if (empty($bungkus) && !empty($rf->bungkus)) {
                        $bungkus = $rf->bungkus;
                    }

                    $components[] = [
                        'obat_id' => $obatId,
                        'nama' => $nama,
                        // stok_dikurangi persisted into ResepFarmasi.jumlah for racikan components (allow decimals)
                        'stok_dikurangi' => floatval($rf->jumlah ?? 0),
                        'bungkus' => isset($rf->bungkus) ? floatval($rf->bungkus) : null,
                        'dosis' => isset($rf->dosis) ? trim((string)$rf->dosis) : null,
                    ];
                }

                $obatIds = array_values(array_unique(array_filter($obatIds)));
            }

            foreach ($racikanItems as $item) {
                $totalPrice += $item->jumlah;

                // Keep a fallback for bungkus when ResepFarmasi query failed
                if (empty($bungkus)) {
                    try {
                        $bungkus = optional($item->billable)->bungkus ?? 0;
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            }

            // Aggregate discount for grouped racikan row so UI doesn't "revert" after refresh.
            // Rule:
            // - If any nominal discounts exist in the group, show total nominal discount.
            // - Else if percent discounts exist and all percent values match, show that percent.
            $groupNominalDiscount = 0.0;
            $groupPercentDiscounts = [];
            try {
                foreach ($racikanItems as $it) {
                    $dv = floatval($it->diskon ?? 0);
                    if ($dv <= 0) continue;
                    $dt = trim((string)($it->diskon_type ?? ''));
                    if ($dt === '%') {
                        $groupPercentDiscounts[] = $dv;
                    } else {
                        $groupNominalDiscount += $dv;
                    }
                }
            } catch (\Exception $e) {
                $groupNominalDiscount = 0.0;
                $groupPercentDiscounts = [];
            }

            // Clone the first item and modify its properties for display
            $racikanItem = clone $firstItem;
            $racikanItem->is_racikan = true;
            $racikanItem->racikan_obat_list = $obatList;
            // Expose obat IDs so frontend can fetch stock for each component
            $racikanItem->racikan_obat_ids = $obatIds;
            $racikanItem->racikan_total_price = $totalPrice;
            $racikanItem->racikan_bungkus = $bungkus;
            $racikanItem->racikan_ke = $racikanKey;
            $racikanItem->nama_item = 'Racikan ' . $racikanKey; // Explicitly set the name with racikan number

            // Attach aggregated discount to grouped racikan row
            if ($groupNominalDiscount > 0) {
                $racikanItem->diskon = $groupNominalDiscount;
                $racikanItem->diskon_type = 'nominal';
            } elseif (!empty($groupPercentDiscounts)) {
                $unique = array_values(array_unique(array_map(function($v){ return (string)$v; }, $groupPercentDiscounts)));
                if (count($unique) === 1) {
                    $racikanItem->diskon = floatval($groupPercentDiscounts[0]);
                    $racikanItem->diskon_type = '%';
                } else {
                    // Mixed percent values; fall back to no grouped discount display
                    $racikanItem->diskon = 0;
                    $racikanItem->diskon_type = null;
                }
            } else {
                $racikanItem->diskon = 0;
                $racikanItem->diskon_type = null;
            }

            // Prefer using the ResepFarmasi model's built-in paket resolution.
            // If all components point to the same paket name, use it for the grouped racikan row.
            try {
                $paketNames = [];
                foreach ($racikanItems as $it) {
                    $resep = $it->billable ?? null;
                    if (!$resep) continue;
                    $pn = null;
                    try {
                        $pn = $resep->paket_racikan_name ?? null;
                    } catch (\Exception $e) {
                        $pn = null;
                    }
                    if ($pn) {
                        $paketNames[] = trim((string)$pn);
                    }
                }
                $paketNames = array_values(array_unique(array_filter($paketNames)));
                if (count($paketNames) === 1) {
                    $racikanItem->paket_racikan_name = $paketNames[0];
                }
            } catch (\Exception $e) {
                // ignore
            }

            // If the ResepFarmasi query failed (should be rare), fall back to building components from billing rows
            if (empty($components)) {
                foreach ($racikanItems as $it) {
                    try {
                        $billable = $it->billable ?? null;
                        $obatModel = $billable ? $billable->obat : null;
                        $obatId = $billable ? ($billable->obat_id ?? null) : null;
                        $nama = $obatModel ? ($obatModel->nama ?? '') : (!empty($obatId) ? ('Obat #' . $obatId) : 'Obat Tidak Diketahui');
                        $components[] = [
                            'obat_id' => $obatId,
                            'nama' => $nama,
                            'stok_dikurangi' => $billable ? floatval($billable->jumlah ?? 0) : 0,
                            'bungkus' => $billable ? (isset($billable->bungkus) ? floatval($billable->bungkus) : null) : null,
                            'dosis' => isset($billable->dosis) ? trim((string)$billable->dosis) : null,
                        ];
                    } catch (\Exception $e) {
                        $components[] = ['obat_id' => null, 'nama' => '', 'stok_dikurangi' => 0, 'bungkus' => null, 'dosis' => null];
                    }
                }
            }
            $racikanItem->racikan_components = $components;

            // Try to match components with an active PaketRacikan (obat_id + dosis match, order-insensitive)
            // Only do this when we couldn't resolve paket name from ResepFarmasi model.
            try {
                if (isset($racikanItem->paket_racikan_name) && $racikanItem->paket_racikan_name) {
                    // already resolved
                } else {
                // Helper to normalize dosis: extract numeric part if present, else trimmed lower-case string
                $normalizeDose = function($val) {
                    if ($val === null) return '';
                    $s = trim(strtolower((string)$val));
                    // replace commas with dots, remove thousands separators
                    $s = str_replace([','], ['.'], $s);
                    // extract first number (integer/decimal)
                    if (preg_match('/\d+(?:\.\d+)?/', $s, $m)) {
                        return rtrim(rtrim($m[0], '0'), '.') ?: $m[0];
                    }
                    return $s; // fallback: raw string
                };
                // Build a normalized map from components
                $compMap = [];
                foreach ($components as $c) {
                    if (!$c['obat_id']) continue;
                    $key = $c['obat_id'] . '|' . $normalizeDose($c['dosis'] ?? '');
                    $compMap[$key] = true;
                }

                foreach ($activePaketRacikans as $paket) {
                    $details = $paket->details;
                    if (!$details || $details->count() === 0) continue;
                    if ($details->count() !== count($compMap)) continue; // quick size check

                    $allMatch = true;
                    foreach ($details as $d) {
                        $dKey = ($d->obat_id ?? '0') . '|' . $normalizeDose($d->dosis ?? '');
                        if (!isset($compMap[$dKey])) {
                            $allMatch = false;
                            break;
                        }
                    }

                    if ($allMatch) {
                        // Found matching paket
                        $racikanItem->paket_racikan_name = $paket->nama_paket;
                        break;
                    }
                }
                }
            } catch (\Exception $e) {
                // non-fatal: leave paket name unset
                Log::warning('Racikan paket matching failed: ' . $e->getMessage());
            }

            $processedBillings[] = $racikanItem;
        }

        // Process pharmacy fee items if any
        if (!empty($pharmacyFeeItems)) {
            // Use the first item as base
            $firstPharmacyFee = $pharmacyFeeItems[0];
            
            // Calculate total pharmacy fees
            $totalFees = 0;
            $feeDescriptions = [];
            
            foreach ($pharmacyFeeItems as $item) {
                $totalFees += $item->jumlah;
                
                // Extract description based on available data
                $desc = '';
if (isset($item->keterangan)) {
    // Simply use the keterangan field directly now that it's simplified
    $desc = $item->keterangan;
} else if (isset($item->nama_item)) {
    $desc = $item->nama_item;
}

if (!empty($desc) && !in_array($desc, $feeDescriptions)) {
    $feeDescriptions[] = $desc;
}
            }
            
            // Create a grouped pharmacy service item
            $pharmacyServiceItem = clone $firstPharmacyFee;
            $pharmacyServiceItem->is_pharmacy_fee = true;
            $pharmacyServiceItem->fee_descriptions = $feeDescriptions;
            $pharmacyServiceItem->fee_total_price = $totalFees;
            $pharmacyServiceItem->fee_items_count = count($pharmacyFeeItems);
            
            $processedBillings[] = $pharmacyServiceItem;
        }

        // Pre-compute out-of-stock flags for fast initial UI rendering (obat/racikan/tindakan)
        // This mirrors the frontend stock modal logic: sum ObatStokGudang across batches.
        // NOTE: This is expensive; skip it in `light=1` (polling refresh), and cache briefly otherwise.
        $outOfStockByBillingId = [];
        if (!$isLight) {
            try {
                $maxUpdatedAt = 0;
                try {
                    $maxUpdatedAt = intval($billings->max(function($b) {
                        try {
                            return optional($b->updated_at)->timestamp ?? 0;
                        } catch (\Exception $e) {
                            return 0;
                        }
                    }) ?? 0);
                } catch (\Exception $e) {
                    $maxUpdatedAt = 0;
                }

                $billingsCount = method_exists($billings, 'count') ? intval($billings->count()) : 0;
                $cacheKey = 'billing:create:oos:' . intval($visitation_id) . ':' . $billingsCount . ':' . $maxUpdatedAt;

                $outOfStockByBillingId = Cache::remember($cacheKey, now()->addSeconds(10), function() use ($processedBillings) {
                    $out = [];
                    try {
                        $requirementsByBillingId = []; // billing_id => [ ['obat_id'=>..,'gudang_id'=>..,'needed'=>..], ... ]
                        $allObatIds = [];
                        $allGudangIds = [];

                        $resolveGudangId = function($mapping) {
                            if (is_object($mapping)) return $mapping->gudang_id ?? null;
                            return $mapping;
                        };

                        $fallbackGudang = Gudang::query()->select('id')->first();

                        $defaultResepGudangId = $resolveGudangId(GudangMapping::resolveGudangForTransaction('resep'));
                        if (!$defaultResepGudangId) {
                            $defaultResepGudangId = $fallbackGudang ? $fallbackGudang->id : null;
                        }

                        $defaultKodeTindakanGudangId = $resolveGudangId(GudangMapping::resolveGudangForTransaction('kode_tindakan'));
                        if (!$defaultKodeTindakanGudangId) {
                            $defaultKodeTindakanGudangId = $fallbackGudang ? $fallbackGudang->id : null;
                        }

                        $tindakanBillingToRiwayat = []; // billing_id => riwayat_tindakan_id
                        $riwayatIds = [];

                        foreach ($processedBillings as $row) {
                            if (!$row || !isset($row->id)) continue;
                            $billingId = $row->id;

                            // Skip non-stock rows
                            if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) continue;

                            // Racikan: check each component
                            if (isset($row->is_racikan) && $row->is_racikan) {
                                $components = $row->racikan_components ?? [];
                                if (is_array($components) && $defaultResepGudangId) {
                                    foreach ($components as $c) {
                                        $obatId = $c['obat_id'] ?? null;
                                        if (!$obatId) continue;
                                        $needed = abs(floatval($c['stok_dikurangi'] ?? 0));
                                        if ($needed <= 0) {
                                            $bungkus = abs(floatval($row->racikan_bungkus ?? 0));
                                            $needed = $bungkus > 0 ? $bungkus : 1;
                                        }
                                        $requirementsByBillingId[$billingId][] = [
                                            'obat_id' => intval($obatId),
                                            'gudang_id' => intval($defaultResepGudangId),
                                            'needed' => floatval($needed),
                                        ];
                                        $allObatIds[] = intval($obatId);
                                        $allGudangIds[] = intval($defaultResepGudangId);
                                    }
                                }
                                continue;
                            }

                            // Tindakan: resolve from pivot table later
                            if (isset($row->billable_type) && $row->billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                                $riwayatId = $row->billable_id ?? null;
                                if ($riwayatId) {
                                    $tindakanBillingToRiwayat[$billingId] = intval($riwayatId);
                                    $riwayatIds[] = intval($riwayatId);
                                }
                                continue;
                            }

                            // Obat (single)
                            $obatId = null;
                            $needed = null;
                            try {
                                if (isset($row->billable_type) && $row->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                                    $obatId = optional(optional($row->billable)->obat)->id;
                                    $needed = abs(floatval(optional($row->billable)->jumlah ?? ($row->qty ?? 1)));
                                } elseif (isset($row->billable_type) && $row->billable_type === 'App\\Models\\ERM\\Obat') {
                                    $obatId = $row->billable_id ?? null;
                                    $needed = abs(floatval($row->qty ?? 1));
                                } elseif (isset($row->obat_id) && $row->obat_id) {
                                    $obatId = $row->obat_id;
                                    $needed = abs(floatval($row->qty ?? 1));
                                }
                            } catch (\Exception $e) {
                                $obatId = null;
                                $needed = null;
                            }

                            if ($obatId && $defaultResepGudangId) {
                                $needed = ($needed !== null && $needed > 0) ? $needed : 1;
                                $requirementsByBillingId[$billingId][] = [
                                    'obat_id' => intval($obatId),
                                    'gudang_id' => intval($defaultResepGudangId),
                                    'needed' => floatval($needed),
                                ];
                                $allObatIds[] = intval($obatId);
                                $allGudangIds[] = intval($defaultResepGudangId);
                            }
                        }

                        // Tindakan pivot requirements
                        $riwayatIds = array_values(array_unique(array_filter($riwayatIds)));
                        if (!empty($riwayatIds)) {
                            // Prefetch spesialis_id per riwayat tindakan
                            $spesialisByRiwayat = [];
                            try {
                                $riwayats = \App\Models\ERM\RiwayatTindakan::with(['tindakan:id,spesialis_id'])
                                    ->whereIn('id', $riwayatIds)
                                    ->get(['id', 'tindakan_id']);
                                foreach ($riwayats as $rt) {
                                    $spesialisByRiwayat[$rt->id] = optional($rt->tindakan)->spesialis_id;
                                }
                            } catch (\Exception $e) {
                                // ignore
                            }

                            $gudangBySpesialis = [];
                            $resolveKodeTindakanGudangForRiwayat = function($riwayatId) use (&$spesialisByRiwayat, &$gudangBySpesialis, $defaultKodeTindakanGudangId, $resolveGudangId) {
                                $spesialisId = $spesialisByRiwayat[$riwayatId] ?? null;
                                if (!$spesialisId) return $defaultKodeTindakanGudangId;
                                if (isset($gudangBySpesialis[$spesialisId])) return $gudangBySpesialis[$spesialisId];
                                $mapping = GudangMapping::resolveGudangForTransaction('kode_tindakan', 'spesialisasi', $spesialisId);
                                $gid = $resolveGudangId($mapping) ?: $defaultKodeTindakanGudangId;
                                $gudangBySpesialis[$spesialisId] = $gid;
                                return $gid;
                            };

                            $pivotRows = DB::table('erm_riwayat_tindakan_obat')
                                ->select([
                                    'riwayat_tindakan_id',
                                    'obat_id',
                                    DB::raw('SUM(COALESCE(qty, 0)) as qty')
                                ])
                                ->whereIn('riwayat_tindakan_id', $riwayatIds)
                                ->groupBy('riwayat_tindakan_id', 'obat_id')
                                ->get();

                            // Invert mapping: riwayat_id -> billing_id
                            $billingByRiwayat = [];
                            foreach ($tindakanBillingToRiwayat as $billingId => $riwayatId) {
                                $billingByRiwayat[intval($riwayatId)] = intval($billingId);
                            }

                            foreach ($pivotRows as $p) {
                                $riwayatId = intval($p->riwayat_tindakan_id ?? 0);
                                $billingId = $billingByRiwayat[$riwayatId] ?? null;
                                if (!$billingId) continue;
                                $obatId = intval($p->obat_id ?? 0);
                                if (!$obatId) continue;
                                $needed = abs(floatval($p->qty ?? 0));
                                if ($needed <= 0) continue;

                                $gudangId = $resolveKodeTindakanGudangForRiwayat($riwayatId);
                                if (!$gudangId) continue;

                                $requirementsByBillingId[$billingId][] = [
                                    'obat_id' => $obatId,
                                    'gudang_id' => intval($gudangId),
                                    'needed' => floatval($needed),
                                ];
                                $allObatIds[] = $obatId;
                                $allGudangIds[] = intval($gudangId);
                            }
                        }

                        $allObatIds = array_values(array_unique(array_filter($allObatIds)));
                        $allGudangIds = array_values(array_unique(array_filter($allGudangIds)));

                        $stockTotals = [];
                        if (!empty($allObatIds) && !empty($allGudangIds)) {
                            $rows = ObatStokGudang::query()
                                ->select(['obat_id', 'gudang_id', DB::raw('SUM(stok) as total')])
                                ->whereIn('obat_id', $allObatIds)
                                ->whereIn('gudang_id', $allGudangIds)
                                ->groupBy('obat_id', 'gudang_id')
                                ->get();
                            foreach ($rows as $r) {
                                $key = intval($r->obat_id) . '|' . intval($r->gudang_id);
                                $stockTotals[$key] = floatval($r->total ?? 0);
                            }
                        }

                        foreach ($requirementsByBillingId as $billingId => $reqs) {
                            $isLow = false;
                            foreach ($reqs as $req) {
                                $needed = floatval($req['needed'] ?? 0);
                                if ($needed <= 0) continue;
                                $key = intval($req['obat_id']) . '|' . intval($req['gudang_id']);
                                $avail = floatval($stockTotals[$key] ?? 0);
                                if ($avail < $needed) {
                                    $isLow = true;
                                    break;
                                }
                            }
                            if ($isLow) {
                                $out[intval($billingId)] = true;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to precompute out-of-stock flags (cached): ' . $e->getMessage());
                        $out = [];
                    }
                    return $out;
                });
            } catch (\Exception $e) {
                Log::warning('Failed to precompute out-of-stock flags: ' . $e->getMessage());
                $outOfStockByBillingId = [];
            }
        }

        // Reduce payload size: keep relations available for server-side calculations,
        // but don't serialize them into the DataTables JSON.
        try {
            foreach ($processedBillings as $row) {
                if ($row instanceof \Illuminate\Database\Eloquent\Model) {
                    $row->makeHidden(['billable']);
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        try {
            $dt = DataTables::of($processedBillings)->addIndexColumn();
            if (!$isLight) {
                $dt->addColumn('is_out_of_stock', function ($row) use ($outOfStockByBillingId) {
                    try {
                        $id = isset($row->id) ? intval($row->id) : null;
                        return ($id && !empty($outOfStockByBillingId[$id])) ? 1 : 0;
                    } catch (\Exception $e) {
                        return 0;
                    }
                });
            }

            // Expose a lightweight "invoice out-of-date" flag so the UI can switch to "Update Invoice"
            // without requiring a full page refresh.
            $invoiceNeedsUpdate = false;
            try {
                $latestInvoice = Invoice::where('visitation_id', $visitation_id)->latest()->first();
                if ($latestInvoice) {
                    $maxBillingUpdatedAt = null;
                    try {
                        $maxBillingUpdatedAt = $billings->max('updated_at');
                    } catch (\Exception $e) {
                        $maxBillingUpdatedAt = null;
                    }
                    if ($maxBillingUpdatedAt && $latestInvoice->updated_at) {
                        $invoiceNeedsUpdate = \Carbon\Carbon::parse($maxBillingUpdatedAt)->gt($latestInvoice->updated_at);
                    }
                }
            } catch (\Exception $e) {
                $invoiceNeedsUpdate = false;
            }
            $dt->with('invoice_needs_update', $invoiceNeedsUpdate ? 1 : 0);

            return $dt
            ->addColumn('is_racikan', function ($row) {
                try {
                    return (!empty($row->is_racikan)) ? 1 : 0;
                } catch (\Exception $e) {
                    return 0;
                }
            })
            ->addColumn('racikan_components', function ($row) {
                try {
                    if (!empty($row->is_racikan) && isset($row->racikan_components) && is_array($row->racikan_components)) {
                        return $row->racikan_components;
                    }
                } catch (\Exception $e) {
                    // ignore
                }
                return [];
            })
            ->addColumn('racikan_obat_ids', function ($row) {
                try {
                    if (!empty($row->is_racikan) && isset($row->racikan_obat_ids) && is_array($row->racikan_obat_ids)) {
                        return $row->racikan_obat_ids;
                    }
                } catch (\Exception $e) {
                    // ignore
                }
                return [];
            })
            ->addColumn('racikan_obat_list', function ($row) {
                try {
                    if (!empty($row->is_racikan) && isset($row->racikan_obat_list) && is_array($row->racikan_obat_list)) {
                        return $row->racikan_obat_list;
                    }
                } catch (\Exception $e) {
                    // ignore
                }
                return [];
            })
            ->addColumn('racikan_bungkus', function ($row) {
                try {
                    if (!empty($row->is_racikan) && isset($row->racikan_bungkus)) {
                        return $row->racikan_bungkus;
                    }
                } catch (\Exception $e) {
                    // ignore
                }
                return null;
            })
            ->addColumn('obat_id', function ($row) {
                try {
                    // If this billing row is a ResepFarmasi, expose obat id for frontend
                    if ($row->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                        $resep = $row->billable;
                        if ($resep) {
                            // Prefer the FK field (works even if relation is missing)
                            if (!empty($resep->obat_id)) return $resep->obat_id;
                            // Fallback to relation when available
                            if (isset($resep->obat) && !empty($resep->obat->id)) return $resep->obat->id;
                        }
                    }

                    // If billing row itself references an Obat directly
                    if ($row->billable_type == 'App\\Models\\ERM\\Obat') {
                        return $row->billable_id;
                    }
                } catch (\Exception $e) {
                    // swallow and return null
                }
                return null;
            })
            ->addColumn('nama_item', function ($row) {
                    // Use optional() to avoid "property on null" errors when relations are missing
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        // Prefer displaying matched PaketRacikan name if available
                        if (isset($row->paket_racikan_name) && $row->paket_racikan_name) {
                            return $row->paket_racikan_name;
                        }
                        return 'Obat Racikan';
                    } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                        return 'Jasa Farmasi';
                    } else if ($row->billable_type == 'App\Models\ERM\ResepFarmasi') {
                        $namaObat = null;
                        try {
                            $namaObat = optional(optional($row->billable)->obat)->nama;
                        } catch (\Exception $e) {
                            $namaObat = null;
                        }

                        if (!empty($namaObat)) {
                            return $namaObat;
                        }

                        // Fallbacks when relation is missing (e.g. resep deleted):
                        if (!empty($row->nama_item)) return $row->nama_item;
                        if (!empty($row->keterangan)) {
                            // strip common prefix
                            return preg_replace('/^Obat:\s*/i', '', (string)$row->keterangan);
                        }
                        return '-';
                    } else if ($row->billable_type == 'App\Models\ERM\LabPermintaan') {
                        $labName = optional(optional($row->billable)->labTest)->nama;
                        return 'Lab: ' . ($labName ?? preg_replace('/^Lab: /', '', $row->keterangan ?? 'Test'));
                    } else if ($row->billable_type == 'App\Models\ERM\RadiologiPermintaan') {
                        $radName = optional(optional($row->billable)->radiologiTest)->nama;
                        return 'Radiologi: ' . ($radName ?? preg_replace('/^Radiologi: /', '', $row->keterangan ?? 'Test'));
                    } else {
                        return $row->nama_item ?? optional($row->billable)->nama ?? $row->keterangan ?? '-';
                    }
            })
            ->addColumn('jumlah_raw', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_total_price;
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return $row->fee_total_price;
                }
                return $row->jumlah ?? 0;
            })
            ->addColumn('diskon_raw', function ($row) {
                return $row->diskon ?? '';
            })
            ->addColumn('jumlah', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return 'Rp ' . number_format($row->racikan_total_price, 0, ',', '.');
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 'Rp ' . number_format($row->fee_total_price, 0, ',', '.');
                }
                return 'Rp ' . number_format($row->jumlah, 0, ',', '.');
            })
            ->addColumn('harga_akhir_raw', function ($row) {
                // For pharmacy fees
                if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return $row->fee_total_price;
                }
                
                // For racikan items
                if (isset($row->is_racikan) && $row->is_racikan) {
                    $qty = floatval($row->racikan_bungkus ?? 0);
                    $lineNoDisc = floatval($row->racikan_total_price) * $qty;
                    $discountVal = floatval($row->diskon ?? 0);
                    $lineAfter = $lineNoDisc;
                    if ($discountVal > 0) {
                        if (($row->diskon_type ?? null) === '%') {
                            $lineAfter = $lineNoDisc - ($lineNoDisc * ($discountVal / 100));
                        } else {
                            $lineAfter = $lineNoDisc - $discountVal;
                        }
                    }
                    return max(0, $lineAfter);
                }

                // Get quantity
                $qty = isset($row->qty) ? $row->qty : (
                    $row->billable_type == 'App\\Models\\ERM\\ResepFarmasi'
                        ? ($row->billable->jumlah ?? 1)
                        : ($row->billable->qty ?? 1)
                );
                $qty = floatval($qty ?: 1);

                // Base unit price (harga)
                // If a promo_price_base is provided (promo applies and uses a special base), prefer it for percentage discounts
                $unitBase = floatval($row->jumlah ?? 0);
                if (isset($row->promo_price_base) && $row->promo_price_base && $row->diskon_type == '%') {
                    $unitBase = floatval($row->promo_price_base);
                }

                $lineNoDisc = $unitBase * $qty;
                $discountVal = floatval($row->diskon ?? 0);

                // Apply discount: percent applies proportionally; nominal is treated as LINE discount
                $lineAfter = $lineNoDisc;
                if ($discountVal > 0) {
                    if ($row->diskon_type == '%') {
                        $lineAfter = $lineNoDisc - ($lineNoDisc * ($discountVal / 100));
                    } else {
                        $lineAfter = $lineNoDisc - $discountVal;
                    }
                }

                return max(0, $lineAfter);
            })
            ->addColumn('harga_akhir', function ($row) {
                // For pharmacy fees
                if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 'Rp ' . number_format($row->fee_total_price, 0, ',', '.');
                }
                
                // For racikan items
                if (isset($row->is_racikan) && $row->is_racikan) {
                    $qty = floatval($row->racikan_bungkus ?? 0);
                    $lineNoDisc = floatval($row->racikan_total_price) * $qty;
                    $discountVal = floatval($row->diskon ?? 0);
                    $lineAfter = $lineNoDisc;
                    if ($discountVal > 0) {
                        if (($row->diskon_type ?? null) === '%') {
                            $lineAfter = $lineNoDisc - ($lineNoDisc * ($discountVal / 100));
                        } else {
                            $lineAfter = $lineNoDisc - $discountVal;
                        }
                    }
                    $lineAfter = max(0, $lineAfter);
                    return 'Rp ' . number_format($lineAfter, 0, ',', '.');
                }

                // Quantity
                $qty = isset($row->qty) ? $row->qty : (
                    $row->billable_type == 'App\\Models\\ERM\\ResepFarmasi'
                        ? ($row->billable->jumlah ?? 1)
                        : ($row->billable->qty ?? 1)
                );
                $qty = floatval($qty ?: 1);

                // Base unit price
                $unitBase = floatval($row->jumlah ?? 0);
                if (isset($row->promo_price_base) && $row->promo_price_base && $row->diskon_type == '%') {
                    $unitBase = floatval($row->promo_price_base);
                }

                $lineNoDisc = $unitBase * $qty;
                $discountVal = floatval($row->diskon ?? 0);
                $lineAfter = $lineNoDisc;
                if ($discountVal > 0) {
                    if ($row->diskon_type == '%') {
                        $lineAfter = $lineNoDisc - ($lineNoDisc * ($discountVal / 100));
                    } else {
                        // Nominal discount is treated as LINE discount
                        $lineAfter = $lineNoDisc - $discountVal;
                    }
                }
                $lineAfter = max(0, $lineAfter);

                return 'Rp ' . number_format($lineAfter, 0, ',', '.');
            })
            ->addColumn('qty', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_bungkus ?? 0;
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 1; // Pharmacy fees are counted as single group
                } else if (isset($row->qty)) {
                    return $row->qty;
                } else if ($row->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                    return optional($row->billable)->jumlah ?? 1;
                }
                return optional($row->billable)->qty ?? 1;
            })
            ->addColumn('diskon', function ($row) {
                if (!$row->diskon || $row->diskon == 0) {
                    return '-';
                }

                // If percent promo and a promo_price_base exists which is lower than original amount,
                // show the original fixed discount (original - promo_base) plus the percent.
                if ($row->diskon_type == '%') {
                    $percentText = number_format($row->diskon, 2) . '%';

                    // Determine original unit amount where possible
                    $originalAmount = null;
                    if (isset($row->jumlah) && is_numeric($row->jumlah)) {
                        $originalAmount = $row->jumlah;
                    } elseif (isset($row->racikan_total_price)) {
                        $originalAmount = $row->racikan_total_price;
                    } elseif (isset($row->fee_total_price)) {
                        $originalAmount = $row->fee_total_price;
                    }

                    if ($originalAmount && isset($row->promo_price_base) && is_numeric($row->promo_price_base) && $row->promo_price_base < $originalAmount) {
                        $fixedDiscount = $originalAmount - $row->promo_price_base;
                        if ($fixedDiscount > 0) {
                            return 'Rp ' . number_format($fixedDiscount, 0, ',', '.') . ' + ' . $percentText;
                        }
                    }

                    return $percentText;
                } else {
                    return 'Rp ' . number_format($row->diskon, 0, ',', '.');
                }
            })
            ->addColumn('deskripsi', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    // Format as a list with each item prefixed by a dash
                    $obatList = array_map(function ($item) {
                        return "- " . $item;
                    }, $row->racikan_obat_list ?? []);

                    // Join with <br> for a line break between each item
                    return implode("<br>", $obatList);
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    // Display the list of fee items
                    if (empty($row->fee_descriptions)) {
                        return 'Biaya jasa farmasi (' . ($row->fee_items_count ?? 0) . ' item)';
                    }
                    
                    // Format fee descriptions with dash prefixes and line breaks
                    $formattedFees = array_map(function ($item) {
                        return "- " . $item;
                    }, $row->fee_descriptions ?? []);
                    
                    return implode("<br>", $formattedFees);
                } else if ($row->billable_type == 'App\\Models\\ERM\\PaketTindakan') {
                    // For PaketTindakan, show a list of contained tindakan
                    $tindakanList = optional($row->billable)->tindakan ? optional($row->billable)->tindakan()->pluck('nama')->toArray() : [];

                    if (empty($tindakanList)) {
                        return '-';
                    }

                    // Format tindakan list with dash prefixes and line breaks
                    $formattedList = array_map(function ($item) {
                        return "- " . $item;
                    }, $tindakanList);

                    return implode("<br>", $formattedList);
                } else if ($row->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                    $deskripsi = [];
                    if (optional($row->billable)->keterangan) {
                        $deskripsi[] = $row->billable->keterangan;
                    }
                    return !empty($deskripsi) ? implode(", ", $deskripsi) : '-';
                }
                return '-';
            })
                ->rawColumns(['aksi', 'deskripsi'])
                ->make(true);
        } catch (\Exception $e) {
            // Log and return JSON error so remote clients (browser) can see the message
            Log::error('Error in BillingController::create AJAX DataTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'visitation_id' => $visitation_id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while generating billing data: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    $visitation = Visitation::with(['pasien', 'metodeBayar'])->findOrFail($visitation_id);
    // Fetch latest invoice for this visitation (if exists)
    $invoice = \App\Models\Finance\Invoice::with('piutangs')->where('visitation_id', $visitation_id)->latest()->first();

    // Detect if invoice is out-of-date versus billing (used by UI to prompt "Update Invoice" before payment).
    $invoiceNeedsUpdate = false;
    try {
        if ($invoice) {
            $maxBillingUpdatedAt = Billing::where('visitation_id', $visitation_id)->max('updated_at');
            if ($maxBillingUpdatedAt && $invoice->updated_at) {
                $invoiceNeedsUpdate = \Carbon\Carbon::parse($maxBillingUpdatedAt)->gt($invoice->updated_at);
            }
        }
    } catch (\Exception $e) {
        $invoiceNeedsUpdate = false;
    }
    
    // Get all available gudangs for dropdown
    $gudangs = Gudang::orderBy('nama')->get();
    
    // Get active gudang mappings for auto-selection
    $gudangMappings = [
        'resep' => GudangMapping::getDefaultGudangId('resep'),
        'tindakan' => GudangMapping::getDefaultGudangId('tindakan'),
        'kode_tindakan' => GudangMapping::getDefaultGudangId('kode_tindakan'),
    ];
    
    return view('finance.billing.create', compact('visitation', 'invoice', 'gudangs', 'gudangMappings', 'invoiceNeedsUpdate'));
}

    public function createInvoice(Request $request)
    {
        // Buat Invoice: strictly creates/updates an UNPAID invoice.
        return $this->upsertInvoiceInternal($request, 'unpaid');
    }

    public function receivePayment(Request $request)
    {
        // Terima Pembayaran: updates an existing invoice's payment and triggers stock reduction (when fully paid).
        return $this->upsertInvoiceInternal($request, 'payment');
    }

    private function upsertInvoiceInternal(Request $request, string $mode)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'totals' => 'required',
            'gudang_selections' => 'nullable|array',
            'invoice_id' => 'nullable|integer',
        ]);

        // Accept totals as either an array (normal) or a JSON string (sent from frontend).
        $totalsInput = $request->input('totals');
        if (is_string($totalsInput)) {
            $decoded = json_decode($totalsInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['totals' => $decoded]);
            } else {
                $request->merge(['totals' => []]);
            }
        }

        DB::beginTransaction();

        try {
            $visitation = Visitation::find($request->visitation_id);
            if (!$visitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visitation not found with ID: ' . $request->visitation_id
                ], 404);
            }

            // Find existing invoice (prefer explicit invoice_id, then latest-by-visitation).
            $existingInvoice = null;
            if ($request->filled('invoice_id')) {
                $existingInvoice = Invoice::where('id', $request->input('invoice_id'))
                    ->where('visitation_id', $request->visitation_id)
                    ->first();
            }
            if (!$existingInvoice) {
                $existingInvoice = Invoice::where('visitation_id', $request->visitation_id)->latest()->first();
            }

            // Strict separation:
            // - unpaid mode is allowed when there is no invoice yet OR when updating an existing invoice
            //   that has not been processed for payment (payment_method still null/empty and no stock ledger).
            // - payment mode requires an existing invoice
            if ($mode === 'unpaid' && $existingInvoice) {
                $hasPaymentProcessed = !empty($existingInvoice->payment_method) || floatval($existingInvoice->amount_paid ?? 0) > 0;
                $hasStockLedger = false;
                try {
                    $hasStockLedger = (bool) KartuStok::where('ref_type', 'invoice_penjualan')
                        ->where('ref_id', $existingInvoice->id)
                        ->exists();
                } catch (\Exception $e) {
                    $hasStockLedger = false;
                }

                if ($hasPaymentProcessed || $hasStockLedger) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice sudah diproses pembayaran. Gunakan Terima Pembayaran untuk melanjutkan.'
                    ], 400);
                }
                // else: allow updating the existing unpaid invoice to match current billing
            }

            if ($mode === 'payment' && !$existingInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice belum dibuat. Silakan klik Buat Invoice terlebih dahulu.'
                ], 400);
            }

            $billingItems = Billing::where('visitation_id', $request->visitation_id)->get();

            try {
                Log::info('upsertInvoiceInternal debug payload', [
                    'mode' => $mode,
                    'visitation_id' => $request->visitation_id,
                    'existing_invoice_id' => $existingInvoice ? $existingInvoice->id : null,
                    'existing_invoice_amount_paid' => $existingInvoice ? floatval($existingInvoice->amount_paid ?? 0) : null,
                    'billing_item_count' => $billingItems->count(),
                    'totals_present' => $request->has('totals'),
                    'gudang_selections_present' => $request->has('gudang_selections'),
                    'totals_payload' => $request->input('totals') ?? null,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to write upsertInvoiceInternal debug log: ' . $e->getMessage());
            }

            // --- Stock validation (shared) ---
            $stockErrors = [];
            $kodeTindakanObats = [];
            $labRequiredObats = [];
            $gudangSelections = $request->input('gudang_selections', []);

            foreach ($billingItems as $item) {
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                    if ($resep && $resep->obat) {
                        $qty = floatval($item->qty ?? 1);
                        if ($resep->racikan_ke > 0) {
                            continue;
                        }
                        $billingKey = $item->id;
                        $selectedGudangId = null;
                        if ($billingKey && isset($gudangSelections[$billingKey]) && $gudangSelections[$billingKey]) {
                            $selectedGudangId = $gudangSelections[$billingKey];
                        } elseif (isset($gudangSelections['resep_' . $resep->obat->id]) && $gudangSelections['resep_' . $resep->obat->id]) {
                            $selectedGudangId = $gudangSelections['resep_' . $resep->obat->id];
                        }

                        if ($selectedGudangId) {
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $resep->obat->id)
                                ->where('gudang_id', $selectedGudangId)
                                ->sum('stok');
                        } else {
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $resep->obat->id)
                                ->sum('stok');
                        }

                        if ($qty > $currentStock) {
                            $stockErrors[] = "Stok {$resep->obat->nama} tidak mencukupi. Dibutuhkan: {$qty}, Tersedia: {$currentStock}";
                        }
                    }
                } else if (
                    isset($item->billable_type) &&
                    $item->billable_type === 'App\\Models\\ERM\\Obat' &&
                    isset($item->keterangan) &&
                    str_contains($item->keterangan, 'Obat Bundled:')
                ) {
                    $obat = \App\Models\ERM\Obat::find($item->billable_id);
                    if ($obat) {
                        $qty = floatval($item->qty ?? 1);
                        $billingKey = $item->id;
                        $selectedGudangId = null;
                        if ($billingKey && isset($gudangSelections[$billingKey]) && $gudangSelections[$billingKey]) {
                            $selectedGudangId = $gudangSelections[$billingKey];
                        } elseif (isset($gudangSelections['tindakan_' . $obat->id]) && $gudangSelections['tindakan_' . $obat->id]) {
                            $selectedGudangId = $gudangSelections['tindakan_' . $obat->id];
                        }

                        if ($selectedGudangId) {
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                ->where('gudang_id', $selectedGudangId)
                                ->sum('stok');
                        } else {
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                ->sum('stok');
                        }

                        if ($qty > $currentStock) {
                            $stockErrors[] = "Stok {$obat->nama} (bundled) tidak mencukupi. Dibutuhkan: {$qty}, Tersedia: {$currentStock}";
                        }
                    }
                }
            }

            foreach ($billingItems as $item) {
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                    $riwayatTindakan = \App\Models\ERM\RiwayatTindakan::find($item->billable_id);
                    if ($riwayatTindakan) {
                        $kodeTindakanMeds = DB::table('erm_riwayat_tindakan_obat')
                            ->where('riwayat_tindakan_id', $riwayatTindakan->id)
                            ->get();

                        foreach ($kodeTindakanMeds as $kodeTindakanMed) {
                            $obat = \App\Models\ERM\Obat::find($kodeTindakanMed->obat_id);
                            if ($obat) {
                                $qty = floatval($kodeTindakanMed->qty ?? 1);
                                $resolvedGudangId = $this->getGudangForItem($request, $obat->id, 'kode_tindakan', $item->id);
                                if ($resolvedGudangId) {
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->where('gudang_id', $resolvedGudangId)
                                        ->sum('stok');
                                } else {
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->sum('stok');
                                }

                                if ($qty > $currentStock) {
                                    $gudangName = null;
                                    if (!empty($resolvedGudangId)) {
                                        $gudangName = optional(\App\Models\ERM\Gudang::find($resolvedGudangId))->nama;
                                    }
                                    $suffix = $gudangName ? " di gudang {$gudangName}" : "";
                                    $stockErrors[] = "Stok {$obat->nama} (kode tindakan){$suffix} tidak mencukupi. Dibutuhkan: {$qty}, Tersedia: {$currentStock}";
                                }

                                $kodeTindakanObats[] = [
                                    'obat_id' => $obat->id,
                                    'qty' => $qty,
                                    'riwayat_tindakan_id' => $riwayatTindakan->id,
                                    'kode_tindakan_id' => $kodeTindakanMed->kode_tindakan_id,
                                    'billing_id' => $item->id
                                ];
                            }
                        }
                    }
                }
            }

            foreach ($billingItems as $item) {
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\LabPermintaan') {
                    $labPermintaan = \App\Models\ERM\LabPermintaan::with('labTest.obats')->find($item->billable_id);
                    if ($labPermintaan && $labPermintaan->labTest) {
                        $qtyTestRaw = $item->qty ?? 1;
                        if (is_string($qtyTestRaw)) {
                            $normalized = str_replace('.', '', $qtyTestRaw);
                            $normalized = str_replace(',', '.', $normalized);
                            $qtyTest = floatval($normalized);
                        } else {
                            $qtyTest = floatval($qtyTestRaw);
                        }
                        if ($qtyTest >= 100 && $qtyTest <= 1000) {
                            $qtyTest = $qtyTest / 100.0;
                        }

                        foreach ($labPermintaan->labTest->obats as $obat) {
                            $dosisRaw = $obat->pivot->dosis ?? 0;
                            if (is_string($dosisRaw)) {
                                $dosis = floatval(str_replace(',', '.', $dosisRaw));
                            } else {
                                $dosis = floatval($dosisRaw);
                            }
                            $required = $dosis * $qtyTest;
                            if ($required <= 0) { continue; }

                            $billingKey = $item->id;
                            $selectedGudangId = null;
                            if ($billingKey && isset($gudangSelections[$billingKey]) && $gudangSelections[$billingKey]) {
                                $selectedGudangId = $gudangSelections[$billingKey];
                            } elseif (isset($gudangSelections['lab_' . $obat->id]) && $gudangSelections['lab_' . $obat->id]) {
                                $selectedGudangId = $gudangSelections['lab_' . $obat->id];
                            }

                            if ($selectedGudangId) {
                                $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                    ->where('gudang_id', $selectedGudangId)
                                    ->sum('stok');
                            } else {
                                $mappedGudangId = $this->getGudangForItem($request, $obat->id, 'lab', $item->id);
                                if ($mappedGudangId) {
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->where('gudang_id', $mappedGudangId)
                                        ->sum('stok');
                                } else {
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->sum('stok');
                                }
                            }

                            if ($required > $currentStock) {
                                $testName = $labPermintaan->labTest->nama ?? 'Lab Test';
                                $stockErrors[] = "Stok {$obat->nama} untuk lab ({$testName}) tidak mencukupi. Dibutuhkan: {$required}, Tersedia: {$currentStock}";
                            }

                            $labRequiredObats[] = [
                                'billing_id' => $item->id,
                                'obat_id' => $obat->id,
                                'qty' => $required,
                                'lab_test_id' => $labPermintaan->labTest->id,
                            ];
                        }
                    }
                }
            }

            if (!empty($stockErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi untuk beberapa obat:\n' . implode('\n', $stockErrors)
                ], 400);
            }

            // --- Totals + invoice upsert ---
            $totals = $request->totals ?? [];
            $subtotal = floatval($totals['subtotal'] ?? 0);
            $discountAmount = floatval($totals['discountAmount'] ?? 0);
            $taxAmount = floatval($totals['taxAmount'] ?? 0);
            $grandTotal = isset($totals['grandTotalInt']) ? intval($totals['grandTotalInt']) : floatval($totals['grandTotal'] ?? $subtotal);
            $amountPaid = isset($totals['amountPaidInt']) ? intval($totals['amountPaidInt']) : floatval($totals['amountPaid'] ?? 0);
            $paymentMethod = $totals['paymentMethod'] ?? null;

            $amountPaidNumeric = floatval($amountPaid ?? 0);
            $grandTotalNumeric = floatval($grandTotal ?? 0);

            // Force unpaid for the Buat Invoice endpoint.
            if ($mode === 'unpaid') {
                $amountPaidNumeric = 0.0;
                $amountPaid = 0;
                $paymentMethod = null;
            }

            // Determine invoice status based on integer-ceil comparison (aligns with UI totals)
            // - issued: unpaid (amount_paid == 0)
            // - partial: amount_paid > 0 but not fully paid (shortage -> piutang)
            // - paid: fully covered
            $amountPaidIntForStatus = intval(ceil($amountPaidNumeric));
            $grandTotalIntForStatus = intval(ceil($grandTotalNumeric));
            $invoiceStatus = 'issued';
            if ($mode === 'payment') {
                if ($grandTotalIntForStatus > 0 && $amountPaidIntForStatus >= $grandTotalIntForStatus) {
                    $invoiceStatus = 'paid';
                } elseif ($amountPaidIntForStatus > 0 && $amountPaidIntForStatus < $grandTotalIntForStatus) {
                    $invoiceStatus = 'partial';
                } else {
                    $invoiceStatus = 'issued';
                }
            }

            // Unpaid invoice creation always stays issued.
            if ($mode === 'unpaid') {
                $invoiceStatus = 'issued';
            }

            // Payment rule:
            // - if payment_method is not provided, default based on amount_paid
            //   (paid==0 => piutang, paid>0 => cash)
            // - if payment_method is provided (transfer/debit/qris/asuransi/etc), respect it
            //   and do NOT overwrite to cash just because paid>0
            if ($mode === 'payment') {
                if (is_string($paymentMethod)) {
                    $paymentMethod = trim($paymentMethod);
                }

                if (empty($paymentMethod)) {
                    $paymentMethod = ($amountPaidNumeric > 0) ? 'cash' : 'piutang';
                } else {
                    // If client says cash but paid==0, treat it as piutang.
                    if ($amountPaidNumeric <= 0 && $paymentMethod === 'cash') {
                        $paymentMethod = 'piutang';
                    }
                }
            }

            $changeAmount = 0.0;
            $shortageAmount = 0.0;
            if ($amountPaidNumeric >= $grandTotalNumeric) {
                $changeAmount = $amountPaidNumeric - $grandTotalNumeric;
            } else {
                $shortageAmount = max(0, $grandTotalNumeric - $amountPaidNumeric);
            }

            // Unpaid invoice creation keeps payment_method NULL.
            if ($mode === 'unpaid') {
                $paymentMethod = null;
            }

            // payment_date is only meaningful on payment flow.
            $paymentDate = $existingInvoice ? $existingInvoice->payment_date : null;
            if ($mode === 'payment') {
                $amountPaidIntForDate = intval(ceil($amountPaidNumeric));
                $totalAmountIntForDate = intval(ceil($grandTotalNumeric));
                if ($amountPaidIntForDate > 0 && $amountPaidIntForDate >= $totalAmountIntForDate) {
                    if (!$paymentDate) {
                        $paymentDate = now();
                    }
                }
            } else {
                $paymentDate = null;
            }

            $invoiceData = [
                'invoice_number' => $existingInvoice ? $existingInvoice->invoice_number : Invoice::generateInvoiceNumber(),
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'tax' => $taxAmount,
                'discount_type' => $totals['discountType'] ?? null,
                'discount_value' => $totals['discountValue'] ?? 0,
                'tax_percentage' => $totals['taxPercentage'] ?? 0,
                'total_amount' => $grandTotal,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'shortage_amount' => $shortageAmount,
                'payment_method' => $paymentMethod,
                'payment_date' => $paymentDate,
                'status' => $invoiceStatus,
                'user_id' => Auth::id(),
                'notes' => $request->notes ?? null,
            ];

            if ($existingInvoice) {
                $invoice = $existingInvoice;
                $invoice->fill($invoiceData);
                $invoice->save();
            } else {
                $invoice = new Invoice();
                $invoice->visitation_id = $request->visitation_id;
                $invoice->fill($invoiceData);
                $invoice->save();
            }

            // Get old items for comparison if invoice exists
            $oldInvoiceItems = [];
            $previousAmountPaid = 0;
            if ($existingInvoice) {
                $previousAmountPaid = floatval($existingInvoice->amount_paid ?? 0);
                $oldInvoiceItems = $invoice->items()
                    ->get()
                    ->keyBy(function($item) {
                        return $item->billable_type . '-' . $item->billable_id;
                    });
                $invoice->items()->delete();
            }
            
            // Initialize arrays for grouping items
            $racikanGroups = [];
            $regularItems = [];
            $pharmacyFeeItems = [];
            // Track whether any stock reductions actually happened (useful for response)
            $stockReduced = false;
            // For reliability: track attempted vs successful stock operations.
            $stockOpsAttempted = 0;
            $stockOpsSucceeded = 0;
            $stockOpsErrors = [];

            foreach ($billingItems as $item) {
                // Identify pharmacy fee items
                if (
                    (isset($item->nama_item) && strtolower($item->nama_item) === 'jasa farmasi') ||
                    (isset($item->is_pharmacy_fee) && $item->is_pharmacy_fee)
                ) {
                    $pharmacyFeeItems[] = $item;
                    continue;
                }

                // Identify racikan items
                if (
                    $item->billable_type == 'App\Models\ERM\ResepFarmasi' && 
                    optional($item->billable)->racikan_ke !== null &&
                    optional($item->billable)->racikan_ke > 0
                ) {
                    $racikanKey = optional($item->billable)->racikan_ke;
                    if (!isset($racikanGroups[$racikanKey])) {
                        $racikanGroups[$racikanKey] = [];
                    }
                    
                    // Add to group and mark as processed
                    $racikanGroups[$racikanKey][] = $item;
                } else {
                    $regularItems[] = $item;
                }
            }

            // Stock cutting rule (robust):
            // Reduce stock whenever "Terima Pembayaran" is processed, but ONLY ONCE per invoice.
            // We detect prior reductions via KartuStok entries created with ref_type=invoice_penjualan.
            $amountPaidRaw = floatval($amountPaid ?? 0);
            $alreadyReduced = false;
            try {
                $alreadyReduced = (bool)($existingInvoice && KartuStok::where('ref_type', 'invoice_penjualan')
                    ->where('ref_id', $existingInvoice->id)
                    ->exists());
            } catch (\Exception $e) {
                $alreadyReduced = false;
            }
            $shouldReduceStock = ($mode === 'payment') && !$alreadyReduced;
            // For compatibility with existing qty-diff logic (racikan + oldQty handling)
            $paymentIncreased = ($mode === 'payment') && !$alreadyReduced;

            Log::info('Stock reduction decision', [
                'mode' => $mode,
                'invoice_id' => $invoice->id,
                'already_reduced' => $alreadyReduced,
                'should_reduce_stock' => $shouldReduceStock,
                'amount_paid_raw' => $amountPaidRaw,
                'payment_method' => $paymentMethod,
            ]);
            
            foreach ($billingItems as $item) {
                $itemKey = $item->billable_type . '-' . $item->billable_id;
                $newQty = floatval($item->qty ?? 1);

                if ($existingInvoice) {
                    // For updates, only adjust the difference
                    $oldItem = $oldInvoiceItems[$itemKey] ?? null;
                    $oldQty = $oldItem ? floatval($oldItem->quantity) : 0;

                    // If we are finalizing for the first time, treat old quantity as 0 so we reduce full qty
                    if (!empty($shouldReduceStock)) {
                        $oldQty = 0;
                    }

                    $qtyDiff = $newQty - $oldQty;

                    // Skip if no quantity change (tolerant float comparison)
                    if (abs($qtyDiff) < 0.00001) {
                        continue;
                    }
                } else {
                    // For new invoices, treat the full quantity as the difference
                    $qtyDiff = $newQty;
                }
                
                // Only process stock changes if payment is made
                if ($shouldReduceStock) {
                    // For ResepFarmasi items
                    if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                        $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                        if ($resep && $resep->obat) {
                            // Skip stock reduction for individual racikan items
                            if ($resep->racikan_ke > 0) {
                                continue;
                            }
                            
                            $obat = $resep->obat;
                            
                            if (abs($qtyDiff) > 0.00001) {
                                Log::info('Stock adjustment', [
                                    'invoice_id' => $invoice->id,
                                    'is_update' => (bool)$existingInvoice,
                                    'obat_id' => $obat->id,
                                    'qty_diff' => $qtyDiff,
                                    'obat_nama' => $obat->nama
                                ]);
                                
                                // If stock is being reduced (positive diff), reduce faktur stock
                                if ($qtyDiff > 0) {
                                    // Get gudang selection from frontend or use default mapping
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $item->id);
                                    
                                    // Reduce stock from selected gudang
                                    $stockOpsAttempted++;
                                    $reduced = $this->reduceGudangStock($obat->id, $qtyDiff, $gudangId, $invoice->id, $invoice->invoice_number);
                                    if ($reduced) {
                                        $stockOpsSucceeded++;
                                        $stockReduced = true;
                                    } else {
                                        $stockOpsErrors[] = "Gagal mengurangi stok {$obat->nama} (qty: {$qtyDiff})";
                                    }
                                } else if ($qtyDiff < 0) {
                                    // Get gudang selection for stock return
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $item->id);
                                    
                                    // Return stock to selected gudang
                                    $stockOpsAttempted++;
                                    $returned = $this->returnToGudangStock($obat->id, abs($qtyDiff), $gudangId, $invoice->id, $invoice->invoice_number);
                                    if ($returned) {
                                        $stockOpsSucceeded++;
                                        $stockReduced = true; // treat returned as stock-adjusted
                                    } else {
                                        $stockOpsErrors[] = "Gagal mengembalikan stok {$obat->nama} (qty: " . abs($qtyDiff) . ")";
                                    }
                                }
                            Log::info('Stock processed via invoice (ResepFarmasi)', [
                                'obat_id' => $obat->id,
                                'obat_nama' => $obat->nama,
                                'qty_diff' => $qtyDiff,
                                'invoice_id' => $invoice->id,
                                'visitation_id' => $request->visitation_id,
                                'user_id' => Auth::id()
                            ]);
                        }
                    }
                    // For bundled Obat items (from Tindakan)
                    else if (
                        isset($item->billable_type) && 
                        $item->billable_type === 'App\\Models\\ERM\\Obat' &&
                        isset($item->keterangan) && 
                        str_contains($item->keterangan, 'Obat Bundled:')
                    ) {
                        $obat = \App\Models\ERM\Obat::find($item->billable_id);
                        if ($obat) {
                            $qty = floatval($item->qty ?? 1);
                            
                            // Get gudang selection for bundled obat
                            $gudangId = $this->getGudangForItem($request, $obat->id, 'tindakan', $item->id);

                            $stockOpsAttempted++;
                            $reduced = $this->reduceGudangStock($obat->id, $qty, $gudangId, $invoice->id, $invoice->invoice_number);
                            if ($reduced) {
                                $stockOpsSucceeded++;
                                $stockReduced = true;
                            } else {
                                $stockOpsErrors[] = "Gagal mengurangi stok {$obat->nama} (bundled) (qty: {$qty})";
                            }
                            Log::info('Stock processed via invoice (Bundled Obat)', [
                                'obat_id' => $obat->id,
                                'obat_nama' => $obat->nama,
                                'qty_reduced' => $qty,
                                'invoice_id' => $invoice->id,
                                'visitation_id' => $request->visitation_id,
                                'user_id' => Auth::id(),
                                'keterangan' => $item->keterangan
                            ]);
                        }
                    }
                }
                } // Close the main shouldReduceStock if block
            }
            
            // Process kode tindakan medications stock reduction (only when payment is made)
            if ($shouldReduceStock) {
                foreach ($kodeTindakanObats as $kodeTindakanObat) {
                    $obatId = $kodeTindakanObat['obat_id'];
                    $qty = $kodeTindakanObat['qty'];
                    
                    // Get gudang selection for kode tindakan obat
                    $gudangId = $this->getGudangForItem($request, $obatId, 'kode_tindakan', $kodeTindakanObat['billing_id'] ?? null);
                    
                    $stockOpsAttempted++;
                    $reduced = $this->reduceGudangStock($obatId, $qty, $gudangId, $invoice->id, $invoice->invoice_number);
                    if ($reduced) {
                        $stockOpsSucceeded++;
                        $stockReduced = true;
                    } else {
                        $obatName = optional(\App\Models\ERM\Obat::find($obatId))->nama;
                        $obatName = $obatName ?: 'Unknown';
                        $stockOpsErrors[] = "Gagal mengurangi stok {$obatName} (kode tindakan) (qty: {$qty})";
                    }
                    
                    $obat = \App\Models\ERM\Obat::find($obatId);
                    Log::info('Stock processed via invoice (Kode Tindakan Obat)', [
                        'obat_id' => $obatId,
                        'obat_nama' => $obat ? $obat->nama : 'Unknown',
                        'qty_reduced' => $qty,
                        'invoice_id' => $invoice->id,
                        'visitation_id' => $request->visitation_id,
                        'user_id' => Auth::id(),
                        'riwayat_tindakan_id' => $kodeTindakanObat['riwayat_tindakan_id'],
                        'kode_tindakan_id' => $kodeTindakanObat['kode_tindakan_id'],
                        'payment_triggered' => true
                    ]);
                }
                // Process Lab Test medications stock reduction
                foreach ($labRequiredObats as $labMed) {
                    $obatId = $labMed['obat_id'];
                    $qty = $labMed['qty'];
                    $gudangId = $this->getGudangForItem($request, $obatId, 'lab', $labMed['billing_id'] ?? null);
                    $stockOpsAttempted++;
                    $reduced = $this->reduceGudangStock($obatId, $qty, $gudangId, $invoice->id, $invoice->invoice_number);
                    if ($reduced) {
                        $stockOpsSucceeded++;
                        $stockReduced = true;
                    } else {
                        $obatName = optional(\App\Models\ERM\Obat::find($obatId))->nama;
                        $obatName = $obatName ?: 'Unknown';
                        $stockOpsErrors[] = "Gagal mengurangi stok {$obatName} (lab) (qty: {$qty})";
                    }
                    $obat = \App\Models\ERM\Obat::find($obatId);
                    \Illuminate\Support\Facades\Log::info('Stock processed via invoice (Lab Test Obat)', [
                        'obat_id' => $obatId,
                        'obat_nama' => $obat ? $obat->nama : 'Unknown',
                        'qty_reduced' => $qty,
                        'invoice_id' => $invoice->id,
                        'visitation_id' => $request->visitation_id,
                        'lab_test_id' => $labMed['lab_test_id'] ?? null,
                        'payment_triggered' => true
                    ]);
                }
            } else {
                Log::info('Skipped kode tindakan stock reduction - no payment made', [
                    'invoice_id' => $invoice->id,
                    'visitation_id' => $request->visitation_id,
                    'kode_tindakan_count' => count($kodeTindakanObats),
                    'amount_paid' => $amountPaid
                ]);
                \Illuminate\Support\Facades\Log::info('Skipped lab stock reduction - no payment made', [
                    'invoice_id' => $invoice->id,
                    'visitation_id' => $request->visitation_id,
                    'lab_med_count' => count($labRequiredObats),
                    'amount_paid' => $amountPaid
                ]);
            }

            // If we were supposed to reduce stock, make sure all stock operations succeeded.
            if (!empty($shouldReduceStock) && $stockOpsAttempted > 0 && $stockOpsSucceeded < $stockOpsAttempted) {
                throw new \Exception(
                    "Stok gagal diproses untuk beberapa item:\n" . implode("\n", $stockOpsErrors)
                );
            }

            // Process regular items
            foreach ($regularItems as $item) {
                $name = $item->nama_item;
                // Fix: If LabPermintaan, use LabTest name
                if ($item->billable_type == 'App\\Models\\ERM\\LabPermintaan' && !empty($item->billable_id)) {
                    $labPermintaan = \App\Models\ERM\LabPermintaan::with('labTest')->find($item->billable_id);
                    if ($labPermintaan && $labPermintaan->labTest) {
                        $name = $labPermintaan->labTest->nama ?? 'Lab Test';
                    }
                }
                if (empty($name) || $name === 'Unknown Item') {
                    if (!empty($item->billable_type) && !empty($item->billable_id)) {
                        try {
                            $billableModel = app($item->billable_type)::find($item->billable_id);
                            if ($billableModel) {
                                // Try common name fields
                                if (isset($billableModel->nama)) {
                                    $name = $billableModel->nama;
                                } elseif (isset($billableModel->name)) {
                                    $name = $billableModel->name;
                                // Special-case: if this is a RiwayatTindakan, prefer the tindakan or paket name
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\RiwayatTindakan') {
                                    // Try to load via relation fields if present
                                    try {
                                        $rt = $billableModel; // already loaded
                                        if (isset($rt->tindakan) && isset($rt->tindakan->nama)) {
                                            $name = $rt->tindakan->nama;
                                        } elseif (isset($rt->paketTindakan) && isset($rt->paketTindakan->nama)) {
                                            $name = $rt->paketTindakan->nama;
                                        }
                                    } catch (\Exception $ex) {
                                        // swallow - we'll fallback below
                                    }
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi' && isset($billableModel->obat)) {
                                    $name = $billableModel->obat->nama ?? 'Obat';
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Error getting billable model name', [
                                'error' => $e->getMessage(),
                                'billable_type' => $item->billable_type,
                                'billable_id' => $item->billable_id
                            ]);
                        }
                    }
                    // If name is still empty, use a default
                    if (empty($name)) {
                        if ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                            $name = 'Obat';
                        } else {
                            $name = 'Item ' . substr(md5(rand()), 0, 5);
                        }
                    }
                }
                
                // Fallback for description
                $description = $item->keterangan;
                if (empty($description)) {
                    if (!empty($item->deskripsi)) {
                        $description = $item->deskripsi;
                    } elseif (!empty($item->nama_item)) {
                        $description = $item->nama_item;
                    } else {
                        $description = '';
                    }
                }
                
                // Ensure per-item computed values are reset to avoid leaking from previous loop iterations
                $finalAmountComputed = null;

                // Compute final amount: prefer applying active promo percent on the promo base (prefer harga_diskon)
                $unitPrice = floatval($item->jumlah ?? 0);
                $discountVal = floatval($item->diskon ?? 0);
                $discountType = $item->diskon_type ?? null;
                // initialize promo tracking variables to avoid undefined variable when promo lookup fails
                $appliedPromo = false;
                $promoBase = null;
                $promoPercent = null;

                try {
                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                    $candidates = [];
                    if (isset($item->billable_id)) $candidates[] = $item->billable_id;
                    if (isset($item->billable) && isset($item->billable->obat) && isset($item->billable->obat->id)) $candidates[] = $item->billable->obat->id;
                    if (isset($item->billable) && isset($item->billable->tindakan_id)) $candidates[] = $item->billable->tindakan_id;
                    if (isset($item->billable) && isset($item->billable->id)) $candidates[] = $item->billable->id;
                    $candidates = array_values(array_filter(array_unique($candidates)));

                    if (!empty($candidates)) {
                        $promoItems = \App\Models\Marketing\PromoItem::whereIn('item_id', $candidates)
                            ->whereIn('item_type', ['tindakan','obat'])
                            ->whereHas('promo', function($q) use ($today){
                                $q->where(function($q2) use ($today){
                                    $q2->whereNotNull('start_date')->whereNotNull('end_date')
                                        ->where('start_date','<=',$today)
                                        ->where('end_date','>=',$today);
                                })->orWhere(function($q2) use ($today){
                                    $q2->whereNotNull('start_date')->whereNull('end_date')
                                        ->where('start_date','<=',$today);
                                })->orWhere(function($q2) use ($today){
                                    $q2->whereNull('start_date')->whereNotNull('end_date')
                                        ->where('end_date','>=',$today);
                                });
                            })->get();

                        if (!$promoItems->isEmpty()) {
                            $max = $promoItems->max('discount_percent');
                            if ($max > 0) {
                                // apply percent discount from promo on promo_price_base (prefer harga_diskon)
                                $winning = $promoItems->firstWhere('discount_percent', $max);
                                $basePrice = null;
                                if ($winning) {
                                    if ($winning->item_type === 'tindakan') {
                                        $t = \App\Models\ERM\Tindakan::find($winning->item_id);
                                        $basePrice = $t->harga_diskon ?? $t->harga ?? null;
                                    } elseif ($winning->item_type === 'obat') {
                                        $o = \App\Models\ERM\Obat::withInactive()->find($winning->item_id);
                                        $basePrice = $o->harga_diskon ?? $o->harga_net ?? null;
                                    }
                                }
                                if (!$basePrice && isset($item->billable)) {
                                    $basePrice = $item->billable->harga_diskon ?? $item->billable->unit_price ?? null;
                                }
                                if (!$basePrice) $basePrice = $unitPrice;

                                // Overwrite discount to percent and compute unit price after discount based on promo base
                                $discountVal = $max;
                                $discountType = '%';
                                $unitPriceAfter = max(0, $basePrice - ($basePrice * ($discountVal / 100)));
                                $finalAmountComputed = $unitPriceAfter * floatval($item->qty ?? 1);
                                // mark applied promo details for later nominal storage calculation
                                $appliedPromo = true;
                                $promoBase = $basePrice;
                                $promoPercent = $max;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // fallback to existing billing discount fields if promo detection fails
                    Log::warning('Failed to evaluate promo for invoice item: '.$e->getMessage());
                }

                // If $finalAmountComputed not set by promo flow, compute using existing billing discount fields
                if (!isset($finalAmountComputed)) {
                    $qty = floatval($item->qty ?? 1);
                    $qty = $qty > 0 ? $qty : 1;

                    if ($discountVal > 0) {
                        if ($discountType === '%') {
                            $unitPriceAfter = $unitPrice - ($unitPrice * ($discountVal / 100));
                            $unitPriceAfter = max(0, $unitPriceAfter);
                            $finalAmountComputed = $unitPriceAfter * $qty;
                        } else {
                            // Nominal discount is treated as LINE discount
                            $lineNoDisc = $unitPrice * $qty;
                            $finalAmountComputed = max(0, $lineNoDisc - $discountVal);
                        }
                    } else {
                        $finalAmountComputed = $unitPrice * $qty;
                    }
                }

                    // Compute stored nominal discount value
                    // Special-case: bundled obat items should not carry an additional charged final amount
                    if (isset($item->keterangan) && str_contains($item->keterangan, 'Obat Bundled:')) {
                        $storedDiscount = 0;
                        $storedDiscountType = null;
                        $finalAmountComputed = 0;
                        $unitPrice = 0;
                    } else {
                        $storedDiscount = 0;
                        $storedDiscountType = null;
                    }

                    // Always store invoice-item discount as NOMINAL (line discount), never as '%'.
                    $qtyForDiscount = floatval($item->qty ?? 1);
                    $qtyForDiscount = $qtyForDiscount > 0 ? $qtyForDiscount : 1;

                    if ($appliedPromo && $promoBase !== null && $promoPercent !== null) {
                        // Convert promo % into nominal line discount.
                        // Gap between original unit price and promo base + percent discount on promo base.
                        $gapUnit = max(0, $unitPrice - $promoBase);
                        $percentUnit = ($promoBase * ($promoPercent / 100));
                        $unitDiscount = max(0, $gapUnit + $percentUnit);
                        $storedDiscount = round($unitDiscount * $qtyForDiscount, 2);
                        $storedDiscountType = $storedDiscount > 0 ? 'nominal' : null;
                    } else {
                        if ($discountVal > 0 && $discountType === '%') {
                            // Percent discount: convert to nominal LINE discount (qty-aware).
                            $lineNoDiscTmp = $unitPrice * $qtyForDiscount;
                            $storedDiscount = round($lineNoDiscTmp * ($discountVal / 100), 2);
                            $storedDiscountType = $storedDiscount > 0 ? 'nominal' : null;
                        } else {
                            // Nominal discount from billing is already treated as LINE discount.
                            $storedDiscount = round(floatval($discountVal ?? 0), 2);
                            $storedDiscountType = $storedDiscount > 0 ? 'nominal' : null;
                        }
                    }

                    InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $name,
                    'description' => $description,
                    'quantity' => floatval($item->qty ?? 1),
                    // store original unit price (before discount) where available
                    'unit_price' => $unitPrice,
                    'hpp' => (function() use ($item) {
                        try {
                            if (!empty($item->billable_type) && !empty($item->billable_id)) {
                                if ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                                    if ($resep && isset($resep->obat) && $resep->obat) return $resep->obat->hpp ?? null;
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\Obat') {
                                    $obat = \App\Models\ERM\Obat::withInactive()->find($item->billable_id);
                                    if ($obat) return $obat->hpp ?? null;
                                }
                                // fallback: try to resolve model and common field
                                $model = app($item->billable_type)::find($item->billable_id);
                                if ($model) {
                                    if (isset($model->hpp)) return $model->hpp;
                                    if (isset($model->obat) && isset($model->obat->hpp)) return $model->obat->hpp;
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to get hpp for invoice item: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'hpp_jual' => (function() use ($item) {
                        try {
                            if (!empty($item->billable_type) && !empty($item->billable_id)) {
                                if ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                                    if ($resep && isset($resep->obat) && $resep->obat) return $resep->obat->hpp_jual ?? null;
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\Obat') {
                                    $obat = \App\Models\ERM\Obat::withInactive()->find($item->billable_id);
                                    if ($obat) return $obat->hpp_jual ?? null;
                                }
                                $model = app($item->billable_type)::find($item->billable_id);
                                if ($model) {
                                    if (isset($model->hpp_jual)) return $model->hpp_jual;
                                    if (isset($model->obat) && isset($model->obat->hpp_jual)) return $model->obat->hpp_jual;
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to get hpp_jual for invoice item: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'discount' => $storedDiscount,
                    'discount_type' => $storedDiscountType,
                    // final_amount must reflect discount application
                    'final_amount' => $finalAmountComputed,
                    'billable_type' => $item->billable_type ?? null,
                    'billable_id' => $item->billable_id ?? null,
                ]);
            }
            
            // Preload active PaketRacikan with details (for racikan name matching)
            $activePaketRacikans = PaketRacikan::with(['details' => function($q){ $q->select('id','paket_racikan_id','obat_id','dosis'); }])
                ->where('is_active', true)
                ->get(['id','nama_paket','is_active']);

            // Process racikan groups
            foreach ($racikanGroups as $racikanKey => $racikanItems) {
                // Use the first item as base
                $firstItem = $racikanItems[0];
                
                // Get the total price for this racikan group
                $totalPrice = 0;
                $obatList = [];
                $qty = 0;
                
                foreach ($racikanItems as $item) {
                    $totalPrice += floatval($item->jumlah ?? 0);
                    
                    // Get the obat name for description
                    if (isset($item->billable) && $item->billable->obat) {
                        $obatList[] = $item->billable->obat->nama;
                    }
                    
                    // Use the qty/bungkus from the first item (should be the same for all items in racikan)
                        if ($qty == 0) {
                        $qty = floatval($item->billable->bungkus ?? $item->qty ?? 30); // Default to 30 if not specified
                    }
                }
                
                    // Format the description as a list
                    $formattedObatList = array_map(function($obat) {
                        return "- " . $obat;
                    }, $obatList);
                    
                    $description = implode("\n", $formattedObatList);

                    // Determine racikan display name: match to PaketRacikan (obat_id + dosis order-insensitive)
                    $racikanDisplayName = 'Obat Racikan';
                    try {
                        $normalizeDose = function($val) {
                            if ($val === null) return '';
                            $s = trim(strtolower((string)$val));
                            $s = str_replace([','], ['.'], $s);
                            if (preg_match('/\d+(?:\.\d+)?/', $s, $m)) {
                                return rtrim(rtrim($m[0], '0'), '.') ?: $m[0];
                            }
                            return $s;
                        };
                        $compMap = [];
                        foreach ($racikanItems as $ri) {
                            $billable = $ri->billable ?? null;
                            $ob = ($billable && isset($billable->obat)) ? $billable->obat : null;
                            $dose = $billable ? ($billable->dosis ?? null) : null;
                            if ($ob && isset($ob->id)) {
                                $key = $ob->id . '|' . $normalizeDose($dose);
                                $compMap[$key] = true;
                            }
                        }
                        foreach ($activePaketRacikans as $paket) {
                            $details = $paket->details;
                            if (!$details || $details->count() === 0) continue;
                            if ($details->count() !== count($compMap)) continue;
                            $allMatch = true;
                            foreach ($details as $d) {
                                $dKey = ($d->obat_id ?? '0') . '|' . $normalizeDose($d->dosis ?? '');
                                if (!isset($compMap[$dKey])) { $allMatch = false; break; }
                            }
                            if ($allMatch) { $racikanDisplayName = $paket->nama_paket; break; }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Racikan paket matching (invoice) failed: ' . $e->getMessage());
                    }
                    
                    // Handle stock adjustments for racikan items only when payment triggers stock reduction.
                    // Stock reduction is idempotent per invoice (based on KartuStok existence), so when
                    // $shouldReduceStock is true we reduce the FULL racikan qty; otherwise we do nothing.
                    $newRacikanQty = floatval($qty ?? 0);
                    $racikanQtyDiff = !empty($shouldReduceStock) ? $newRacikanQty : 0;

                    // Only perform stock operations if the invoice reached full payment (shouldReduceStock)
                    // and there is a non-zero qty difference to apply.
                    if (!empty($shouldReduceStock) && $racikanQtyDiff != 0) {
                        foreach ($racikanItems as $racikanItem) {
                            if (isset($racikanItem->billable) && $racikanItem->billable->obat) {
                                $obat = $racikanItem->billable->obat;

                                // For racikan components, prefer using the stored 'jumlah' value
                                // (which we persist as 'stok_dikurangi' during resep submit). Fall
                                // back to the racikan group qty diff if not present.
                                $componentQty = floatval($racikanItem->billable->jumlah ?? 0);
                                if ($componentQty <= 0) {
                                    // fallback to group diff
                                    $componentQty = floatval($racikanQtyDiff);
                                }

                                Log::info('Processing racikan stock adjustment', [
                                    'obat_id' => $obat->id,
                                    'component_qty' => $componentQty,
                                    'group_qty_diff' => $racikanQtyDiff,
                                    'is_update' => (bool)$existingInvoice,
                                    'invoice_id' => $invoice->id
                                ]);

                                if ($componentQty > 0) {
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $racikanItem->id);
                                    $stockOpsAttempted++;
                                    $ok = $this->reduceGudangStock($obat->id, $componentQty, $gudangId, $invoice->id, $invoice->invoice_number);
                                    if ($ok) {
                                        $stockOpsSucceeded++;
                                        $stockReduced = true;
                                    } else {
                                        $stockOpsErrors[] = "Gagal mengurangi stok {$obat->nama} (racikan) (qty: {$componentQty})";
                                    }
                                } else if ($componentQty < 0) {
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $racikanItem->id);
                                    $stockOpsAttempted++;
                                    $ok = $this->returnToGudangStock($obat->id, abs($componentQty), $gudangId, $invoice->id, $invoice->invoice_number);
                                    if ($ok) {
                                        $stockOpsSucceeded++;
                                        $stockReduced = true;
                                    } else {
                                        $stockOpsErrors[] = "Gagal mengembalikan stok {$obat->nama} (racikan) (qty: " . abs($componentQty) . ")";
                                    }
                                }

                                Log::info('Racikan stock processed', [
                                    'invoice_id' => $invoice->id,
                                    'is_update' => (bool)$existingInvoice,
                                    'racikan_ke' => $racikanKey,
                                    'obat_id' => $obat->id,
                                    'component_qty' => $componentQty
                                ]);
                            }
                        }
                    }
                    
                    // For racikan, the unit price is the total price of all components per unit (bungkus)
                    $unitPrice = (float) $totalPrice;  // Total price of all components is treated as unit price
                    // Aggregate discount for racikan group (mirror billing grouped-row behavior)
                    $racikanNominalDiscount = 0.0;
                    $racikanPercentDiscounts = [];
                    try {
                        foreach ($racikanItems as $ri) {
                            $dv = floatval($ri->diskon ?? 0);
                            if ($dv <= 0) continue;
                            $dt = trim((string)($ri->diskon_type ?? ''));
                            if ($dt === '%') {
                                $racikanPercentDiscounts[] = $dv;
                            } else {
                                $racikanNominalDiscount += $dv;
                            }
                        }
                    } catch (\Exception $e) {
                        $racikanNominalDiscount = 0.0;
                        $racikanPercentDiscounts = [];
                    }

                    // Always store invoice-item discount as NOMINAL (line discount).
                    $racikanDiscountValue = 0.0;
                    $racikanDiscountType = null;
                    if ($racikanNominalDiscount > 0) {
                        $racikanDiscountValue = $racikanNominalDiscount;
                        $racikanDiscountType = 'nominal';
                    } elseif (!empty($racikanPercentDiscounts)) {
                        $unique = array_values(array_unique(array_map(function($v){ return (string)$v; }, $racikanPercentDiscounts)));
                        if (count($unique) === 1) {
                            $percent = floatval($racikanPercentDiscounts[0]);
                            $lineNoDiscTmp = $unitPrice * ((int)$qty);
                            $racikanDiscountValue = round($lineNoDiscTmp * ($percent / 100), 2);
                            $racikanDiscountType = $racikanDiscountValue > 0 ? 'nominal' : null;
                        }
                    }

                    // Use the computed $qty (bungkus) for final amount and apply discount.
                    $qtyInt = (int) $qty;
                    $lineNoDisc = $unitPrice * $qtyInt;
                    $finalAmount = $lineNoDisc;
                    if ($racikanDiscountValue > 0) {
                        // nominal discount is treated as LINE discount
                        $finalAmount = $lineNoDisc - $racikanDiscountValue;
                    }
                    $finalAmount = max(0, $finalAmount);

                    // Create single invoice item for the racikan group
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $racikanDisplayName,
                    'description' => $description,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'hpp' => (function() use ($racikanItems) {
                        try {
                            $hppVals = [];
                            foreach ($racikanItems as $ri) {
                                if (isset($ri->billable) && isset($ri->billable->obat) && $ri->billable->obat) {
                                    $hppVals[] = $ri->billable->obat->hpp ?? null;
                                }
                            }
                            $hppVals = array_filter($hppVals, function($v){ return !is_null($v); });
                            if (!empty($hppVals)) return round(array_sum($hppVals)/count($hppVals), 2);
                        } catch (\Exception $e) {
                            Log::warning('Failed to compute racikan hpp: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'hpp_jual' => (function() use ($racikanItems) {
                        try {
                            $vals = [];
                            foreach ($racikanItems as $ri) {
                                if (isset($ri->billable) && isset($ri->billable->obat) && $ri->billable->obat) {
                                    $vals[] = $ri->billable->obat->hpp_jual ?? null;
                                }
                            }
                            $vals = array_filter($vals, function($v){ return !is_null($v); });
                            if (!empty($vals)) return round(array_sum($vals)/count($vals), 2);
                        } catch (\Exception $e) {
                            Log::warning('Failed to compute racikan hpp_jual: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'discount' => $racikanDiscountValue,
                    'discount_type' => $racikanDiscountType,
                    'final_amount' => $finalAmount,
                    'billable_type' => 'App\\Models\\ERM\\ResepFarmasi',
                    'billable_id' => null, // No specific billable_id since it's a group
                ]);
            }
            
            // Add biaya administrasi and biaya ongkir as invoice items if present
            if (!empty($totals['adminFee']) && $totals['adminFee'] > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => 'Biaya Administrasi',
                    'description' => 'Biaya administrasi layanan',
                    'quantity' => 1,
                    'unit_price' => floatval($totals['adminFee']),
                    'hpp' => null,
                    'hpp_jual' => null,
                    'discount' => 0,
                    'discount_type' => null,
                    'final_amount' => floatval($totals['adminFee']),
                    'billable_type' => null,
                    'billable_id' => null,
                ]);
            }
            if (!empty($totals['shippingFee']) && $totals['shippingFee'] > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => 'Biaya Ongkir',
                    'description' => 'Biaya pengiriman',
                    'quantity' => 1,
                    'unit_price' => floatval($totals['shippingFee']),
                    'hpp' => null,
                    'hpp_jual' => null,
                    'discount' => 0,
                    'discount_type' => null,
                    'final_amount' => floatval($totals['shippingFee']),
                    'billable_type' => null,
                    'billable_id' => null,
                ]);
            }

            // Rule guarantee: when there is kekurangan (shortage) after payment,
            // it must be recorded as Piutang for exactly the shortage amount.
            // Also: do NOT wipe existing piutang paid_amount if it was already paid/partial.
            if ($mode === 'payment') {
                $existingPiutang = Piutang::where('invoice_id', $invoice->id)->orderByDesc('id')->first();

                if ($shortageAmount > 0) {
                    $piutang = $existingPiutang ?: new Piutang();
                    $piutang->visitation_id = $request->visitation_id;
                    $piutang->invoice_id = $invoice->id;
                    $piutang->amount = floatval($shortageAmount);

                    if ($piutang->paid_amount === null) {
                        $piutang->paid_amount = 0;
                    }

                    $paidPiutang = floatval($piutang->paid_amount ?? 0);
                    $totalPiutang = floatval($piutang->amount ?? 0);

                    if ($paidPiutang <= 0) {
                        $piutang->payment_status = 'unpaid';
                        $piutang->payment_date = null;
                    } elseif ($paidPiutang < $totalPiutang) {
                        $piutang->payment_status = 'partial';
                        // keep payment_date as-is; will be managed by PiutangController
                    } else {
                        $piutang->payment_status = 'paid';
                        if (empty($piutang->payment_date)) {
                            $piutang->payment_date = now();
                        }
                    }

                    // notes/user_id are optional; keep existing user_id if already set
                    if (!empty($request->notes)) {
                        $piutang->notes = $request->notes;
                    }
                    if (empty($piutang->user_id) && Auth::check()) {
                        $piutang->user_id = Auth::id();
                    }

                    $piutang->save();
                } else {
                    // No shortage: if there was an outstanding piutang linked to this invoice,
                    // settle it to avoid stale outstanding amounts.
                    if ($existingPiutang && floatval($existingPiutang->amount ?? 0) > 0) {
                        $existingPiutang->amount = 0;
                        $existingPiutang->payment_status = 'paid';
                        if (empty($existingPiutang->payment_date)) {
                            $existingPiutang->payment_date = now();
                        }
                        if (empty($existingPiutang->user_id) && Auth::check()) {
                            $existingPiutang->user_id = Auth::id();
                        }
                        $existingPiutang->save();
                    }
                }
            }

            DB::commit();

            // Determine paid status using the same integer-ceil comparison used for stock reduction
            $invoiceAmountPaidRaw = floatval($invoice->amount_paid ?? 0);
            $invoiceTotalAmountRaw = floatval($invoice->total_amount ?? 0);
            $invoiceAmountPaidInt = intval(ceil($invoiceAmountPaidRaw));
            $invoiceTotalAmountInt = intval(ceil($invoiceTotalAmountRaw));
            $isPaid = $invoiceAmountPaidInt >= $invoiceTotalAmountInt;

            $responseMessage = $mode === 'payment' ? 'Pembayaran berhasil diproses' : 'Invoice berhasil dibuat';

            $computedStockMessage = null;
            if (!empty($shouldReduceStock)) {
                if ($stockOpsAttempted <= 0) {
                    $computedStockMessage = 'Stok tidak dikurangi (tidak ada item stok).';
                } else {
                    $computedStockMessage = 'Stok berhasil dikurangi sesuai pembayaran.';
                }
            } else {
                if ($mode === 'payment' && !empty($alreadyReduced)) {
                    $computedStockMessage = 'Stok tidak dikurangi (stok sudah dikurangi sebelumnya untuk invoice ini).';
                } else {
                    $computedStockMessage = 'Stok tidak dikurangi (invoice belum diproses untuk pengurangan stok).';
                }
            }

            $paymentMethod = $invoice->payment_method ?? null;
            $latestPiutang = null;
            $piutangPaymentStatus = null;
            $piutangPayload = null;
            try {
                // Always include piutang info if it exists (even when payment_method is cash),
                // because shortage after payment is recorded as Piutang.
                $latestPiutang = Piutang::where('invoice_id', $invoice->id)
                    ->orderByDesc('id')
                    ->first();
                if ($latestPiutang) {
                    $piutangPaymentStatus = $latestPiutang->payment_status ?? null;
                    $piutangPayload = [
                        'id' => $latestPiutang->id,
                        'amount' => floatval($latestPiutang->amount ?? 0),
                        'paid_amount' => floatval($latestPiutang->paid_amount ?? 0),
                        'payment_status' => $latestPiutang->payment_status ?? null,
                    ];
                }
            } catch (\Exception $e) {
                $latestPiutang = null;
                $piutangPaymentStatus = null;
                $piutangPayload = null;
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoiceTotalAmountRaw,
                'amount_paid' => $invoiceAmountPaidRaw,
                'payment_method' => $paymentMethod,
                'piutang_payment_status' => $piutangPaymentStatus,
                'piutang' => $piutangPayload,
                'is_paid' => $isPaid,
                'stock_reduced' => $stockReduced,
                'stock_message' => $stockReduced ? 'Stok berhasil dikurangi sesuai pembayaran.' : $computedStockMessage,
                'stock_ops_attempted' => $stockOpsAttempted,
                'stock_ops_succeeded' => $stockOpsSucceeded,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $logLabel = $mode === 'payment' ? 'Error processing payment' : 'Error creating invoice';
            Log::error($logLabel, [
                'error' => $e->getMessage(),
                'visitation_id' => $request->visitation_id ?? null
            ]);
            
            $respPrefix = $mode === 'payment' ? 'Failed to process payment: ' : 'Failed to create invoice: ';
            return response()->json([
                'success' => false,
                'message' => $respPrefix . $e->getMessage()
            ], 500);
        }
    }

    public function saveBilling(Request $request)
    {
        // Log::info('=== SAVE BILLING REQUEST START ===');
        // Log::info('Request data: ' . json_encode($request->all()));
        // Log::info('New items: ' . json_encode($request->input('new_items', [])));
        // Log::info('=== SAVE BILLING REQUEST END ===');

        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'edited_items' => 'nullable|array',
            'deleted_items' => 'nullable|array',
            'new_items' => 'nullable|array',
            'totals' => 'nullable',
        ]);

        // Accept totals as array or JSON string from frontend
        $totalsInput = $request->input('totals');
        if (is_string($totalsInput)) {
            $decoded = json_decode($totalsInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['totals' => $decoded]);
            } else {
                $request->merge(['totals' => null]);
            }
        }

        // Lock rule: once payment is processed, stock is reduced and billing becomes non-editable.
        // We detect the lock by checking whether stock ledger entries exist for this visitation's invoice.
        try {
            $invoice = Invoice::where('visitation_id', $request->visitation_id)
                ->orderByDesc('id')
                ->first();

            $hasPaymentProcessed = $invoice && !empty($invoice->payment_method);
            $hasStockLedger = $invoice && KartuStok::where('ref_type', 'invoice_penjualan')
                ->where('ref_id', $invoice->id)
                ->exists();

            if ($hasPaymentProcessed || $hasStockLedger) {
                return response()->json([
                    'success' => false,
                    'message' => 'Billing tidak dapat diedit karena pembayaran sudah diproses (stok sudah dikurangi).'
                ], 423);
            }
        } catch (\Exception $e) {
            // If the lock check fails unexpectedly, do not block normal billing save.
        }

        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');

        // Authorization: only Admin may delete billing items.
        if (!empty($request->deleted_items) && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus item billing.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Process deleted items
            if (!empty($request->deleted_items)) {
                // Log::info('Processing deleted items: ' . json_encode($request->deleted_items));
                Billing::whereIn('id', $request->deleted_items)->delete();
            }

            // Process edited items
            if (!empty($request->edited_items)) {
                // Log::info('Processing edited items: ' . json_encode($request->edited_items));
                foreach ($request->edited_items as $item) {
                    // Skip deleted items that may have been edited before deletion
                    if (in_array($item['id'], $request->deleted_items ?? [])) {
                        continue;
                    }

                    // Racikan group edit: update items within the SAME racikan_ke proportionally.
                    // Never fall back to updating all racikan rows for the visitation (can look like random changes).
                    if (isset($item['is_racikan']) && $item['is_racikan']) {
                        $visitationId = $request->visitation_id;

                        // Determine new group total from request (prefer racikan_total_price, fallback to jumlah_raw).
                        $newTotal = null;
                        if (isset($item['racikan_total_price'])) {
                            $newTotal = (float) $item['racikan_total_price'];
                        } elseif (isset($item['jumlah_raw'])) {
                            $newTotal = (float) $item['jumlah_raw'];
                        }

                        // Resolve racikan_ke: prefer payload, fallback to DB lookup from the edited billing row.
                        $racikanKe = $item['racikan_ke'] ?? null;
                        if ($racikanKe === null && isset($item['id'])) {
                            try {
                                $editedBilling = Billing::with('billable')->find($item['id']);
                                if ($editedBilling && $editedBilling->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                                    $rk = optional($editedBilling->billable)->racikan_ke;
                                    if ($rk !== null) {
                                        $racikanKe = $rk;
                                    }
                                }
                            } catch (\Exception $e) {
                                // ignore
                            }
                        }

                        // If we can't resolve group identity or new total, do not mass-update.
                        // Let it fall through to normal single-row edit below.
                        if ($racikanKe !== null && $newTotal !== null) {
                            $resepfarmasiIds = DB::table('erm_resepfarmasi')
                                ->where('visitation_id', $visitationId)
                                ->where('racikan_ke', $racikanKe)
                                ->pluck('id')
                                ->toArray();

                            if (!empty($resepfarmasiIds)) {
                                $racikanBillings = Billing::where('visitation_id', $visitationId)
                                    ->where('billable_type', 'App\\Models\\ERM\\ResepFarmasi')
                                    ->whereIn('billable_id', $resepfarmasiIds)
                                    ->orderBy('id')
                                    ->get();

                                $count = $racikanBillings->count();
                                if ($count > 0) {
                                    $originalTotal = $racikanBillings->sum(function($b){ return (float)$b->jumlah; });
                                    if ($originalTotal > 0) {
                                        $ratio = $newTotal / $originalTotal;
                                        $sumSoFar = 0;
                                        foreach ($racikanBillings as $i => $racikanBilling) {
                                            $oldHarga = (float)$racikanBilling->jumlah;
                                            if ($i < $count - 1) {
                                                $newHarga = round($oldHarga * $ratio, 2);
                                                $racikanBilling->jumlah = $newHarga > 0 ? $newHarga : 0;
                                                $racikanBilling->save();
                                                $sumSoFar += $racikanBilling->jumlah;
                                            } else {
                                                $lastHarga = round($newTotal - $sumSoFar, 2);
                                                $racikanBilling->jumlah = $lastHarga > 0 ? $lastHarga : 0;
                                                $racikanBilling->save();
                                            }
                                        }
                                    } else {
                                        // If original total is zero, set all to zero except last gets total
                                        foreach ($racikanBillings as $i => $racikanBilling) {
                                            if ($i < $count - 1) {
                                                $racikanBilling->jumlah = 0;
                                                $racikanBilling->save();
                                            } else {
                                                $racikanBilling->jumlah = $newTotal;
                                                $racikanBilling->save();
                                            }
                                        }
                                    }

                                    // Persist discount for grouped racikan edit.
                                    // UI edits diskon on the grouped row (line discount on total racikan line).
                                    $diskonVal = null;
                                    if (isset($item['diskon_raw'])) {
                                        $diskonVal = floatval($item['diskon_raw']);
                                    }
                                    $diskonType = isset($item['diskon_type']) ? trim((string)$item['diskon_type']) : '';
                                    if ($diskonVal !== null && $diskonVal > 0 && $diskonType === '') {
                                        $diskonType = 'nominal';
                                    }

                                    if ($diskonVal !== null) {
                                        if ($diskonVal <= 0) {
                                            foreach ($racikanBillings as $rb) {
                                                $rb->diskon = 0;
                                                $rb->diskon_type = null;
                                                $rb->save();
                                            }
                                        } elseif ($diskonType === '%') {
                                            foreach ($racikanBillings as $rb) {
                                                $rb->diskon = $diskonVal;
                                                $rb->diskon_type = '%';
                                                $rb->save();
                                            }
                                        } else {
                                            // Nominal: store as a group-level line discount on the first row only.
                                            foreach ($racikanBillings as $idx2 => $rb) {
                                                if ($idx2 === 0) {
                                                    $rb->diskon = $diskonVal;
                                                    $rb->diskon_type = 'nominal';
                                                } else {
                                                    $rb->diskon = 0;
                                                    $rb->diskon_type = null;
                                                }
                                                $rb->save();
                                            }
                                        }
                                    }

                                    continue;
                                }
                            }
                        }
                    }

                    // Normal edit for non-racikan items
                    $billing = Billing::find($item['id']);
                    if ($billing) {
                        // Update only specific fields that can be edited
                        $billing->jumlah = $item['jumlah_raw'] ?? $billing->jumlah;
                        $billing->diskon = $item['diskon_raw'] ?? null;
                        $billing->diskon_type = $item['diskon_type'] ?? null;
                        if (isset($item['qty']) && $isAdmin) {
                            $billing->qty = $item['qty'];
                        }
                        $billing->save();
                    }
                }
            }

            // Process new items (added through dropdowns)
            if (!empty($request->new_items)) {
                // Log::info('Processing new items: ' . json_encode($request->new_items));
                foreach ($request->new_items as $item) {
                    // Log::info('Processing new item: ' . json_encode($item));
                    
                    // Skip if this item was marked as deleted (check for both boolean and string)
                    if ((isset($item['deleted']) && ($item['deleted'] === true || $item['deleted'] === 'true'))) {
                        // Log::info('Skipping deleted new item: ' . $item['id']);
                        continue;
                    }

                    $newDiskon = 0;
                    if (isset($item['diskon_raw'])) {
                        $newDiskon = $item['diskon_raw'];
                    } elseif (isset($item['diskon'])) {
                        $newDiskon = $item['diskon'];
                    }

                    $newDiskonType = $item['diskon_type'] ?? 'nominal';
                    if ($newDiskonType === '' || $newDiskonType === null) {
                        $newDiskonType = 'nominal';
                    }

                    // Create new billing record
                    $newBilling = Billing::create([
                        'visitation_id' => $request->visitation_id,
                        'billable_type' => $item['billable_type'],
                        'billable_id' => $item['billable_id'],
                        'nama_item' => $item['nama_item'],
                        'jumlah' => $item['harga_akhir_raw'] ?? 0,
                        'qty' => $item['qty'] ?? 1,
                        'diskon' => $newDiskon ?? 0,
                        'diskon_type' => $newDiskonType,
                        'keterangan' => $item['deskripsi'] ?? null,
                    ]);
                    
                    // Log::info('Created new billing: ' . json_encode($newBilling->toArray()));
                }
            } else {
                // Log::info('No new items to process');
            }

            DB::commit();
            // Log::info('Save billing completed successfully');
            return response()->json(['success' => true, 'message' => 'Data billing berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Save billing failed: ' . $e->getMessage());
            // Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!($user && method_exists($user, 'hasRole') && $user->hasRole('Admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $item = Billing::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item billing dihapus']);
    }

    /**
     * Restore a soft-deleted billing item
     */
    public function restore($id)
    {
        $item = Billing::withTrashed()->findOrFail($id);
        if ($item->trashed()) {
            $item->restore();
            return response()->json(['message' => 'Item billing berhasil dikembalikan']);
        }
        return response()->json(['message' => 'Item tidak berada di trash'], 400);
    }

    /**
     * Permanently delete a billing item
     */
    public function forceDelete($id)
    {
        $user = Auth::user();
        if (!($user && method_exists($user, 'hasRole') && $user->hasRole('Admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $item = Billing::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Item billing dihapus permanen']);
    }

    /**
     * Soft-delete all billing items for a visitation (from index)
     */
    public function trashByVisitation($visitation_id)
    {
        $user = Auth::user();
        if (!($user && method_exists($user, 'hasRole') && $user->hasRole('Admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $visitation = \App\Models\ERM\Visitation::findOrFail($visitation_id);
        $count = Billing::where('visitation_id', $visitation_id)->delete();
        return response()->json(['message' => 'Billing untuk kunjungan dipindahkan ke trash', 'count' => $count]);
    }

    /**
     * Restore all billing items for a visitation
     */
    public function restoreByVisitation($visitation_id)
    {
        $user = Auth::user();
        if (!($user && method_exists($user, 'hasRole') && $user->hasRole('Admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $visitation = \App\Models\ERM\Visitation::findOrFail($visitation_id);
        $count = Billing::withTrashed()->where('visitation_id', $visitation_id)->restore();
        return response()->json(['message' => 'Billing untuk kunjungan dikembalikan', 'count' => $count]);
    }

    /**
     * Force delete all billing items for a visitation
     */
    public function forceDeleteByVisitation($visitation_id)
    {
        $user = Auth::user();
        if (!($user && method_exists($user, 'hasRole') && $user->hasRole('Admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $visitation = \App\Models\ERM\Visitation::findOrFail($visitation_id);
        $count = Billing::withTrashed()->where('visitation_id', $visitation_id)->forceDelete();
        return response()->json(['message' => 'Billing untuk kunjungan dihapus permanen', 'count' => $count]);
    }

    public function getVisitationsData(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');
        $metodeGroup = $request->input('metode_group');

        $user = Auth::user();
        $isAdmin = ($user && method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        
        $visitations = \App\Models\ERM\Visitation::with(['pasien', 'klinik', 'dokter.user', 'dokter.spesialisasi', 'invoice.piutangs', 'metodeBayar'])
            ->whereBetween('tanggal_visitation', [$startDate, $endDate . ' 23:59:59'])
            ->where('status_kunjungan', 2);

        if ($dokterId) {
            $visitations->where('dokter_id', $dokterId);
        }
        if ($klinikId) {
            $visitations->where('klinik_id', $klinikId);
        }

        // Tab filter: Umum vs Asuransi (everything except Umum)
        if ($metodeGroup === 'umum') {
            $visitations->whereHas('metodeBayar', function ($q) {
                $q->whereRaw('LOWER(nama) = ?', ['umum']);
            });
        } elseif ($metodeGroup === 'asuransi') {
            $visitations->where(function ($q) {
                $q->whereDoesntHave('metodeBayar')
                  ->orWhereHas('metodeBayar', function ($mq) {
                      $mq->whereRaw('LOWER(nama) != ?', ['umum']);
                  });
            });
        }

        // Status filter: 'belum' (default), 'sudah', or '' (all)
        $statusFilter = $request->input('status_filter', 'belum');

        // Checkbox removed from UI; include deleted rows only when explicitly filtering Terhapus
        $includeDeleted = ($statusFilter === 'terhapus');

        if ($statusFilter === 'terhapus') {
            // Only visitations where there is at least one billing row AND all billing rows are trashed
            $visitations
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('finance_billing')
                        ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
                })
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('finance_billing')
                        ->whereNull('finance_billing.deleted_at')
                        ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
                });
        } elseif ($statusFilter === 'belum') {
            // "Belum Transaksi": no invoice OR invoice exists but no payment yet (amount_paid == 0)
            // Exclude pure piutang invoices (payment_method == 'piutang') because those belong to Piutang bucket.
            // AND ensure they have at least one billing item (respect includeDeleted)
            $visitations->where(function($query) use ($includeDeleted) {
                $query->where(function($q) {
                          $q->whereDoesntHave('invoice')
                            ->orWhereHas('invoice', function($iq) {
                                  $iq->where('amount_paid', 0)
                                     ->where(function($pm) {
                                         $pm->whereNull('payment_method')
                                            ->orWhere('payment_method', '!=', 'piutang');
                                     });
                            });
                      })
                      ->whereExists(function($sub) use ($includeDeleted) {
                          $sub->select(DB::raw(1))
                              ->from('finance_billing')
                              ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
                          if (!$includeDeleted) {
                              $sub->whereNull('finance_billing.deleted_at');
                          }
                      });
            });
        } elseif ($statusFilter === 'belum_lunas') {
            // "Belum Lunas": partially paid (cash/transfer/etc) OR piutang with partial payment
            $visitations->whereHas('invoice', function($q) {
                $q->where(function($qq) {
                    $qq->where(function($n) {
                        $n->where('amount_paid', '>', 0)
                          ->whereColumn('amount_paid', '<', 'total_amount');
                    })->orWhere(function($p) {
                        $p->where('payment_method', 'piutang')
                          ->whereHas('piutangs', function($pq) {
                              $pq->where('payment_status', 'partial');
                          });
                    });
                });
            });
        } elseif ($statusFilter === 'sudah') {
            // "Lunas": fully paid invoice (including those originally piutang but later settled)
            $visitations->whereHas('invoice', function($q) {
                $q->whereColumn('amount_paid', '>=', 'total_amount');
            });
        } elseif ($statusFilter === 'piutang') {
            // Visitations where invoice.payment_method is piutang
            $visitations->whereHas('invoice', function($q) {
                $q->where('payment_method', 'piutang');
            });
        } else {
            // Semua status: by default exclude visitations that only have trashed billings
            if (!$includeDeleted) {
                $visitations->where(function($q) {
                    $q->whereHas('invoice')
                      ->orWhereExists(function($sub) {
                          $sub->select(DB::raw(1))
                              ->from('finance_billing')
                              ->whereNull('finance_billing.deleted_at')
                              ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
                      });
                });
            }
        }
        
        return DataTables::of($visitations)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value']) {
                    $query->whereHas('pasien', function($q) use ($search) {
                        $q->where('nama', 'like', "%$search%")
                          ->orWhere('id', 'like', "%$search%") ;
                    })
                    ->orWhereHas('dokter.user', function($q) use ($search) {
                        $q->where('name', 'like', "%$search%") ;
                    })
                    ->orWhereHas('dokter.spesialisasi', function($q) use ($search) {
                        $q->where('nama', 'like', "%$search%") ;
                    })
                    ->orWhereHas('klinik', function($q) use ($search) {
                        $q->where('nama', 'like', "%$search%") ;
                    })
                    ->orWhere('tanggal_visitation', 'like', "%$search%") ;
                }
            })
            ->addColumn('no_rm', function ($visitation) {
                return $visitation->pasien ? $visitation->pasien->id : '-';
            })
            ->addColumn('nama_pasien', function ($visitation) {
                return $visitation->pasien ? $visitation->pasien->nama : 'No Patient';
            })
            ->addColumn('dokter', function ($visitation) {
                // Show dokter name combined with specialization if available, e.g. "dr Bambang (Penyakit Dalam)"
                if ($visitation->dokter && $visitation->dokter->user) {
                    $name = $visitation->dokter->user->name;
                    if ($visitation->dokter->spesialisasi && $visitation->dokter->spesialisasi->nama) {
                        return $name . ' (' . $visitation->dokter->spesialisasi->nama . ')';
                    }
                    return $name;
                }
                return '-';
            })
            ->addColumn('jenis_kunjungan', function ($visitation) {
                // Map numeric values to labels
                if (isset($visitation->jenis_kunjungan)) {
                    switch ($visitation->jenis_kunjungan) {
                        case 1:
                        case '1':
                            return 'Konsultasi Dokter';
                        case 2:
                        case '2':
                            return 'Beli Produk';
                        case 3:
                        case '3':
                            return 'Laboratorium';
                        default:
                            return $visitation->jenis_kunjungan;
                    }
                }
                return '-';
            })
            ->addColumn('tanggal_visit', function ($visitation) {
                return \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->format('j F Y');
            })
            ->addColumn('nama_klinik', function ($visitation) {
                return $visitation->klinik ? $visitation->klinik->nama : 'No Clinic';
            })
            ->addColumn('invoice_number', function ($visitation) {
                // Return associated invoice number if exists, otherwise dash
                if ($visitation->invoice && isset($visitation->invoice->invoice_number)) {
                    return $visitation->invoice->invoice_number;
                }
                return '-';
            })
            ->addColumn('payment_method', function ($visitation) {
                if ($visitation->invoice && isset($visitation->invoice->payment_method)) {
                    return $visitation->invoice->payment_method;
                }
                return null;
            })
                ->addColumn('status', function ($visitation) {
                        $invoice = $visitation->invoice;

                        // Check if all billings for this visitation are trashed (if there are any)
                        $totalBillings = \App\Models\Finance\Billing::withTrashed()->where('visitation_id', $visitation->id)->count();
                        $trashedBillings = \App\Models\Finance\Billing::onlyTrashed()->where('visitation_id', $visitation->id)->count();
                        if ($totalBillings > 0 && $trashedBillings === $totalBillings) {
                            return '<span style="color: #fff; background: #6c757d; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Terhapus</span>';
                        }

                        // No invoice created -> Belum Transaksi
                        if (!$invoice) {
                            return '<span style="color: #fff; background: #dc3545; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Transaksi</span>';
                        }

                        $amountPaid = floatval($invoice->amount_paid ?? 0);
                        $totalAmount = floatval($invoice->total_amount ?? 0);

                        // Fully paid (normal or after piutang settlement)
                        if ($totalAmount > 0 && $amountPaid >= $totalAmount) {
                            return '<span style="color: #fff; background: #28a745; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Lunas</span>';
                        }

                        // Piutang flow: show Piutang / Belum Lunas depending on Piutang.payment_status
                        if (isset($invoice->payment_method) && $invoice->payment_method === 'piutang') {
                            $piutang = $invoice->relationLoaded('piutangs') ? $invoice->piutangs->first() : null;
                            $status = $piutang && isset($piutang->payment_status) ? strtolower((string)$piutang->payment_status) : null;

                            if ($status === 'paid') {
                                return '<span style="color: #fff; background: #28a745; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Lunas</span>';
                            }
                            if ($status === 'partial') {
                                return '<span style="color: #fff; background: #ffc107; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Lunas</span>';
                            }

                            // unpaid/unknown piutang -> Piutang
                            return '<span style="color: #fff; background: #17a2b8; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Piutang</span>';
                        }

                        // Partially paid (non-piutang)
                        if ($amountPaid > 0 && $amountPaid < $totalAmount) {
                            return '<span style="color: #fff; background: #ffc107; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Lunas</span>';
                        }

                        // Invoice exists but no payment yet
                        return '<span style="color: #fff; background: #dc3545; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Transaksi</span>';
                })
            ->addColumn('action', function ($visitation) use ($isAdmin) {
                $action = '<a href="'.route('finance.billing.create', $visitation->id).'" class="btn btn-sm btn-primary">Lihat Billing</a>';

                // Add "Cetak Nota" buttons if invoice exists
                if ($visitation->invoice) {
                    $action .= ' <a href="'.route('finance.invoice.print-nota', $visitation->invoice->id).'" class="btn btn-sm btn-success ml-1" target="_blank">Cetak Nota</a>';
                    $action .= ' <a href="'.route('finance.invoice.print-nota-v2', $visitation->invoice->id).'" class="btn btn-sm btn-warning ml-1" target="_blank">Cetak Nota v2</a>';
                }

                // Admin-only visitation actions: trash/restore/force delete
                if ($isAdmin) {
                    $totalBillings = \App\Models\Finance\Billing::withTrashed()->where('visitation_id', $visitation->id)->count();
                    $trashedBillings = \App\Models\Finance\Billing::onlyTrashed()->where('visitation_id', $visitation->id)->count();

                    if ($totalBillings > 0 && $trashedBillings === $totalBillings) {
                        // all billings trashed -> show restore and force-delete (force as text 'Permanent Delete')
                        $action .= ' <button data-id="'.$visitation->id.'" class="btn btn-sm btn-info btn-restore-visitation ml-1">Restore</button>';
                        // add data-no-icon to prevent client-side icon mapping
                        $action .= ' <button data-id="'.$visitation->id.'" data-no-icon="1" class="btn btn-sm btn-danger btn-force-visitation ml-1">Permanent Delete</button>';
                    } elseif ($totalBillings > 0) {
                        // has non-deleted billing -> show trash as icon-only button
                        $action .= ' <button data-id="'.$visitation->id.'" class="btn btn-sm btn-danger btn-trash-visitation ml-1" title="Hapus"><i class="ti-trash" aria-hidden="true"></i></button>';
                    }
                }

                return $action;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    /**
     * Get badge counts for billing index tabs (Umum vs Asuransi).
     * Counts visitations that are NOT "Lunas" (Belum Transaksi + Belum Lunas + Piutang),
     * while respecting date/dokter/klinik filters.
     */
    public function getBillingTabCounts(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');

        $buildQuery = function (string $metodeGroup) use ($startDate, $endDate, $dokterId, $klinikId) {
            $q = \App\Models\ERM\Visitation::query()
                ->whereBetween('tanggal_visitation', [$startDate, $endDate . ' 23:59:59'])
                ->where('status_kunjungan', 2);

            if ($dokterId) {
                $q->where('dokter_id', $dokterId);
            }
            if ($klinikId) {
                $q->where('klinik_id', $klinikId);
            }

            // Exclude fully-trashed visitations from the badge counts
            // by requiring at least one non-deleted billing row.
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('finance_billing')
                    ->whereNull('finance_billing.deleted_at')
                    ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
            });

            // Tab filter: Umum vs Asuransi (everything except Umum)
            if ($metodeGroup === 'umum') {
                $q->whereHas('metodeBayar', function ($mq) {
                    $mq->whereRaw('LOWER(nama) = ?', ['umum']);
                });
            } elseif ($metodeGroup === 'asuransi') {
                $q->where(function ($w) {
                    $w->whereDoesntHave('metodeBayar')
                      ->orWhereHas('metodeBayar', function ($mq) {
                          $mq->whereRaw('LOWER(nama) != ?', ['umum']);
                      });
                });
            }

            // Count items that are NOT "Lunas".
            $q->where(function ($w) {
                // 1) No invoice yet (Belum Transaksi) but has at least one billing row
                $w->where(function ($n) {
                    $n->whereDoesntHave('invoice')
                      ->whereExists(function ($sub) {
                          $sub->select(DB::raw(1))
                              ->from('finance_billing')
                              ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
                          $sub->whereNull('finance_billing.deleted_at');
                      });
                })
                // 2) Has invoice but not fully paid (Belum Transaksi / Belum Lunas / Piutang not paid)
                ->orWhereHas('invoice', function ($inv) {
                    $inv->where(function ($x) {
                        // Non-piutang: not fully paid
                        $x->where(function ($pm) {
                            $pm->whereNull('payment_method')
                               ->orWhere('payment_method', '!=', 'piutang');
                        })->where(function ($paid) {
                            $paid->whereNull('total_amount')
                                 ->orWhere('total_amount', '<=', 0)
                                 ->orWhereColumn('amount_paid', '<', 'total_amount');
                        });
                    })->orWhere(function ($piu) {
                        // Piutang: include unless status is paid
                        $piu->where('payment_method', 'piutang')
                            ->where(function ($ps) {
                                $ps->whereDoesntHave('piutangs')
                                   ->orWhereHas('piutangs', function ($pq) {
                                       $pq->where(function ($s) {
                                           $s->whereNull('payment_status')
                                             ->orWhereRaw('LOWER(payment_status) != ?', ['paid']);
                                       });
                                   });
                            });
                    });
                });
            });

            return $q;
        };

        $umumCount = (clone $buildQuery('umum'))->count();
        $asuransiCount = (clone $buildQuery('asuransi'))->count();

        return response()->json([
            'umum' => $umumCount,
            'asuransi' => $asuransiCount,
        ]);
    }

    /**
     * Return dokter and klinik lists for filter dropdowns
     */
    public function filters() {
        // Get all dokters with their user relation
        $dokters = \App\Models\ERM\Dokter::with('user')->get()->map(function($dokter) {
            return [
                'id' => $dokter->id,
                'name' => $dokter->user ? $dokter->user->name : 'Tanpa Nama',
            ];
        });
        $kliniks = \App\Models\ERM\Klinik::select('id', 'nama')->orderBy('nama')->get();
        return response()->json([
            'dokters' => $dokters,
            'kliniks' => $kliniks,
        ]);
    }

    /**
     * Get gudang mappings and available gudangs for billing
     */
    public function getGudangData()
    {
        $gudangs = Gudang::orderBy('nama')->get();
        $gudangMappings = [
            'resep' => GudangMapping::getDefaultGudangId('resep'),
            'tindakan' => GudangMapping::getDefaultGudangId('tindakan'),
            'kode_tindakan' => GudangMapping::getDefaultGudangId('kode_tindakan'),
            'lab' => GudangMapping::getDefaultGudangId('lab'),
        ];

        return response()->json([
            'gudangs' => $gudangs,
            'mappings' => $gudangMappings,
        ]);
    }

    /**
     * Reduce stock using StokService with FIFO logic
     */
    private function reduceGudangStock($obatId, $qty, $gudangId = null, $invoiceId = null, $invoiceNumber = null)
    {
        // Default gudang ID jika tidak dispesifikasikan
        if (!$gudangId) {
            $defaultGudang = \App\Models\ERM\Gudang::first();
            $gudangId = $defaultGudang ? $defaultGudang->id : null;
        }

        if (!$gudangId) {
            Log::error("Tidak ada gudang yang tersedia untuk pengurangan stok obat ID: " . $obatId);
            return false;
        }

        // Gunakan StokService untuk pengurangan stok dengan FIFO logic
        $stokService = new StokService();

        try {
            // Ambil stok dari gudang berdasarkan FIFO (tanggal expired terlama)
            $stokList = \App\Models\ERM\ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('stok', '>', 0)
                ->orderBy('expiration_date', 'asc')
                ->get();

            $remainingQty = $qty;

            foreach ($stokList as $stok) {
                if ($remainingQty <= 0) break;

                $qtyToReduce = min($remainingQty, $stok->stok);
                
                // Debug: log the computed qty and types just before calling StokService
                try {
                    Log::info('About to call StokService::kurangiStok', [
                        'obat_id' => $obatId,
                        'gudang_id' => $gudangId,
                        'batch' => $stok->batch,
                        'qtyToReduce' => $qtyToReduce,
                        'qtyToReduce_type' => gettype($qtyToReduce),
                        'stok_value' => $stok->stok,
                        'stok_value_type' => gettype($stok->stok),
                    ]);
                } catch (\Exception $e) {
                    // swallow logging errors
                }

                // Kurangi stok menggunakan StokService dengan referensi invoice
                $stokService->kurangiStok(
                    $obatId, 
                    $gudangId, 
                    $qtyToReduce, 
                    $stok->batch,
                    'invoice_penjualan',
                    $invoiceId,
                    $invoiceNumber ? "Penjualan via Invoice: {$invoiceNumber}" : "Penjualan obat"
                );
                
                Log::info("Stok berkurang dari gudang", [
                    'obat_id' => $obatId,
                    'gudang_id' => $gudangId,
                    'batch' => $stok->batch,
                    'qty' => $qtyToReduce,
                    'before_stock' => $stok->stok,
                    'expiration_date' => $stok->expiration_date
                ]);

                $remainingQty -= $qtyToReduce;
            }

            if ($remainingQty > 0) {
                Log::error("Stok tidak cukup untuk obat ID: " . $obatId . " di gudang ID: " . $gudangId . ". Kurang: " . $remainingQty);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Gagal mengurangi stok obat ID: " . $obatId . " dari gudang ID: " . $gudangId . ". Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Legacy method kept for backwards compatibility
     * Now delegates to reduceGudangStock
     */
    private function reduceFakturStock($obatId, $qty)
    {
        return $this->reduceGudangStock($obatId, $qty);
    }

    /**
     * Get gudang ID for specific item based on frontend selection or mapping
     */
    private function getGudangForItem($request, $obatId, $transactionType, $billingId = null)
    {
        // Check if frontend sent specific gudang selection for this item
        $gudangSelections = $request->input('gudang_selections', []);

        // 1) Prefer direct billing-id keyed selection (frontend uses billing row id as key)
        if ($billingId !== null && isset($gudangSelections[$billingId]) && $gudangSelections[$billingId]) {
            return $gudangSelections[$billingId];
        }

        // 2) Then check typed key e.g. resep_{obatId}, tindakan_{obatId}, kode_tindakan_{obatId}
        $itemKey = $transactionType . '_' . $obatId;
        if (isset($gudangSelections[$itemKey]) && $gudangSelections[$itemKey]) {
            return $gudangSelections[$itemKey];
        }

        // 3) For kode_tindakan try to resolve by spesialisasi (if billing row references RiwayatTindakan)
        if ($transactionType === 'kode_tindakan') {
            try {
                if ($billingId) {
                    $billingRow = \App\Models\Finance\Billing::find($billingId);
                    if ($billingRow && isset($billingRow->billable_type) && $billingRow->billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                        $riwayat = \App\Models\ERM\RiwayatTindakan::with('tindakan')->find($billingRow->billable_id);
                        if ($riwayat && $riwayat->tindakan && isset($riwayat->tindakan->spesialis_id)) {
                            $spesialisId = $riwayat->tindakan->spesialis_id;
                            $mapping = GudangMapping::resolveGudangForTransaction($transactionType, 'spesialisasi', $spesialisId);
                            if ($mapping && $mapping->gudang_id) {
                                return $mapping->gudang_id;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore and fallback to default mapping
                \Illuminate\Support\Facades\Log::warning('Failed to resolve gudang by spesialisasi: ' . $e->getMessage());
            }
        }

        // 4) Fallback to mapping default (transaction-only)
        $defaultGudangId = GudangMapping::resolveGudangForTransaction($transactionType);
        if ($defaultGudangId) {
            return $defaultGudangId->gudang_id ?? ($defaultGudangId);
        }

        // 4) Last resort: use first available gudang
        $defaultGudang = \App\Models\ERM\Gudang::first();
        return $defaultGudang ? $defaultGudang->id : null;
    }

    /**
     * Return stock to gudang using StokService
     */
    private function returnToGudangStock($obatId, $qty, $gudangId = null, $invoiceId = null, $invoiceNumber = null)
    {
        if (!$gudangId) {
            $defaultGudang = Gudang::first();
            $gudangId = $defaultGudang ? $defaultGudang->id : null;
        }

        if (!$gudangId) {
            Log::error("Tidak ada gudang yang tersedia untuk pengembalian stok obat ID: " . $obatId);
            return false;
        }

        // Gunakan StokService untuk penambahan stok
        $stokService = new StokService();

        try {
            // Get the most recent batch for return reference
            $lastBatch = \App\Models\ERM\ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->orderBy('created_at', 'desc')
                ->first();

            $batchName = $lastBatch ? $lastBatch->batch : 'RETURN-' . date('YmdHis');
            $expDate = $lastBatch ? $lastBatch->expiration_date : now()->addYears(1);

            // Tambah stok menggunakan StokService dengan referensi invoice
            $keterangan = $invoiceNumber ? "Pengembalian stok dari Invoice: {$invoiceNumber}" : "Pengembalian stok";
            $stokService->tambahStok(
                $obatId, 
                $gudangId, 
                $qty, 
                $batchName, 
                $expDate,
                null, // rak
                null, // lokasi
                null, // hargaBeli
                null, // hargaBeliJual
                'invoice_return', // refType
                $invoiceId, // refId
                $keterangan // keterangan
            );

            Log::info("Stok dikembalikan ke gudang menggunakan StokService", [
                'obat_id' => $obatId,
                'gudang_id' => $gudangId,
                'batch' => $batchName,
                'qty_returned' => $qty
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Gagal mengembalikan stok obat ID: " . $obatId . " ke gudang ID: " . $gudangId . ". Error: " . $e->getMessage());
            return false;
        }
    }
}
