<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Obat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ERM\ResepDokter;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;
use App\Models\ERM\EdukasiObat;
use App\Models\ERM\JasaFarmasi;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\WadahObat;
use App\Models\User;
use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Models\ERM\ResepDetail;
use App\Models\ERM\PaketRacikan;
use App\Models\ERM\PaketRacikanDetail;



class EresepController extends Controller
{
    private function shouldKeepFractionalRacikanStock(?string $wadahNama): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $wadahNama ?? '')));

        return in_array($normalized, ['botol', 'cream / lotion'], true);
    }

    private function calculateRacikanStockReduction(ResepFarmasi $resep): float
    {
        $prescribedDosis = 0.0;
        $baseDosis = 0.0;

        if (preg_match('/(\d+(?:[.,]\d+)?)/', $resep->dosis ?? '', $m)) {
            $prescribedDosis = (float) str_replace(',', '.', $m[1]);
        }
        if ($resep->obat && !empty($resep->obat->dosis) && preg_match('/(\d+(?:[.,]\d+)?)/', $resep->obat->dosis, $m2)) {
            $baseDosis = (float) str_replace(',', '.', $m2[1]);
        }

        if ($baseDosis <= 0 || $prescribedDosis <= 0) {
            return 0.0;
        }

        $rawReduction = ($prescribedDosis * (float) ($resep->bungkus ?? 1)) / $baseDosis;
        if ($this->shouldKeepFractionalRacikanStock(optional($resep->wadah)->nama)) {
            return round($rawReduction, 3);
        }

        return (float) ceil($rawReduction);
    }

    private function getInvoicePaymentMethodForVisitation(string $visitationId): ?string
    {
        $visitation = Visitation::with(['invoice:id,visitation_id,payment_method'])
            ->select('id')
            ->find($visitationId);

        $paymentMethod = optional($visitation?->invoice)->payment_method;
        $paymentMethod = is_null($paymentMethod) ? null : trim((string) $paymentMethod);
        if ($paymentMethod === '') $paymentMethod = null;

        return $paymentMethod;
    }

    private function isInvoiceLockedForVisitation(string $visitationId): bool
    {
        $paymentMethod = $this->getInvoicePaymentMethodForVisitation($visitationId);
        return !is_null($paymentMethod);
    }

    private function guardInvoiceNotLocked(?string $visitationId)
    {
        if (!$visitationId) return null;

        if (!$this->isInvoiceLockedForVisitation($visitationId)) return null;

        $message = 'Resep dikunci karena Invoice sudah ditransaksikan.';

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 423);
        }

        abort(423, $message);
    }

    public function invoiceLockStatus(string $visitationId)
    {
        $isLocked = $this->isInvoiceLockedForVisitation($visitationId);
        $paymentMethod = $this->getInvoicePaymentMethodForVisitation($visitationId);

        return response()->json([
            'success' => true,
            'visitation_id' => $visitationId,
            'is_locked' => $isLocked,
            'payment_method' => $paymentMethod,
        ]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Lean page query: only filters, joins and sorting, so MySQL can cut to the current page fast.
            // Heavier per-row data (resep, alergi, invoice, asesmen, first visit) is loaded afterwards
            // for the page's rows only, see enrichFarmasiRows().
            $visitations = Visitation::query()
                ->select([
                    'erm_visitations.id',
                    'erm_visitations.pasien_id',
                    'erm_visitations.klinik_id',
                    'erm_visitations.status_kunjungan',
                    'erm_visitations.jenis_kunjungan',
                    'erm_visitations.tanggal_visitation',
                    'erm_visitations.waktu_kunjungan',

                    'erm_pasiens.nama as nama_pasien',
                    'erm_pasiens.id as no_rm',
                    'erm_pasiens.identity_number',
                    'erm_pasiens.no_hp as telepon_pasien',
                    'erm_pasiens.gender',
                    'erm_pasiens.tanggal_lahir',
                    'erm_pasiens.alamat',
                    'erm_pasiens.notes as catatan_pasien',
                    'erm_pasiens.status_pasien',
                    'erm_pasiens.status_akses',
                    'erm_pasiens.status_review',
                    'erm_pasiens.employee_id',

                    'mb.nama as metode_bayar',
                    'u.name as dokter_nama',
                    's.nama as spesialisasi',
                    'k.nama as nama_klinik',
                    'k.logo as klinik_logo',

                    'av.name as village_name',
                    'ad.name as district_name',
                    'ar.name as regency_name',
                    'ap2.name as province_name',
                ])
                ->leftJoin('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
                ->leftJoin('erm_metode_bayar as mb', 'erm_visitations.metode_bayar_id', '=', 'mb.id')
                ->leftJoin('erm_dokters as d', 'erm_visitations.dokter_id', '=', 'd.id')
                ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
                ->leftJoin('erm_spesialisasis as s', 'd.spesialisasi_id', '=', 's.id')
                ->leftJoin('erm_klinik as k', 'erm_visitations.klinik_id', '=', 'k.id')
                ->leftJoin('area_villages as av', 'erm_pasiens.village_id', '=', 'av.id')
                ->leftJoin('area_districts as ad', 'av.district_id', '=', 'ad.id')
                ->leftJoin('area_regencies as ar', 'ad.regency_id', '=', 'ar.id')
                ->leftJoin('area_provinces as ap2', 'ar.province_id', '=', 'ap2.id')
                ->whereIn('erm_visitations.jenis_kunjungan', [1, 2, 5])
                ->where('erm_visitations.status_kunjungan', '!=', 7);

            if ($request->tanggal_mulai && $request->tanggal_selesai) {
                // tanggal_visitation is a DATE column: compare directly so the (status, tanggal) index is used
                $visitations->whereBetween('erm_visitations.tanggal_visitation', [$request->tanggal_mulai, $request->tanggal_selesai]);
            }
            if ($request->dokter_id) {
                $visitations->where('erm_visitations.dokter_id', $request->dokter_id);
            }
            if ($request->klinik_id) {
                $visitations->where('erm_visitations.klinik_id', $request->klinik_id);
            }

            $user = Auth::user();
            if ($user->hasRole('Farmasi')) {
                $visitations->where('erm_visitations.status_kunjungan', 2);
            }

            if ($request->status_resep !== null && $request->status_resep !== '') {
                // Filter visitations that have a resepdetail with the selected status
                $visitations->whereExists(function($query) use ($request) {
                    $query->select(DB::raw(1))
                        ->from('erm_resepdetail')
                        ->whereColumn('erm_resepdetail.visitation_id', 'erm_visitations.id')
                        ->where('erm_resepdetail.status', $request->status_resep);
                });
            }

            $payload = datatables()->of($visitations)
                ->filterColumn('nama_pasien', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('erm_pasiens.nama', 'like', "%{$keyword}%")
                          ->orWhere('erm_pasiens.id', 'like', "%{$keyword}%")
                          ->orWhere('erm_pasiens.notes', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('no_resep', function($query, $keyword) {
                    $query->whereExists(function($q) use ($keyword) {
                        $q->select(DB::raw(1))
                          ->from('erm_resepdetail')
                          ->whereColumn('erm_resepdetail.visitation_id', 'erm_visitations.id')
                          ->where('erm_resepdetail.no_resep', 'like', "%$keyword%");
                    });
                })
                ->make(true)
                ->getData(true);

            $payload['data'] = $this->enrichFarmasiRows($payload['data'] ?? []);

            return response()->json($payload);
        }

        $kliniks = \App\Models\ERM\Klinik::all();
        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        return view('erm.eresep.index', compact('dokters', 'metodeBayar', 'kliniks'));
    }

    /**
     * Add the per-row data of the E-Resep Farmasi table for the current page only
     * (a handful of WHERE IN queries instead of correlated subqueries over every matching visit).
     */
    private function enrichFarmasiRows(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $visitationIds = array_values(array_unique(array_column($rows, 'id')));
        $pasienIds = array_values(array_unique(array_filter(array_column($rows, 'pasien_id'))));

        $resepDetails = DB::table('erm_resepdetail')
            ->whereIn('visitation_id', $visitationIds)
            ->orderBy('id')
            ->get(['visitation_id', 'no_resep', 'status'])
            ->unique('visitation_id')
            ->keyBy('visitation_id');

        $alergiByPasien = DB::table('erm_alergi')
            ->join('erm_zataktif', 'erm_alergi.zataktif_id', '=', 'erm_zataktif.id')
            ->whereIn('erm_alergi.pasien_id', $pasienIds)
            ->orderBy('erm_zataktif.nama')
            ->get(['erm_alergi.pasien_id', 'erm_zataktif.nama'])
            ->groupBy('pasien_id')
            ->map(fn ($items) => $items->pluck('nama')->unique()->values()->all());

        // Latest invoice per visit (a visit can have more than one) + its first piutang
        $invoices = DB::table('finance_invoices')
            ->whereIn('visitation_id', $visitationIds)
            ->orderByDesc('id')
            ->get(['id', 'visitation_id', 'status', 'payment_method', 'amount_paid', 'total_amount'])
            ->unique('visitation_id')
            ->keyBy('visitation_id');
        $piutangStatusByInvoice = DB::table('finance_piutangs')
            ->whereIn('invoice_id', $invoices->pluck('id')->all())
            ->orderBy('id')
            ->get(['invoice_id', 'payment_status'])
            ->unique('invoice_id')
            ->pluck('payment_status', 'invoice_id');

        $billingCounts = DB::table('finance_billing')
            ->whereIn('visitation_id', $visitationIds)
            ->groupBy('visitation_id')
            ->selectRaw('visitation_id, COUNT(*) as total, SUM(deleted_at IS NOT NULL) as trashed')
            ->get()
            ->keyBy('visitation_id');

        $asesmenPenunjangAt = DB::table('erm_asesmen_penunjang')
            ->whereIn('visitation_id', $visitationIds)
            ->groupBy('visitation_id')
            ->selectRaw('visitation_id, MIN(created_at) as at')
            ->pluck('at', 'visitation_id');
        $cpptAt = DB::table('erm_cppt')
            ->whereIn('visitation_id', $visitationIds)
            ->groupBy('visitation_id')
            ->selectRaw('visitation_id, MIN(created_at) as at')
            ->pluck('at', 'visitation_id');

        // First visit of the patient at that klinik (same rule as the Rawat Jalan "NEW" badge)
        $firstVisitByPasienKlinik = DB::table('erm_visitations')
            ->whereIn('pasien_id', $pasienIds)
            ->where('status_kunjungan', '!=', 7)
            ->orderBy('tanggal_visitation')
            ->orderByRaw("COALESCE(waktu_kunjungan, '23:59:59')")
            ->orderBy('id')
            ->get(['id', 'pasien_id', 'klinik_id'])
            ->unique(fn ($v) => $v->pasien_id . '|' . $v->klinik_id)
            ->mapWithKeys(fn ($v) => [$v->pasien_id . '|' . $v->klinik_id => $v->id]);

        foreach ($rows as &$row) {
            $id = $row['id'];
            $resep = $resepDetails->get($id);
            $invoice = $invoices->get($id);
            $billing = $billingCounts->get($id);

            $row['no_resep'] = $resep->no_resep ?? null;
            $row['alergi'] = $alergiByPasien->get($row['pasien_id'], []);
            $row['is_first_visit'] = (string) ($firstVisitByPasienKlinik->get($row['pasien_id'] . '|' . $row['klinik_id']) ?? '') === (string) $id ? 1 : 0;
            $row['klinik_logo_url'] = !empty($row['klinik_logo']) ? asset('storage/' . $row['klinik_logo']) : null;
            unset($row['klinik_logo']);

            $asesmenAt = $asesmenPenunjangAt->get($id) ?? $cpptAt->get($id);
            $row['asesmen_selesai'] = $asesmenAt ? Carbon::parse($asesmenAt)->format('H:i') : '-';
            $row['waktu_kunjungan'] = $row['waktu_kunjungan'] ? substr($row['waktu_kunjungan'], 0, 5) : '-';

            // Invoice status: same labels/rules as the Billing page
            $invoiceModel = null;
            if ($invoice) {
                $invoiceModel = (new \App\Models\Finance\Invoice())->forceFill((array) $invoice);
                $piutangStatus = $piutangStatusByInvoice->get($invoice->id);
                $invoiceModel->setRelation('piutangs', collect($piutangStatus ? [(object) ['payment_status' => $piutangStatus]] : []));
            }
            $row['invoice_status'] = \App\Services\Finance\BillingRowFormatter::statusLabel(
                $invoiceModel,
                (int) ($billing->total ?? 0),
                (int) ($billing->trashed ?? 0)
            );

            // Action buttons: Selesai only when the invoice was transacted but the resep is not yet served
            $buttons = '<a href="' . route('erm.eresepfarmasi.create', $id) . '" class="btn btn-sm btn-primary" style="font-weight:bold;" target="_blank" title="Buka resep">'
                . '<i class="fas fa-prescription-bottle-alt mr-1"></i>Resep</a>';
            if (trim((string) ($invoice->payment_method ?? '')) !== '' && (int) ($resep->status ?? 0) === 0) {
                $buttons .= '<button type="button" class="btn btn-sm btn-success btn-selesai-resep" style="font-weight:bold;" data-url="' . route('erm.eresepfarmasi.selesai', ['visitation_id' => $id]) . '" title="Tandai resep selesai">'
                    . '<i class="fas fa-check mr-1"></i>Selesai</button>';
            }
            $row['dokumen'] = '<div class="btn-group btn-group-sm" role="group">' . $buttons . '</div>';
        }
        unset($row);

        return $rows;
    }

    public function markResepFarmasiSelesai(Request $request, string $visitationId)
    {
        $visitation = Visitation::with(['invoice:id,visitation_id,payment_method'])->findOrFail($visitationId);

        $paymentMethod = optional($visitation->invoice)->payment_method;
        $paymentMethod = is_null($paymentMethod) ? null : trim((string) $paymentMethod);
        if ($paymentMethod === '') $paymentMethod = null;

        if (is_null($paymentMethod)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa diselesaikan: Invoice belum ditransaksikan.',
            ], 422);
        }

        ResepDetail::updateOrCreate(
            ['visitation_id' => $visitationId],
            ['status' => 1, 'submitted_at' => now()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Resep ditandai selesai.',
            'visitation_id' => $visitationId,
        ]);
    }

    // ERESEP DOKTER

    public function create($visitationId)
    {
        $visitation = Visitation::with('invoice')->findOrFail($visitationId);
        $invoicePaymentMethod = is_null(optional($visitation->invoice)->payment_method)
            ? null
            : trim((string) $visitation->invoice->payment_method);
        if ($invoicePaymentMethod === '') $invoicePaymentMethod = null;

        $isInvoiceLocked = !is_null($invoicePaymentMethod);
        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        // Ambil pasien id
        $pasienId = $visitation->pasien_id;

        // Ambil zat aktif yang pasien alergi
        $zatAlergi = DB::table('erm_alergi')
            ->where('pasien_id', $pasienId)
            ->pluck('zataktif_id')
            ->toArray();

        // Ambil obat yang tidak punya zat aktif dari alergi
        $obats = Obat::whereDoesntHave('zatAktifs', function ($query) use ($zatAlergi) {
            $query->whereIn('erm_zataktif.id', $zatAlergi);
        })->get();

        // Map obat collection to include per-gudang stock used by farmasi operations
        // Optimize: avoid N+1 by fetching stok sums for all obat in one query
        $gudangIdForResep = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
        if ($gudangIdForResep) {
            // Get obat IDs list
            $obatIds = $obats->pluck('id')->toArray();
            // Aggregate stok per obat for the target gudang in one query
            $stokRows = \App\Models\ERM\ObatStokGudang::selectRaw('obat_id, SUM(stok) as stok_sum')
                ->whereIn('obat_id', $obatIds)
                ->where('gudang_id', $gudangIdForResep)
                ->groupBy('obat_id')
                ->get()
                ->keyBy('obat_id');

            $obats = $obats->map(function ($obat) use ($stokRows) {
                $stokGudang = 0;
                if ($obat && isset($stokRows[$obat->id])) {
                    $stokGudang = (int) $stokRows[$obat->id]->stok_sum;
                }
                $obat->setAttribute('stok', $stokGudang);
                $obat->setAttribute('stok_gudang', $stokGudang);
                return $obat;
            });
        } else {
            // If no mapped gudang, still expose total_stok as stok_gudang
            $obats = $obats->map(function ($obat) {
                $stokGudang = (int) $obat->getTotalStokAttribute();
                $obat->setAttribute('stok_gudang', $stokGudang);
                return $obat;
            });
        }

        // $obats = Obat::all();

        // Ambil semua resep berdasarkan visitation_id
        $reseps = ResepDokter::where('visitation_id', $visitationId)->with('obat', 'wadah')->get();

        // Kelompokkan racikan berdasarkan racikan_ke
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Ambil non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');

        // Get medications that exist in both doctor and pharmacy prescriptions
        $farmasiObatIds = ResepFarmasi::where('visitation_id', $visitationId)
            ->whereNull('racikan_ke')
            ->pluck('obat_id')
            ->toArray();

        // Get racikan medications that exist in both doctor and pharmacy prescriptions
        $farmasiRacikanObatIds = ResepFarmasi::where('visitation_id', $visitationId)
            ->whereNotNull('racikan_ke')
            ->pluck('obat_id')
            ->toArray();

        // Hitung nilai racikan_ke terakhir dari database
        $lastRacikanKe = $reseps->whereNotNull('racikan_ke')->max('racikan_ke') ?? 0;

        $wadah = WadahObat::all();

        // dd($racikans);
 $catatan_resep = ResepDetail::where('visitation_id', $visitationId)->value('catatan_dokter');

        return view('erm.eresep.create', array_merge([
            'visitation' => $visitation,
            'invoicePaymentMethod' => $invoicePaymentMethod,
            'isInvoiceLocked' => $isInvoiceLocked,
            'obats' => $obats,
            'wadah' => $wadah,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'lastRacikanKe' => $lastRacikanKe,
            'catatan_resep' => $catatan_resep,
            'farmasiObatIds' => $farmasiObatIds,
            'farmasiRacikanObatIds' => $farmasiRacikanObatIds,
        ], $pasienData, $createKunjunganData));
    }
    public function storeNonRacikan(Request $request)
    {
        if ($resp = $this->guardInvoiceNotLocked($request->input('visitation_id'))) return $resp;

        $validated = $request->validate([
            'visitation_id' => 'required',
            'obat_id' => 'required',
            'jumlah' => 'required',
            'aturan_pakai' => 'required',
        ]);
        $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

        $resep = ResepDokter::create([
            'id' => $customId,
            'created_at' => Carbon::now(),
            'visitation_id' => $validated['visitation_id'],
            'obat_id' => $validated['obat_id'],
            'jumlah' => $validated['jumlah'],
            'aturan_pakai' => $validated['aturan_pakai'],
            'user_id' => Auth::id(),
        ]);

    $resep->load('obat'); // ✅ load the obat relation here
    // Get mapped gudang for resep
    $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
    // Guard: $resep->obat may be null (relation missing or Obat inactive). Fall back to 0.
    $stokGudang = ($gudangId && $resep->obat) ? $resep->obat->getStokByGudang($gudangId) : 0;
        // Add stok_gudang to obat data
        $obatData = $resep->obat->toArray();
        $obatData['stok_gudang'] = (int) $stokGudang;

        $responseData = $resep->toArray();
        $responseData['obat'] = $obatData;

        return response()->json([
            'success' => true,
            'message' => 'Obat non-racikan berhasil disimpan.',
            'data' => $responseData
        ]);
    }

    public function storeRacikan(Request $request)
    {
        if ($resp = $this->guardInvoiceNotLocked($request->input('visitation_id'))) return $resp;
        $validated = $request->validate([
            'visitation_id' => 'required',
            'racikan_ke' => 'required|integer',
            'wadah' => 'required',
            'bungkus' => 'required|integer',
            'aturan_pakai' => 'required|string',
            'obats' => 'required|array|min:1',
            'obats.*.obat_id' => 'required',
            'obats.*.dosis' => 'required|string',
        ]);

        $racikanKe = DB::transaction(function () use ($validated) {
            // The client numbers its cards; take the next free number if this one is already used (other tab, paket applied meanwhile)
            $racikanKe = PaketRacikan::freeRacikanKe(ResepDokter::class, $validated['visitation_id'], $validated['racikan_ke']);

            foreach ($validated['obats'] as $obat) {
                Obat::findOrFail($obat['obat_id']);

                do {
                    $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
                } while (ResepDokter::where('id', $customId)->exists());

                ResepDokter::create([
                    'id' => $customId,
                    'visitation_id' => $validated['visitation_id'],
                    'obat_id' => $obat['obat_id'],
                    'aturan_pakai' => $validated['aturan_pakai'],
                    'racikan_ke' => $racikanKe,
                    'wadah_id' => $validated['wadah'],
                    'bungkus' => $validated['bungkus'],
                    'dosis' => $obat['dosis'],
                    'created_at' => now(),
                    'user_id' => Auth::id(),
                ]);
            }

            return $racikanKe;
        });

        return response()->json(['success' => true, 'message' => 'Racikan berhasil disimpan.', 'racikan_ke' => $racikanKe]);
    }

    public function destroyNonRacikan($id)
    {
        $resep = ResepDokter::findOrFail($id);
        if ($resp = $this->guardInvoiceNotLocked($resep->visitation_id)) return $resp;
        $resep->delete();
        PaketRacikan::syncRacikanLink(ResepDokter::class, $resep->visitation_id, $resep->racikan_ke);

        return response()->json(['message' => 'Resep berhasil dihapus']);
    }

    public function destroyRacikan($racikanKe, Request $request)
    {
        $visitationId = $request->visitation_id;
        if ($resp = $this->guardInvoiceNotLocked($visitationId)) return $resp;

        // Delete ALL records with matching racikan_ke and visitation_id
        $deleted = ResepDokter::where('racikan_ke', $racikanKe)
            ->where('visitation_id', $visitationId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Racikan berhasil dihapus']);
        } else {
            return response()->json(['message' => 'Racikan tidak ditemukan'], 404);
        }
    }

    public function updateNonRacikan(Request $request, $id)
    {
        $resep = ResepDokter::findOrFail($id);
        if ($resp = $this->guardInvoiceNotLocked($resep->visitation_id)) return $resp;

        $data = $request->validate([
            'jumlah'       => 'required|integer|min:1',
            'aturan_pakai' => 'required|string|max:255',
        ]);
        $resep->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil diubah',
            'data'    => $resep,
        ]);
    }

    public function updateRacikan(Request $request, $racikanKe)
    {
        try {
            if ($resp = $this->guardInvoiceNotLocked($request->input('visitation_id'))) return $resp;
            $validated = $request->validate([
                'visitation_id' => 'required',
                'wadah' => 'required',
                'bungkus' => 'required|integer|min:1',
                'aturan_pakai' => 'required|string',
                'obats' => 'required|array',
            ]);

            $visitationId = $validated['visitation_id'];
            $wadahId = $validated['wadah'];
            $bungkus = $validated['bungkus'];
            $aturanPakai = $validated['aturan_pakai'];
            $obats = $validated['obats'];

            DB::beginTransaction();
            $linkBefore = PaketRacikan::racikanLink(ResepDokter::class, $visitationId, $racikanKe);

            // Get all resep rows for this racikan_ke and visitation
            $existingReseps = \App\Models\ERM\ResepDokter::where('visitation_id', $visitationId)
                ->where('racikan_ke', $racikanKe)
                ->get();
            $existingById = $existingReseps->keyBy('id');

            // Collect incoming IDs if present
            $incomingIds = collect($obats)->pluck('id')->filter()->toArray();

            // Delete resep rows that are not present in the incoming obats
            foreach ($existingReseps as $resep) {
                if (!in_array($resep->id, $incomingIds)) {
                    $resep->delete();
                }
            }

            // Update or create resep rows for each obat
            foreach ($obats as $obatData) {
                if (!empty($obatData['id'])) {
                    // Update existing (only rows of this racikan)
                    $resep = $existingById->get($obatData['id']);
                    if ($resep) {
                        $resep->update([
                            'obat_id' => $obatData['obat_id'],
                            'dosis' => $obatData['dosis'] ?? '',
                            'jumlah' => $obatData['jumlah'] ?? 1,
                            'wadah_id' => $wadahId,
                            'bungkus' => $bungkus,
                            'aturan_pakai' => $aturanPakai,
                        ]);
                    }
                } else if (!empty($obatData['obat_id'])) {
                    // Create new
                    do {
                        $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
                    } while (\App\Models\ERM\ResepDokter::where('id', $customId)->exists());
                    \App\Models\ERM\ResepDokter::create([
                        'id' => $customId,
                        'visitation_id' => $visitationId,
                        'obat_id' => $obatData['obat_id'],
                        'dosis' => $obatData['dosis'] ?? '',
                        'jumlah' => $obatData['jumlah'] ?? 1,
                        'racikan_ke' => $racikanKe,
                        'wadah_id' => $wadahId,
                        'bungkus' => $bungkus,
                        'aturan_pakai' => $aturanPakai,
                        'user_id' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }

            PaketRacikan::syncRacikanLink(ResepDokter::class, $visitationId, $racikanKe, $linkBefore);
            DB::commit();

            // After update/create, return the current rows for this racikan so client can sync
            $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');

            $updatedRows = ResepDokter::with('obat')
                ->where('visitation_id', $visitationId)
                ->where('racikan_ke', $racikanKe)
                ->get()
                ->map(function($r) use ($gudangId) {
                    // Guard against missing obat relation
                    $stokGudang = ($gudangId && $r->obat) ? $r->obat->getStokByGudang($gudangId) : 0;
                    return [
                        'id' => $r->id,
                        'obat_id' => $r->obat_id,
                        'dosis' => $r->dosis,
                        'jumlah' => $r->jumlah ?? 1,
                        'obat_nama' => $r->obat->nama ?? '',
                        'stok_gudang' => (int) $stokGudang
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Racikan berhasil diupdate',
                'created_ids' => $createdIds ?? [],
                'updated_ids' => $updatedIds ?? [],
                'obats' => $updatedRows
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate racikan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ERESEP FARMASI

    public function farmasicreate($visitationId)
    {
        $visitation = Visitation::with('invoice')->findOrFail($visitationId);
        $invoicePaymentMethod = is_null(optional($visitation->invoice)->payment_method)
            ? null
            : trim((string) $visitation->invoice->payment_method);
        if ($invoicePaymentMethod === '') $invoicePaymentMethod = null;

        $isInvoiceLocked = !is_null($invoicePaymentMethod);
        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        // Ambil pasien id
        $pasienId = $visitation->pasien_id;

        // Ambil zat aktif yang pasien alergi
        $zatAlergi = DB::table('erm_alergi')
            ->where('pasien_id', $pasienId)
            ->pluck('zataktif_id')
            ->toArray();

        // Ambil obat yang tidak punya zat aktif dari alergi
        $obats = Obat::whereDoesntHave('zatAktifs', function ($query) use ($zatAlergi) {
            $query->whereIn('erm_zataktif.id', $zatAlergi);
        })->get();

        // $obats = Obat::all();

        // Ambil semua resep berdasarkan visitation_id
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)->with('obat')->get();


        // Kelompokkan racikan berdasarkan racikan_ke
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Ambil non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');

        // Hitung nilai racikan_ke terakhir dari database
        $lastRacikanKe = $reseps->whereNotNull('racikan_ke')->max('racikan_ke') ?? 0;

        $wadah = WadahObat::all();

        // Ambil catatan resep dari erm_resepdetail
        $catatan_resep = ResepDetail::where('visitation_id', $visitationId)->value('catatan_dokter');

        return view('erm.eresep.farmasi.create', array_merge([
            'visitation' => $visitation,
            'invoicePaymentMethod' => $invoicePaymentMethod,
            'isInvoiceLocked' => $isInvoiceLocked,
            'obats' => $obats,
            'wadah' => $wadah,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'lastRacikanKe' => $lastRacikanKe,
            'catatan_resep' => $catatan_resep,
        ], $pasienData, $createKunjunganData));
    }
    public function copyFromDokter($visitationId)
    {
        if ($resp = $this->guardInvoiceNotLocked($visitationId)) return $resp;
        if (ResepFarmasi::where('visitation_id', $visitationId)->exists()) {
            return response()->json(['status' => 'info', 'message' => 'Resep sudah pernah disalin ke Farmasi.']);
        }

        $reseps = ResepDokter::where('visitation_id', $visitationId)->get();

        // all or nothing: a half copy would block retrying ("sudah pernah disalin")
        DB::transaction(function () use ($reseps) {
            foreach ($reseps as $resep) {
                // Retrieve the harga of the obat; a racikan component is prorated by its dosis
                $obat = Obat::find($resep->obat_id);
                $harga = !$obat ? null : ($resep->racikan_ke ? ResepFarmasi::hargaRacikan($obat, $resep->dosis) : $obat->harga_nonfornas);
                // Generate a unique custom ID
                do {
                    $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
                } while (ResepFarmasi::where('id', $customId)->exists());

                ResepFarmasi::create([
                    'id'             => $customId, // Store the custom ID here
                    'visitation_id'  => $resep->visitation_id,
                    'obat_id'        => $resep->obat_id,
                    'jumlah'         => $resep->jumlah,
                    'aturan_pakai'   => $resep->aturan_pakai,
                    'racikan_ke'     => $resep->racikan_ke,
                    'paket_racikan_id'   => $resep->paket_racikan_id,
                    'paket_racikan_nama' => $resep->paket_racikan_nama,
                    'wadah_id'       => $resep->wadah_id, // FIXED: use wadah_id, not wadah
                    'bungkus'        => $resep->bungkus,
                    'dosis'          => $resep->dosis,
                    'dokter_id'      => optional($resep->visitation)->dokter_id,
                    'harga'          => $harga,
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Berhasil menyalin resep ke Farmasi.']);
    }
    public function getFarmasiResepJson($visitationId)
    {
        $reseps = ResepFarmasi::with('obat')
            ->where('visitation_id', $visitationId)
            ->get();

        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke')->values();
        $nonRacikans = $reseps->whereNull('racikan_ke')->values();

        return response()->json([
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
        ]);
    }

    public function farmasistoreNonRacikan(Request $request)
    {
        if ($resp = $this->guardInvoiceNotLocked($request->input('visitation_id'))) return $resp;

        $validated = $request->validate([
            'visitation_id' => 'required',
            'obat_id' => 'required',
            'jumlah' => 'required',
            'aturan_pakai' => 'required',

            'diskon' => 'required',
            'harga' => 'required',
        ]);
        $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

        $resep = ResepFarmasi::create([
            'id' => $customId,
            'created_at' => Carbon::now(),
            'visitation_id' => $validated['visitation_id'],
            'obat_id' => $validated['obat_id'],
            'jumlah' => $validated['jumlah'],
            'aturan_pakai' => $validated['aturan_pakai'],
            'diskon' => $validated['diskon'],
            'harga' => $validated['harga'],
            'user_id' => Auth::id(),
        ]);

    // Load relasi obat agar bisa diakses dari JS
    $resep->load('obat');
    // Get mapped gudang for resep
    $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
    // Guard against missing obat relation
    $stokGudang = ($gudangId && $resep->obat) ? $resep->obat->getStokByGudang($gudangId) : 0;
        // Add stok_gudang to obat data
        $obatData = $resep->obat->toArray();
        $obatData['stok_gudang'] = (int) $stokGudang;

        $responseData = $resep->toArray();
        $responseData['obat'] = $obatData;

        return response()->json([
            'success' => true,
            'message' => 'Obat non-racikan berhasil disimpan.',
            'data' => $responseData
        ]);
    }

   public function farmasistoreRacikan(Request $request)
{
    if ($resp = $this->guardInvoiceNotLocked($request->input('visitation_id'))) return $resp;
    $validated = $request->validate([
        'visitation_id' => 'required',
        'racikan_ke' => 'required|integer',
        'wadah' => 'required',
        'bungkus' => 'required|integer',
        'aturan_pakai' => 'required|string',
        'obats' => 'required|array|min:1',
        'obats.*.obat_id' => 'required',
        'obats.*.dosis' => 'required|string',
    ]);

    [$racikanKe, $createdObats] = DB::transaction(function () use ($validated) {
        $createdRows = [];
        // The client numbers its cards; take the next free number if this one is already used (other tab, paket applied meanwhile)
        $racikanKe = PaketRacikan::freeRacikanKe(ResepFarmasi::class, $validated['visitation_id'], $validated['racikan_ke']);

        foreach ($validated['obats'] as $obat) {
            do {
                $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
            } while (ResepFarmasi::where('id', $customId)->exists());

            $obatModel = Obat::findOrFail($obat['obat_id']);
            $harga = ResepFarmasi::hargaRacikan($obatModel, $obat['dosis']);
            $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
            $stokGudang = $gudangId ? $obatModel->getStokByGudang($gudangId) : 0;
            $created = ResepFarmasi::create([
                'id' => $customId,
                'visitation_id' => $validated['visitation_id'],
                'obat_id' => $obat['obat_id'],
                'aturan_pakai' => $validated['aturan_pakai'],
                'racikan_ke' => $racikanKe,
                'wadah_id' => $validated['wadah'],
                'bungkus' => $validated['bungkus'],
                'dosis' => $obat['dosis'],
                'harga' => $harga,
                'created_at' => now(),
                'user_id' => Auth::id(),
            ]);
            $createdRows[] = [
                'id' => $created->id,
                'obat_id' => $created->obat_id,
                'dosis' => $created->dosis,
                'jumlah' => $created->jumlah ?? 1,
                'stok_gudang' => (int) $stokGudang
            ];
        }

        return [$racikanKe, $createdRows];
    });
    return response()->json([
        'success' => true,
        'message' => 'Racikan berhasil disimpan.',
        'racikan_ke' => $racikanKe,
        'obats' => $createdObats
    ]);
}

    public function farmasidestroyNonRacikan($id)
    {
        $resep = ResepFarmasi::findOrFail($id);
        if ($resp = $this->guardInvoiceNotLocked($resep->visitation_id)) return $resp;

        Billing::where('visitation_id', $resep->visitation_id)
            ->where('billable_type', ResepFarmasi::class)
            ->where('billable_id', $resep->id)
            ->delete();

        $resep->delete();
        PaketRacikan::syncRacikanLink(ResepFarmasi::class, $resep->visitation_id, $resep->racikan_ke);

        return response()->json(['message' => 'Resep berhasil dihapus']);
    }

    public function farmasidestroyRacikan($racikanKe, Request $request)
    {
        $visitationId = $request->visitation_id;
        if ($resp = $this->guardInvoiceNotLocked($visitationId)) return $resp;

        // Delete ALL records with matching racikan_ke and visitation_id
        $reseps = ResepFarmasi::where('racikan_ke', $racikanKe)
            ->where('visitation_id', $visitationId)
            ->get();

        if ($reseps->isEmpty()) {
            return response()->json(['message' => 'Racikan tidak ditemukan'], 404);
        }

        // Delete each model instance so model events (observers) run and keep billing/invoice in sync
        foreach ($reseps as $r) {
            try {
                Billing::where('visitation_id', $r->visitation_id)
                    ->where('billable_type', ResepFarmasi::class)
                    ->where('billable_id', $r->id)
                    ->delete();

                $r->delete();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed deleting resep id '.$r->id.': '.$e->getMessage());
            }
        }

        return response()->json(['message' => 'Racikan berhasil dihapus']);
    }

    public function farmasiupdateNonRacikan(Request $request, $id)
    {
        $resep = ResepFarmasi::findOrFail($id);

        // In invoice-locked state, allow editing ONLY aturan_pakai
        if ($this->isInvoiceLockedForVisitation((string) $resep->visitation_id)) {
            $data = $request->validate([
                'aturan_pakai' => 'required|string|max:255',
            ]);

            $resep->update([
                'aturan_pakai' => $data['aturan_pakai'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aturan pakai berhasil diubah',
                'data'    => $resep,
            ]);
        }

        if ($resp = $this->guardInvoiceNotLocked($resep->visitation_id)) return $resp;

        $data = $request->validate([
            'jumlah'       => 'required|integer|min:1',
            'diskon'       => 'integer|max:100',
            'aturan_pakai' => 'required|string|max:255',
        ]);

        // Retrieve the existing total value
        $existingTotal = $resep->harga;

        // Calculate the new total after applying the discount
        $diskon = $data['diskon'] ?? 0; // Default diskon to 0 if not provided
        $newTotal = $existingTotal * (1 - $diskon / 100);

        // Update the prescription with the calculated total and other fields
        $resep->update(array_merge($data, ['harga' => $newTotal]));

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil diubah',
            'data'    => $resep,
        ]);
    }

    public function farmasiupdateRacikan(Request $request, $racikanKe)
    {
        try {
            $visitationIdInput = (string) $request->input('visitation_id');

            // In invoice-locked state, allow editing ONLY aturan_pakai (no item changes)
            if ($visitationIdInput !== '' && $this->isInvoiceLockedForVisitation($visitationIdInput)) {
                $validatedLocked = $request->validate([
                    'visitation_id' => 'required',
                    'aturan_pakai' => 'required|string|max:255',
                ]);

                ResepFarmasi::where('visitation_id', $validatedLocked['visitation_id'])
                    ->where('racikan_ke', $racikanKe)
                    ->update([
                        'aturan_pakai' => $validatedLocked['aturan_pakai'],
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Aturan pakai racikan berhasil diupdate',
                ]);
            }

            if ($resp = $this->guardInvoiceNotLocked($request->input('visitation_id'))) return $resp;
            // Log incoming request for debugging
            \Illuminate\Support\Facades\Log::info('farmasiupdateRacikan called', ['racikanKe' => $racikanKe, 'payload' => $request->all()]);

            $validated = $request->validate([
                'visitation_id' => 'required',
                'wadah' => 'nullable',
                'bungkus' => 'required|integer',
                'aturan_pakai' => 'required|string',
                'obats' => 'required|array',
                'obats.*.obat_id' => 'nullable',
                'obats.*.dosis' => 'nullable',
                'obats.*.jumlah' => 'nullable',
            ]);

            $visitationId = $validated['visitation_id'];
            $wadahId = $validated['wadah'] ?? null;
            $bungkus = $validated['bungkus'];
            $aturanPakai = $validated['aturan_pakai'];
            $obats = $validated['obats'];

            DB::beginTransaction();
            $linkBefore = PaketRacikan::racikanLink(ResepFarmasi::class, $visitationId, $racikanKe);
            // Get all resep rows for this racikan_ke and visitation
            $existingReseps = \App\Models\ERM\ResepFarmasi::where('visitation_id', $visitationId)
                ->where('racikan_ke', $racikanKe)
                ->get();

            $existingById = $existingReseps->keyBy('id');
            $matchedExistingIds = [];

            $createdIds = [];
            $updatedIds = [];

            // Update or create resep rows for each obat.
            // Fallback matching by obat_id protects signa-only updates when the client payload
            // lacks the persisted resep row ids.
            foreach ($obats as $obatData) {
                $resep = null;
                $incomingId = $obatData['id'] ?? null;

                if (!empty($incomingId) && $existingById->has($incomingId)) {
                    $resep = $existingById->get($incomingId);
                } elseif (!empty($obatData['obat_id'])) {
                    $resep = $existingReseps->first(function ($existing) use ($obatData, $matchedExistingIds) {
                        return !in_array($existing->id, $matchedExistingIds, true)
                            && (string) $existing->obat_id === (string) $obatData['obat_id'];
                    });
                }

                if ($resep) {
                    $changes = [
                        'obat_id' => $obatData['obat_id'],
                        'dosis' => $obatData['dosis'] ?? '',
                        'jumlah' => $obatData['jumlah'] ?? 1,
                        'wadah_id' => $wadahId,
                        'bungkus' => $bungkus,
                        'aturan_pakai' => $aturanPakai,
                    ];
                    // harga is per dosis: recalc when obat or dosis changed, otherwise billing keeps the old price
                    if ((string) $resep->obat_id !== (string) $changes['obat_id'] || (string) $resep->dosis !== (string) $changes['dosis'] || $resep->harga === null) {
                        $changes['harga'] = ResepFarmasi::hargaRacikan(Obat::find($changes['obat_id']), $changes['dosis']);
                    }
                    $resep->update($changes);
                    $matchedExistingIds[] = $resep->id;
                    $updatedIds[] = $resep->id;
                } elseif (!empty($obatData['obat_id'])) {
                    do {
                        $customId = now()->format('YmdHis') . strtoupper(\Illuminate\Support\Str::random(7));
                    } while (\App\Models\ERM\ResepFarmasi::where('id', $customId)->exists());
                    $new = \App\Models\ERM\ResepFarmasi::create([
                        'id' => $customId,
                        'visitation_id' => $visitationId,
                        'obat_id' => $obatData['obat_id'],
                        'dosis' => $obatData['dosis'] ?? '',
                        'harga' => ResepFarmasi::hargaRacikan(Obat::find($obatData['obat_id']), $obatData['dosis'] ?? ''),
                        'jumlah' => $obatData['jumlah'] ?? 1,
                        'racikan_ke' => $racikanKe,
                        'wadah_id' => $wadahId,
                        'bungkus' => $bungkus,
                        'aturan_pakai' => $aturanPakai,
                        'user_id' => Auth::id(),
                        'created_at' => now(),
                    ]);
                    $matchedExistingIds[] = $new->id;
                    $createdIds[] = $new->id;
                    \Illuminate\Support\Facades\Log::info('Created resep', ['id' => $new->id, 'obat_id' => $new->obat_id]);
                }
            }

            foreach ($existingReseps as $resep) {
                if (!in_array($resep->id, $matchedExistingIds, true)) {
                    \Illuminate\Support\Facades\Log::info('Deleting resep', ['id' => $resep->id]);
                    $resep->delete();
                }
            }

            PaketRacikan::syncRacikanLink(ResepFarmasi::class, $visitationId, $racikanKe, $linkBefore);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Racikan berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Illuminate\Support\Facades\Log::error('Error updating racikan: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // SUBMIT RESEP

    public function submitResep(Request $request)
{
    $visitationId = $request->input('visitation_id');
    $force = $request->input('force', false);

        if ($resp = $this->guardInvoiceNotLocked($visitationId)) return $resp;

    // Check if resep already submitted
    $resepDetail = \App\Models\ERM\ResepDetail::where('visitation_id', $visitationId)->first();
    if ($resepDetail && $resepDetail->status == 1 && !$force) {
        return response()->json([
            'status' => 'warning',
            'message' => 'Resep sudah pernah disubmit. Lanjutkan dan timpa billing lama?',
            'need_confirm' => true
        ], 200);
    }

    DB::beginTransaction();
    
    try {
        // Always re-sync billing rows for resep farmasi on submit.
        // Prevents stale/duplicate billing rows if resep items were deleted/edited after a previous submit.
        Billing::withTrashed()
            ->where('visitation_id', $visitationId)
            ->where('billable_type', ResepFarmasi::class)
            ->forceDelete();

        // Fetch all related prescriptions
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)->with(['obat', 'wadah'])->get();
        // For racikan entries, compute stok dikurangi and persist to `jumlah_racikan` so billing/stock reduction
        // can use this value per component while invoice qty remains as `bungkus`.
        foreach ($reseps as $r) {
            if ($r->racikan_ke && $r->racikan_ke > 0) {
                $stokDikurangi = $this->calculateRacikanStockReduction($r);
                // Only update if different to avoid unnecessary writes
                if (abs((float) $r->jumlah_racikan - (float) $stokDikurangi) > 0.0001) {
                    $r->jumlah_racikan = $stokDikurangi;
                    try { $r->save(); } catch (\Exception $ex) { Log::warning('Failed to save stokDikurangi for resep id '.$r->id.': '.$ex->getMessage()); }
                }
            }
        }
        
        // Update resepdetail status to 1
        \App\Models\ERM\ResepDetail::updateOrCreate(
            ['visitation_id' => $visitationId],
            ['status' => 1, 'submitted_at' => now()]
        );

        // Create billing records (NO STOCK REDUCTION HERE)
        foreach ($reseps as $resep) {
            // Ensure harga is present; some legacy/create paths may not set it (e.g., paket-copy, update create)
            if ($resep->harga === null) {
                $resep->harga = $resep->racikan_ke
                    ? ResepFarmasi::hargaRacikan($resep->obat, $resep->dosis)
                    : (float) ($resep->obat->harga_nonfornas ?? 0);
                try { $resep->save(); } catch (\Exception $ex) {
                    Log::warning('Failed to backfill harga for resep id '.$resep->id.': '.$ex->getMessage());
                }
            }

            $qty = $resep->racikan_ke ? ($resep->bungkus ?? 1) : ($resep->jumlah ?? 1);
            
            Billing::updateOrCreate(
                [
                    'visitation_id' => $resep->visitation_id,
                    'billable_id' => $resep->id,
                    'billable_type' => ResepFarmasi::class,
                ],
                [
                    'visitation_id' => $resep->visitation_id,
                    'qty' => $qty,
                    'jumlah' => $resep->harga ?? 0,
                    'keterangan' => 'Obat: ' . ($resep->obat->nama ?? 'Tanpa Nama') . 
                                    ($resep->racikan_ke ? ' (Racikan #' . $resep->racikan_ke . ')' : ''),
                ]
            );
        }
        
        DB::commit();

        // Resolve patient name for better UX in notifications/popups
        $visitation = Visitation::with('pasien')->find($visitationId);
        $pasienNama = ($visitation && $visitation->pasien) ? $visitation->pasien->nama : null;
        $pasienSuffix = $pasienNama ? " (" . $pasienNama . ")" : "";
        
        // Notify Kasir users that resep has been submitted
        try {
            $message = "Resep{$pasienSuffix} untuk kunjungan ID: {$visitationId} telah disubmit ke billing.";
            $kasirs = \App\Models\User::role('Kasir')->get();
            foreach ($kasirs as $kasir) {
                // avoid duplicate DB notifications check is omitted for brevity
                $kasir->notify(new \App\Notifications\FarmasiToKasirNotification($message));
            }
        } catch (\Exception $e) {
            // Log and continue - notification failure shouldn't block response
            \Illuminate\Support\Facades\Log::error('Failed notifying Kasir after submitResep: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => $pasienNama
                ? "Resep pasien: {$pasienNama} (Kunjungan ID: {$visitationId}) berhasil disubmit ke billing!"
                : 'Resep berhasil disubmit ke billing!',
            'patient_name' => $pasienNama,
            'visitation_id' => $visitationId,
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error submitting resep', [
            'visitation_id' => $visitationId,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}

    // RIWAYAT DOKTER & FARMASI

    public function getRiwayatDokter($pasienId)
    {
        $reseps = ResepDokter::with([
            'obat' => function($q) { $q->withInactive(); },
            'visitation'
        ])
            ->whereHas('visitation', fn($q) => $q->where('pasien_id', $pasienId))
            ->orderByDesc(
                \App\Models\ERM\Visitation::select('tanggal_visitation')
                    ->whereColumn('erm_visitations.id', 'erm_resepdokter.visitation_id')
                    ->limit(1)
            )
            ->get()
            ->groupBy('visitation_id');

        $racikanPaketNames = $this->racikanPaketNames($reseps);

        return view('erm.partials.resep-riwayatdokter', compact('reseps', 'racikanPaketNames'));
    }

    public function getRiwayatFarmasi($pasienId)
    {
        $reseps = ResepFarmasi::with(['obat', 'visitation'])
            ->whereHas('visitation', fn($q) => $q->where('pasien_id', $pasienId))
            ->orderByDesc(
                \App\Models\ERM\Visitation::select('tanggal_visitation')
                    ->whereColumn('erm_visitations.id', 'erm_resepfarmasi.visitation_id')
                    ->limit(1)
            )
            ->get()
            ->groupBy('visitation_id');

        $racikanPaketNames = $this->racikanPaketNames($reseps);

        return view('erm.partials.resep-riwayatfarmasi', compact('reseps', 'racikanPaketNames'));
    }

    /**
     * [visitation_id][racikan_ke] => paket name for resep rows grouped by visitation:
     * the paket stored on the racikan, otherwise the paket with exactly its isi.
     */
    private function racikanPaketNames($resepsByVisitation): array
    {
        $resolve = PaketRacikan::racikanNameResolver();
        $names = [];
        foreach ($resepsByVisitation as $visitationId => $group) {
            foreach ($group->whereNotNull('racikan_ke')->groupBy('racikan_ke') as $ke => $items) {
                if ($name = $resolve($items)) {
                    $names[$visitationId][$ke] = $name;
                }
            }
        }

        return $names;
    }

    //Wadah Obat
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $wadahs = WadahObat::where('nama', 'like', '%' . $query . '%')->get();

        return response()->json($wadahs->map(function ($wadah) {
            return [
                'id' => $wadah->id,
                'text' => $wadah->nama . ' ' . $wadah->harga,
            ];
        }));
    }

    public function getResepDokterByVisitation($visitationId)
    {
        $nonRacikans = ResepDokter::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNull('racikan_ke')
            ->get();

        $racikans = ResepDokter::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNotNull('racikan_ke')
            ->get()
            ->groupBy('racikan_ke');

        return response()->json([
            'success' => true,
            'non_racikans' => $nonRacikans,
            'racikans' => $racikans
        ]);
    }

    public function getResepFarmasiByVisitation($visitationId)
    {
        $nonRacikans = ResepFarmasi::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNull('racikan_ke')
            ->get();

        $racikans = ResepFarmasi::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNotNull('racikan_ke')
            ->get()
            ->groupBy('racikan_ke');

        return response()->json([
            'success' => true,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans
        ]);
    }

    public function printResep($visitationId)
    {
        // Get the visitation data with all necessary relations
        $visitation = Visitation::with(['pasien', 'dokter.user', 'metodeBayar', 'klinik'])->findOrFail($visitationId);

        // Get prescription items
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)
            ->with(['obat', 'wadah'])
            ->get();

        // Separate racikan and non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Get patient allergies
        $alergis = DB::table('erm_alergi')
            ->join('erm_zataktif', 'erm_alergi.zataktif_id', '=', 'erm_zataktif.id')
            ->where('erm_alergi.pasien_id', $visitation->pasien_id)
            ->select('erm_zataktif.nama as zataktif_nama', 'erm_alergi.katakunci')
            ->get();

        // Get asesmen penunjang data for diagnoses and follow-up
        $asesmenPenunjang = DB::table('erm_asesmen_penunjang')
            ->where('visitation_id', $visitationId)
            ->first();

        // Get no_resep from ResepDetail
        $noResep = \App\Models\ERM\ResepDetail::where('visitation_id', $visitationId)->value('no_resep');

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.print', [
            'visitation' => $visitation,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'alergis' => $alergis,
            'asesmenPenunjang' => $asesmenPenunjang,
            'noResep' => $noResep,
        ]);

        // Set PDF options for A4 landscape
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);

        // Return the PDF for download or inline display
        return $pdf->stream('resep-' . $visitation->id . '.pdf');
    }

    public function getApotekers()
    {
        $apotekers = User::role('Farmasi')->get(['id', 'name']);
        return response()->json($apotekers);
    }

    public function storeEdukasiObat(Request $request)
    {
        $validated = $request->validate([
            'visitation_id' => 'required',
            'simpan_etiket_label' => 'boolean',
            'simpan_suhu_kulkas' => 'boolean',
            'simpan_tempat_kering' => 'boolean',
            'hindarkan_jangkauan_anak' => 'boolean',
            'insulin_brosur' => 'nullable|string',
            'inhalasi_brosur' => 'nullable|string',
            'apoteker_id' => 'required',
            // 'total_pembayaran' => 'nullable|numeric',
        ]);

        // Create or update edukasi
        $edukasi = EdukasiObat::updateOrCreate(
            ['visitation_id' => $validated['visitation_id']],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Edukasi obat berhasil disimpan',
            'data' => $edukasi
        ]);
    }

    public function printEdukasiObat($visitationId)
    {
        // Get the visitation data with all necessary relations
        $visitation = Visitation::with(['pasien', 'dokter.user', 'metodeBayar', 'klinik'])
            ->findOrFail($visitationId);

        // Get edukasi data
        $edukasi = EdukasiObat::with('apoteker')
            ->where('visitation_id', $visitationId)
            ->first();

        if (!$edukasi) {
            return redirect()->back()->with('error', 'Data edukasi obat tidak ditemukan');
        }

        // Get prescription items
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)
            ->with(['obat'])
            ->get();

        // Separate racikan and non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Get patient allergies
        $alergis = DB::table('erm_alergi')
            ->join('erm_zataktif', 'erm_alergi.zataktif_id', '=', 'erm_zataktif.id')
            ->where('erm_alergi.pasien_id', $visitation->pasien_id)
            ->select('erm_zataktif.nama as zataktif_nama')
            ->get();

        // Get no_resep from ResepDetail
        $noResep = \App\Models\ERM\ResepDetail::where('visitation_id', $visitationId)->value('no_resep');

        // Generate QR code data for apoteker and pasien
        $apotekerQr = 'APOTEKER|' .
            ($edukasi->apoteker->id ?? '-') . '|' .
            ($edukasi->apoteker->name ?? '-') . '|' .
            now()->format('Y-m-d H:i:s');

        $pasienQr = 'PASIEN|' .
            ($visitation->pasien->id ?? '-') . '|' .
            ($visitation->pasien->nama ?? '-') . '|' .
            now()->format('Y-m-d H:i:s');

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.edukasi-print', [
            'visitation' => $visitation,
            'edukasi' => $edukasi,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'alergis' => $alergis,
            'noResep' => $noResep,
            'apotekerQr' => $apotekerQr,
            'pasienQr' => $pasienQr,
        ]);

        // Set PDF options
        $pdf->setPaper('a4', 'portrait');

        // Return the PDF for download or inline display
        return $pdf->stream('edukasi-obat-' . $visitationId . '.pdf');
    }

    public function printEtiket($visitationId)
    {
        // Get the visitation data with all necessary relations
        $visitation = Visitation::with(['pasien', 'dokter.user', 'metodeBayar', 'klinik'])->findOrFail($visitationId);

        // Get all prescription items
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)
            ->with(['obat', 'wadah'])
            ->get();

        $nonRacikans = $reseps->whereNull('racikan_ke');
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        if ($reseps->isEmpty()) {
            return back()->with('error', 'Tidak ada obat untuk dicetak etiket.');
        }

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.etiket-print', [
            'visitation' => $visitation,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
        ]);

        // Set PDF options for 78x60mm (7.8cm x 6cm) landscape format
        // Convert to points: 1cm = 28.35 points
        $height = 78 * 2.835; // 221.13 points
        $width= 60 * 2.835; // 170.1 points
        
        $pdf->setPaper([0, 0, $width, $height], 'landscape');
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);

        // Return the PDF for download or inline display
        return $pdf->stream('etiket-' . $visitation->id . '.pdf');
    }

    public function copyFromHistory(Request $request)
    {
        $validated = $request->validate([
            'source_visitation_id' => 'required',
            'target_visitation_id' => 'required',
            'source_type' => 'required|in:dokter,farmasi',
        ]);

        $targetVisitationId = $validated['target_visitation_id'];
        $sourceVisitationId = $validated['source_visitation_id'];
        $sourceType = $validated['source_type'];

        // Check if prescription already exists for target visitation
        if (ResepDokter::where('visitation_id', $targetVisitationId)->exists()) {
            return response()->json([
                'status' => 'info',
                'message' => 'Resep sudah ada untuk kunjungan ini. Harap hapus resep yang ada sebelum menyalin yang baru.'
            ]);
        }

        // Fetch source prescriptions based on source type
        $sourceReseps = $sourceType === 'dokter'
            ? ResepDokter::where('visitation_id', $sourceVisitationId)->get()
            : ResepFarmasi::where('visitation_id', $sourceVisitationId)->get();

        if ($sourceReseps->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada resep yang ditemukan untuk disalin.'
            ]);
        }

        // Copy each prescription item
        foreach ($sourceReseps as $resep) {
            // Generate unique ID
            $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

            // Get obat information if needed
            $obat = Obat::find($resep->obat_id);
            $baseHarga = $obat ? $obat->harga_nonfornas : 0;
            // Extract numeric value from dosis strings
            preg_match('/(\d+(?:[.,]\d+)?)/', $resep->dosis, $inputMatches);
            preg_match('/(\d+(?:[.,]\d+)?)/', $obat->dosis ?? '', $baseMatches);
            $inputDosis = isset($inputMatches[1]) ? floatval(str_replace(',', '.', $inputMatches[1])) : 0;
            $baseDosis = isset($baseMatches[1]) ? floatval(str_replace(',', '.', $baseMatches[1])) : 0;
            $dosisRatio = ($baseDosis > 0 && $inputDosis > 0) ? ($inputDosis / $baseDosis) : 1;
            $harga = $baseHarga * $dosisRatio;
            Log::info('COPY FROM HISTORY DEBUG', [
                'resep_id' => $resep->id ?? null,
                'obat_id' => $resep->obat_id ?? null,
                'base_harga' => $baseHarga,
                'input_dosis' => $inputDosis,
                'base_dosis' => $baseDosis,
                'dosis_ratio' => $dosisRatio,
                'harga' => $harga,
                'jumlah' => $resep->jumlah ?? null,
                'total' => $harga * ($resep->jumlah ?? 1),
            ]);

            // Create new ResepFarmasi record
            ResepDokter::create([
                'id' => $customId,
                'visitation_id' => $targetVisitationId,
                'obat_id' => $resep->obat_id,
                'jumlah' => $resep->jumlah,
                'dosis' => $resep->dosis,
                'bungkus' => $resep->bungkus,
                'racikan_ke' => $resep->racikan_ke,
                'paket_racikan_id' => $resep->paket_racikan_id,
                'paket_racikan_nama' => $resep->paket_racikan_nama,
                'aturan_pakai' => $resep->aturan_pakai,
                'wadah_id' => $resep->wadah_id,
                'harga' => $harga,
                'diskon' => 0, // Default value
                'total' => $harga * ($resep->jumlah ?? 1),
                'created_at' => now(),
                'user_id' => Auth::id(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Resep berhasil disalin ke farmasi.',
            'redirect' => route('erm.eresepfarmasi.create', ['visitation_id' => $targetVisitationId])
        ]);
    }
    public function copyFromHistoryFarmasi(Request $request)
    {
        $validated = $request->validate([
            'source_visitation_id' => 'required',
            'target_visitation_id' => 'required',
            'source_type' => 'required|in:dokter,farmasi',
        ]);

        $targetVisitationId = $validated['target_visitation_id'];
        $sourceVisitationId = $validated['source_visitation_id'];
        $sourceType = $validated['source_type'];

        // Check if prescription already exists for target visitation
        if (ResepFarmasi::where('visitation_id', $targetVisitationId)->exists()) {
            return response()->json([
                'status' => 'info',
                'message' => 'Resep sudah ada untuk kunjungan ini. Harap hapus resep yang ada sebelum menyalin yang baru.'
            ]);
        }

        // Fetch source prescriptions based on source type
        $sourceReseps = $sourceType === 'dokter'
            ? ResepDokter::where('visitation_id', $sourceVisitationId)->get()
            : ResepFarmasi::where('visitation_id', $sourceVisitationId)->get();

        if ($sourceReseps->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada resep yang ditemukan untuk disalin.'
            ]);
        }

        // Copy each prescription item
        foreach ($sourceReseps as $resep) {
            // Generate unique ID
            $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

            // Get obat information if needed
            $obat = Obat::find($resep->obat_id);
            $baseHarga = $obat ? $obat->harga_nonfornas : 0;
            // Extract numeric value from dosis strings
            preg_match('/(\d+(?:[.,]\d+)?)/', $resep->dosis, $inputMatches);
            preg_match('/(\d+(?:[.,]\d+)?)/', $obat->dosis ?? '', $baseMatches);
            $inputDosis = isset($inputMatches[1]) ? floatval(str_replace(',', '.', $inputMatches[1])) : 0;
            $baseDosis = isset($baseMatches[1]) ? floatval(str_replace(',', '.', $baseMatches[1])) : 0;
            $dosisRatio = ($baseDosis > 0 && $inputDosis > 0) ? ($inputDosis / $baseDosis) : 1;
            $harga = $baseHarga * $dosisRatio;
            Log::info('COPY FROM HISTORY FARMASI DEBUG', [
                'resep_id' => $resep->id ?? null,
                'obat_id' => $resep->obat_id ?? null,
                'base_harga' => $baseHarga,
                'input_dosis' => $inputDosis,
                'base_dosis' => $baseDosis,
                'dosis_ratio' => $dosisRatio,
                'harga' => $harga,
                'jumlah' => $resep->jumlah ?? null,
                'total' => $harga * ($resep->jumlah ?? 1),
            ]);

            // Get obat for satuan information
            $obat = Obat::find($resep->obat_id);
            
            // Create new ResepFarmasi record
            ResepFarmasi::create([
                'id' => $customId,
                'visitation_id' => $targetVisitationId,
                'obat_id' => $resep->obat_id,
                'jumlah' => $resep->jumlah,
                'dosis' => $resep->dosis . ($obat && !str_contains($resep->dosis, $obat->satuan) ? ' ' . $obat->satuan : ''),
                'bungkus' => $resep->bungkus,
                'racikan_ke' => $resep->racikan_ke,
                'paket_racikan_id' => $resep->paket_racikan_id,
                'paket_racikan_nama' => $resep->paket_racikan_nama,
                'aturan_pakai' => $resep->aturan_pakai,
                'wadah_id' => $resep->wadah_id,
                'harga' => $harga,
                'diskon' => 0, // Default value
                'total' => $harga * ($resep->jumlah ?? 1),
                'created_at' => now(),
                'user_id' => Auth::id(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Resep berhasil disalin ke farmasi.',
            'redirect' => route('erm.eresepfarmasi.create', ['visitation_id' => $targetVisitationId])
        ]);
    }
    
    // PAKET RACIKAN METHODS
    public function getPaketRacikanList(\Illuminate\Http\Request $request)
    {
        $q = trim($request->input('q', ''));
        $paketRacikans = PaketRacikan::with(['details.obat', 'wadah'])
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama_paket', 'like', "%$q%")
                        ->orWhereHas('details.obat', function ($obatQ) use ($q) {
                            $obatQ->where('nama', 'like', "%$q%");
                        });
                });
            })
            ->orderBy('nama_paket')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paketRacikans
        ]);
    }

    public function copyFromPaketRacikan(Request $request)
    {
        $validated = $request->validate([
            'paket_racikan_id' => 'required|exists:erm_paket_racikan,id',
            'visitation_id' => 'required',
            'bungkus' => 'required|integer|min:1',
            'aturan_pakai' => 'required|string|max:255',
        ]);

        if ($resp = $this->guardInvoiceNotLocked($validated['visitation_id'] ?? null)) {
            return $resp;
        }

        $paketRacikan = PaketRacikan::with(['details.obat', 'wadah'])
            ->findOrFail($validated['paket_racikan_id']);
        if ($resp = $this->paketNotApplicable($paketRacikan)) {
            return $resp;
        }

        $visitationId = $validated['visitation_id'];
        $bungkus = $validated['bungkus'];
        $aturanPakai = $validated['aturan_pakai'];

        $newRacikanKe = DB::transaction(function () use ($paketRacikan, $visitationId, $bungkus, $aturanPakai) {
            // Lock sequence allocation so concurrent racikan creates do not reuse the same racikan_ke.
            $newRacikanKe = PaketRacikan::freeRacikanKe(ResepDokter::class, $visitationId);

            foreach ($paketRacikan->details as $detail) {
                do {
                    $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
                } while (ResepDokter::where('id', $customId)->exists());

                ResepDokter::create([
                    'id' => $customId,
                    'visitation_id' => $visitationId,
                    'obat_id' => $detail->obat_id,
                    'jumlah' => 1,
                    'aturan_pakai' => $aturanPakai, // Gunakan aturan pakai dari modal
                    'racikan_ke' => $newRacikanKe,
                    'paket_racikan_id' => $paketRacikan->id,
                    'paket_racikan_nama' => $paketRacikan->nama_paket,
                    'wadah_id' => $paketRacikan->wadah_id,
                    'bungkus' => $bungkus, // Gunakan bungkus dari modal
                    'dosis' => $detail->dosis,
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ]);
            }

            return $newRacikanKe;
        });

        // Return created rows with stok_gudang so client can display correct stock immediately
        $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
        $createdRows = ResepDokter::with('obat')
            ->where('visitation_id', $visitationId)
            ->where('racikan_ke', $newRacikanKe)
            ->get()
            ->map(function ($r) use ($gudangId) {
                $stokGudang = ($gudangId && $r->obat) ? $r->obat->getStokByGudang($gudangId) : 0;
                return [
                    'id' => $r->id,
                    'obat_id' => $r->obat_id,
                    'dosis' => $r->dosis,
                    'jumlah' => $r->jumlah ?? 1,
                    'obat_nama' => $r->obat->nama ?? '',
                    'stok_gudang' => (int) $stokGudang,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => "Paket racikan '{$paketRacikan->nama_paket}' berhasil ditambahkan dengan {$bungkus} bungkus.",
            'racikan_ke' => $newRacikanKe,
            'obats' => $createdRows,
        ]);
    }

    /**
     * 422 response when a paket cannot be applied to a resep: it is nonaktif, or holds obat that are
     * nonaktif/deleted in Master Obat (they would be added without a price). Null when it can be applied.
     */
    private function paketNotApplicable(PaketRacikan $paket)
    {
        if (!$paket->is_active) {
            return response()->json(['success' => false, 'message' => "Paket racikan '{$paket->nama_paket}' sedang nonaktif."], 422);
        }
        // details.obat is loaded with the active-only scope, so a missing obat is nonaktif or deleted
        $missing = $paket->details->filter(fn ($d) => !$d->obat);
        if ($missing->isNotEmpty()) {
            $names = Obat::withInactive()->whereIn('id', $missing->pluck('obat_id'))->pluck('nama')->implode(', ') ?: 'obat yang sudah dihapus';

            return response()->json([
                'success' => false,
                'message' => "Paket racikan '{$paket->nama_paket}' berisi obat nonaktif ({$names}). Perbarui paket di Master Data > Paket Racikan.",
            ], 422);
        }

        return null;
    }

    // New: copy paket racikan into resep farmasi (used by Farmasi page)
    public function copyFromPaketRacikanToFarmasi(Request $request)
    {
        $validated = $request->validate([
            'paket_racikan_id' => 'required|exists:erm_paket_racikan,id',
            'visitation_id' => 'required',
            'bungkus' => 'required|integer|min:1',
            'aturan_pakai' => 'required|string|max:255',
        ]);

        if ($resp = $this->guardInvoiceNotLocked($validated['visitation_id'] ?? null)) {
            return $resp;
        }

        $paketRacikan = PaketRacikan::with(['details.obat', 'wadah'])
            ->findOrFail($validated['paket_racikan_id']);
        if ($resp = $this->paketNotApplicable($paketRacikan)) {
            return $resp;
        }

        $visitationId = $validated['visitation_id'];
        $bungkus = $validated['bungkus'];
        $aturanPakai = $validated['aturan_pakai'];

        [$newRacikanKe, $createdRows] = DB::transaction(function () use ($paketRacikan, $visitationId, $bungkus, $aturanPakai) {
            // Lock sequence allocation so concurrent racikan creates do not reuse the same racikan_ke.
            $newRacikanKe = PaketRacikan::freeRacikanKe(ResepFarmasi::class, $visitationId);

            foreach ($paketRacikan->details as $detail) {
                do {
                    $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
                } while (ResepFarmasi::where('id', $customId)->exists());

                $harga = ResepFarmasi::hargaRacikan($detail->obat, $detail->dosis);

                ResepFarmasi::create([
                    'id' => $customId,
                    'visitation_id' => $visitationId,
                    'obat_id' => $detail->obat_id,
                    'jumlah' => 1,
                    'diskon' => 0,
                    'aturan_pakai' => $aturanPakai,
                    'racikan_ke' => $newRacikanKe,
                    'paket_racikan_id' => $paketRacikan->id,
                    'paket_racikan_nama' => $paketRacikan->nama_paket,
                    'wadah_id' => $paketRacikan->wadah_id,
                    'bungkus' => $bungkus,
                    'dosis' => $detail->dosis,
                    'harga' => $harga,
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ]);
            }

            $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
            $createdRows = ResepFarmasi::with('obat')
                ->where('visitation_id', $visitationId)
                ->where('racikan_ke', $newRacikanKe)
                ->get()
                ->map(function ($r) use ($gudangId) {
                    $stokGudang = ($gudangId && $r->obat) ? $r->obat->getStokByGudang($gudangId) : 0;
                    return [
                        'id' => $r->id,
                        'obat_id' => $r->obat_id,
                        'dosis' => $r->dosis,
                        'jumlah' => $r->jumlah ?? 1,
                        'obat_nama' => $r->obat->nama ?? '',
                        'stok_gudang' => (int) $stokGudang,
                    ];
                })
                ->values();

            return [$newRacikanKe, $createdRows];
        });

        return response()->json([
            'success' => true,
            'message' => "Paket racikan '{$paketRacikan->nama_paket}' berhasil diterapkan ke Farmasi dengan {$bungkus} bungkus.",
            'racikan_ke' => $newRacikanKe,
            'obats' => $createdRows,
        ]);
    }

    public function storePaketRacikan(Request $request)
    {
        $validated = $request->validate([
            'nama_paket' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'wadah_id' => 'nullable|exists:erm_wadah_obat,id',
            'bungkus_default' => 'required|integer|min:1',
            'aturan_pakai_default' => 'nullable|string',
            'obats' => 'required|array|min:1',
            'obats.*.obat_id' => 'required|exists:erm_obat,id',
            'obats.*.dosis' => 'required|string',
        ]);

        // Same isi under another name makes resep/billing history ambiguous
        PaketRacikan::assertUniqueComposition($validated['obats']);

        $paketRacikan = PaketRacikan::create([
            'nama_paket' => $validated['nama_paket'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'wadah_id' => $validated['wadah_id'],
            'bungkus_default' => $validated['bungkus_default'],
            'aturan_pakai_default' => $validated['aturan_pakai_default'],
            'created_by' => Auth::id(),
        ]);

        foreach ($validated['obats'] as $obat) {
            PaketRacikanDetail::create([
                'paket_racikan_id' => $paketRacikan->id,
                'obat_id' => $obat['obat_id'],
                'dosis' => $obat['dosis'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paket racikan berhasil disimpan.',
            'data' => $paketRacikan
        ]);
    }

    public function updatePaketRacikan(Request $request, $id)
    {
        $paketRacikan = PaketRacikan::findOrFail($id);

        $validated = $request->validate([
            'nama_paket' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'wadah_id' => 'nullable|exists:erm_wadah_obat,id',
            'bungkus_default' => 'required|integer|min:1',
            'aturan_pakai_default' => 'nullable|string',
            'obats' => 'required|array|min:1',
            'obats.*.obat_id' => 'required|exists:erm_obat,id',
            'obats.*.dosis' => 'required|string',
        ]);

        PaketRacikan::assertUniqueComposition($validated['obats'], $paketRacikan->id);

        $paketRacikan->update([
            'nama_paket' => $validated['nama_paket'],
            'deskripsi' => $validated['deskripsi'] ?? $paketRacikan->deskripsi,
            'wadah_id' => $validated['wadah_id'] ?? null,
            'bungkus_default' => $validated['bungkus_default'],
            'aturan_pakai_default' => $validated['aturan_pakai_default'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        // Replace details: delete existing and recreate
        PaketRacikanDetail::where('paket_racikan_id', $paketRacikan->id)->delete();
        foreach ($validated['obats'] as $obat) {
            PaketRacikanDetail::create([
                'paket_racikan_id' => $paketRacikan->id,
                'obat_id' => $obat['obat_id'],
                'dosis' => $obat['dosis'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paket racikan berhasil diperbarui.',
            'data' => $paketRacikan->fresh()->load('details.obat')
        ]);
    }

    public function deletePaketRacikan($id)
    {
        $paketRacikan = PaketRacikan::findOrFail($id);
        $paketRacikan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paket racikan berhasil dihapus.'
        ]);
    }

    // ETIKET BIRU METHODS
    
    /**
     * Get obat list for current visitation (for Etiket Biru modal)
     */
    public function getVisitationObat($visitationId)
    {
        try {
            $resepFarmasi = ResepFarmasi::with('obat')
                ->where('visitation_id', $visitationId)
                ->get();

            // stored paket of the racikan, or the paket with exactly this isi (ResepFarmasi::paket_racikan_name)
            $resolvePaketName = fn ($racikanGroup) => $racikanGroup->first()->paket_racikan_name ?? null;

            $nonRacikan = $resepFarmasi
                ->filter(function ($resep) {
                    return empty($resep->racikan_ke);
                })
                ->unique('obat_id')
                ->map(function ($resep) {
                    return [
                        'obat_id' => $resep->obat_id,
                        'obat_nama' => $resep->obat->nama ?? 'Unknown',
                        'racikan_ke' => null,
                        'dosis' => $resep->dosis,
                        'aturan_pakai' => $resep->aturan_pakai,
                        'paket_racikan_name' => null,
                    ];
                });

            $racikan = $resepFarmasi
                ->filter(function ($resep) {
                    return !empty($resep->racikan_ke);
                })
                ->groupBy('racikan_ke')
                ->flatMap(function ($racikanGroup) use ($resolvePaketName) {
                    $paketName = $resolvePaketName($racikanGroup);

                    return $racikanGroup->map(function ($resep) use ($paketName) {
                        return [
                            'obat_id' => $resep->obat_id,
                            'obat_nama' => $resep->obat->nama ?? 'Unknown',
                            'racikan_ke' => $resep->racikan_ke,
                            'dosis' => $resep->dosis,
                            'aturan_pakai' => $resep->aturan_pakai,
                            'paket_racikan_name' => $paketName,
                        ];
                    });
                });

            $result = $nonRacikan->concat($racikan)->values()->map(function ($resep) {
                return [
                    'obat_id' => $resep['obat_id'],
                    'obat_nama' => $resep['obat_nama'],
                    'racikan_ke' => $resep['racikan_ke'],
                    'dosis' => $resep['dosis'],
                    'aturan_pakai' => $resep['aturan_pakai'],
                    'paket_racikan_name' => $resep['paket_racikan_name'],
                ];
            });

            return response()->json($result->all());
        } catch (\Exception $e) {
            Log::error('Error getting visitation obat: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Print Etiket Biru PDF
     */
    public function printEtiketBiru(Request $request)
    {
        try {
            // Accept either a single obat_id OR a racikan_ke (group) selection
            $validated = $request->validate([
                'visitation_id' => 'required|string',
                'obat_id' => 'nullable|integer',
                'racikan_ke' => 'nullable|integer',
                'label_name' => 'nullable|string|max:191',
                'expire_date' => 'required|date',
                'pagi' => 'nullable|in:0,1',
                'siang' => 'nullable|in:0,1',
                'sore' => 'nullable|in:0,1',
                'malam' => 'nullable|in:0,1',
            ]);

            // Ensure at least one of obat_id or racikan_ke is provided
            if (empty($validated['obat_id']) && empty($validated['racikan_ke'])) {
                return response()->json(['success' => false, 'message' => 'The obat id or racikan_ke field is required.'], 422);
            }

            $visitation = Visitation::with(['pasien', 'dokter.user'])->findOrFail($validated['visitation_id']);

            $obat = null;
            $resepFarmasi = null;
            // If racikan_ke is provided, fetch the first item of that racikan to use as context,
            // and create a synthetic obat name like "Racikan 1" to show on the label.
            if (!empty($validated['racikan_ke'])) {
                $racikanKe = $validated['racikan_ke'];
                // Get all items in the racikan group (may be used by the view if needed)
                $resepGroup = ResepFarmasi::where('visitation_id', $validated['visitation_id'])
                    ->where('racikan_ke', $racikanKe)
                    ->with('obat')
                    ->get();

                // Use a synthetic obat object for naming purposes
                $obat = (object) ['nama' => 'Racikan ' . $racikanKe];
                // Provide first resep item for dosis/aturan lookup if needed
                $resepFarmasi = $resepGroup->first();
            } else {
                // Single obat selected
                $obat = Obat::findOrFail($validated['obat_id']);
                // Get the resep details for this obat in this visitation
                $resepFarmasi = ResepFarmasi::where('visitation_id', $validated['visitation_id'])
                    ->where('obat_id', $validated['obat_id'])
                    ->first();
            }

            $data = [
                'pasien' => $visitation->pasien,
                'obat' => $obat,
                'expire_date' => Carbon::parse($validated['expire_date']),
                'resep' => $resepFarmasi,
                'visitation' => $visitation,
                'print_date' => now()->format('d/m/Y')
            ];
            // If a custom label was provided, pass it to the view
            $data['label_name'] = $validated['label_name'] ?? null;
            // Include checkbox flags (convert to boolean)
            $data['pagi'] = isset($validated['pagi']) && $validated['pagi'] == 1;
            $data['siang'] = isset($validated['siang']) && $validated['siang'] == 1;
            $data['sore'] = isset($validated['sore']) && $validated['sore'] == 1;
            $data['malam'] = isset($validated['malam']) && $validated['malam'] == 1;

            $html = view('erm.eresep.farmasi.etiket-biru-print', $data)->render();

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);
            $mpdf->SetAutoPageBreak(false);
            $mpdf->WriteHTML($html);
            // Output inline PDF
            // Ensure filename is safe and readable even for racikan groups
            // Prefer explicit label_name if provided, otherwise use obat->nama
            $filenameLabel = $data['label_name'] ?? ($obat->nama ?? 'etiket');
            $labelName = preg_replace('/[^A-Za-z0-9\-\_ ]/', '', $filenameLabel);
            return response($mpdf->Output('etiket-biru-' . $visitation->pasien->nama . '-' . $labelName . '.pdf', 'I'))
                ->header('Content-Type', 'application/pdf');
            
        } catch (\Exception $e) {
            Log::error('Error printing Etiket Biru: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencetak Etiket Biru: ' . $e->getMessage()
            ], 500);
        }
    }
}
