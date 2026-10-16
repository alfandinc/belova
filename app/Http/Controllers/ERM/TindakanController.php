<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Tindakan;
use App\Models\ERM\RiwayatTindakan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\InformConsent;
use App\Models\ERM\Spk;
use App\Models\ERM\AsesmenPenunjang;
use Carbon\Carbon;
use App\Models\ERM\MultiVisitUsage;


class TindakanController extends Controller
    /**
     * Update obat for a riwayat tindakan (substitution from modal)
     */

{
        public function updateRiwayatObat(Request $request, $id)
    {
        $riwayat = \App\Models\ERM\RiwayatTindakan::findOrFail($id);
        $obats = $request->input('obats', []); // [kode_tindakan_id => [obat_id, ...]]
        $qty = $request->input('qty', []); // [kode_tindakan_id][obat_id] => value
        $dosis = $request->input('dosis', []); // [kode_tindakan_id][obat_id] => value
        $satuanDosis = $request->input('satuan_dosis', []); // [kode_tindakan_id][obat_id] => value

        // Helper: normalize decimal string (accept comma or dot)
        $normalizeDecimal = function($v) {
            if ($v === null || $v === '') return null;
            // keep only digits, comma and dot
            $s = preg_replace('/[^0-9,\.]/', '', (string) $v);
            // convert comma to dot and collapse multiple dots
            $s = str_replace(',', '.', $s);
            // If still not a valid numeric, return null
            return is_numeric($s) ? $s : null;
        };

        // Replace all obat of this riwayat in one transaction, so a failure never leaves it half-deleted
        DB::transaction(function () use ($id, $obats, $qty, $dosis, $satuanDosis, $normalizeDecimal) {
            DB::table('erm_riwayat_tindakan_obat')->where('riwayat_tindakan_id', $id)->delete();
            // Insert new pivot entries with qty, dosis, satuan_dosis
            foreach ($obats as $kodeTindakanId => $obatIds) {
                if (!is_array($obatIds)) continue;
                foreach ($obatIds as $obatId) {
                    $rawDosis = isset($dosis[$kodeTindakanId][$obatId]) ? $dosis[$kodeTindakanId][$obatId] : null;
                    $normDosis = $normalizeDecimal($rawDosis);
                    DB::table('erm_riwayat_tindakan_obat')->insert([
                        'riwayat_tindakan_id' => $id,
                        'kode_tindakan_id' => $kodeTindakanId,
                        'obat_id' => $obatId,
                        'qty' => isset($qty[$kodeTindakanId][$obatId]) ? $qty[$kodeTindakanId][$obatId] : 1,
                        'dosis' => $normDosis,
                        'satuan_dosis' => isset($satuanDosis[$kodeTindakanId][$obatId]) ? $satuanDosis[$kodeTindakanId][$obatId] : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Obat berhasil disimpan']);
    }
    /**
     * Show detail for riwayat tindakan (kode tindakan & obat list, substitution UI)
     */
    public function getRiwayatDetail($id)
    {
        $riwayatTindakan = \App\Models\ERM\RiwayatTindakan::with(['tindakan.kodeTindakans.obats', 'tindakan', 'tindakan.kodeTindakans'])->find($id);
        if (!$riwayatTindakan) {
            $riwayatTindakan = new \stdClass();
            $riwayatTindakan->kodeTindakans = [];
        }
        $pivotObats = DB::table('erm_riwayat_tindakan_obat')
            ->where('riwayat_tindakan_id', $id)
            ->get();

        $riwayatObat = [];
        if ($pivotObats) {
            foreach ($pivotObats as $pivot) {
                $riwayatObat[$pivot->kode_tindakan_id][] = $pivot->obat_id;
            }
        }

        $html = view('erm.partials.riwayat-detail', [
            'riwayatTindakan' => $riwayatTindakan,
            'riwayatObat' => $riwayatObat ?: []
        ])->render();
        return response()->json(['html' => $html]);
    }
    /**
     * Get allow_post value for InformConsent (AJAX).
     */
    public function getInformConsentAllowPost($id)
    {
        $informConsent = InformConsent::find($id);
        if (!$informConsent) {
            return response()->json(['allow_post' => false]);
        }
        return response()->json(['allow_post' => (bool)$informConsent->allow_post]);
    }
    public function create($visitationId)
    {
        // Kosongkan dulu, tidak ada data dummy
        $visitation = Visitation::with('dokter')->findOrFail($visitationId);
        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);
        $spesialisasiId = $visitation->resolvedSpesialisasiId();

        if (!$spesialisasiId) {
            $spesialisasiId = \App\Models\ERM\Spesialisasi::where('nama', 'Umum')->value('id');
        }


        // dd($spesialisasiId);
        return view('erm.tindakan.create', array_merge([
            'visitation' => $visitation,
            'spesialisasiId' => $spesialisasiId,
        ], $pasienData, $createKunjunganData));
    }

    public function getTindakanData(Request $request, $spesialisasiId)
    {
        // Include tindakan with spesialis 'Umum' for all spesialisasi and
        // prioritize rows that exactly match the current dokter's spesialisasi.
        $umum = \App\Models\ERM\Spesialisasi::where('nama', 'Umum')->first();
        $umumId = $umum ? $umum->id : null;

        $tindakanQuery = Tindakan::query();
        // Only include tindakan that are marked active
        $tindakanQuery->where('is_active', true);

        if ($umumId) {
            $tindakanQuery->where(function($q) use ($spesialisasiId, $umumId) {
                $q->where('spesialis_id', $spesialisasiId)
                  ->orWhere('spesialis_id', $umumId);
            });

            // Ensure current spesialis rows appear first, then 'Umum', then others
            $tindakanQuery->orderByRaw("CASE WHEN spesialis_id = ? THEN 0 WHEN spesialis_id = ? THEN 1 ELSE 2 END", [$spesialisasiId, $umumId]);
        } else {
            // If 'Umum' doesn't exist, fall back to just the spesialisasi
            $tindakanQuery->where('spesialis_id', $spesialisasiId)
                ->orderByRaw("CASE WHEN spesialis_id = ? THEN 0 ELSE 1 END", [$spesialisasiId]);
        }

        $tindakan = $tindakanQuery->get();

        // Remaining multi visit package per tindakan for this patient (same rule as getMultiVisitStatus)
        $pasienId = $request->filled('visitation_id') ? Visitation::whereKey($request->query('visitation_id'))->value('pasien_id') : null;
        $openUsages = $pasienId
            ? MultiVisitUsage::where('pasien_id', $pasienId)->whereColumn('used', '<', 'total')
                ->orderByDesc('created_at')->get(['tindakan_id', 'used', 'total'])->unique('tindakan_id')->keyBy('tindakan_id')
            : collect();

        return datatables()->of($tindakan)
            ->addColumn('multi_used', fn ($row) => isset($openUsages[$row->id]) ? (int) $openUsages[$row->id]->used : null)
            ->addColumn('multi_total', fn ($row) => isset($openUsages[$row->id]) ? (int) $openUsages[$row->id]->total : null)
            ->addColumn('action', function ($row) {
                return '
                <button class="btn btn-success btn-sm buat-tindakan" data-id="' . $row->id . '" data-type="tindakan">Buat Tindakan</button>
            ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // storeCustomTindakan removed: custom tindakan creation via modal has been deleted

    public function informConsent($id)
    {
        $tindakan = Tindakan::findOrFail($id);
        $visitation = request()->query('visitation_id');
        $visitation = Visitation::findOrFail($visitation);
        $pasien = $visitation->pasien;

        $viewName = strtolower(str_replace(' ', '_', $tindakan->nama));
        if (View::exists("erm.tindakan.inform-consent.{$viewName}")) {
            return view("erm.tindakan.inform-consent.{$viewName}", compact('tindakan', 'pasien', 'visitation'));
        }
        return view('erm.tindakan.inform-consent.default', compact('tindakan', 'pasien', 'visitation'));
    }

    /**
     * Return harga data for a tindakan (AJAX)
     */
    public function getPrices($id)
    {
        $tindakan = Tindakan::find($id);
        if (!$tindakan) return response()->json(['success' => false], 404);
        return response()->json([
            'success' => true,
            'harga' => $tindakan->harga,
            'harga_diskon' => $tindakan->harga_diskon,
            'diskon_active' => (bool)$tindakan->diskon_active,
            'harga_paket_visit' => $tindakan->harga_paket_visit,
            'multi_visit_total' => $tindakan->multi_visit_total
        ]);
    }

    /**
     * Return current multi-visit usage status for a tindakan and pasien (via visitation_id or pasien_id).
     */
    public function getMultiVisitStatus(Request $request, $id)
    {
        $pasienId = null;
        if ($request->has('visitation_id')) {
            $vis = Visitation::find($request->query('visitation_id'));
            if ($vis) $pasienId = $vis->pasien_id;
        }
        if (!$pasienId && $request->has('pasien_id')) {
            $pasienId = $request->query('pasien_id');
        }

        if (!$pasienId) {
            return response()->json(['success' => false, 'message' => 'pasien_id or visitation_id required'], 400);
        }

        $usage = MultiVisitUsage::where('pasien_id', $pasienId)
            ->where('tindakan_id', $id)
            ->whereColumn('used', '<', 'total')
            ->orderByDesc('created_at')
            ->first();

        if ($usage) {
            return response()->json(['success' => true, 'used' => (int)$usage->used, 'total' => (int)$usage->total]);
        }

        return response()->json(['success' => true, 'used' => null, 'total' => null]);
    }

    /**
     * Check whether a tindakan already exists for a visitation (AJAX)
     */
    public function existsInVisitation(Request $request, $id)
    {
        $visitationId = $request->query('visitation_id');
        if (!$visitationId) {
            return response()->json(['success' => false, 'message' => 'visitation_id required'], 400);
        }

        $exists = RiwayatTindakan::where('visitation_id', $visitationId)
            ->where('tindakan_id', $id)
            ->exists();

        return response()->json(['success' => true, 'exists' => (bool) $exists]);
    }

    /**
     * Save a tindakan (riwayat, inform consent PDF, SPK, billing, obat) all or nothing: a failure never leaves
     * a riwayat without its billing, nor a PDF without its record.
     */
    public function saveInformConsent(Request $request)
    {
        $storedPdf = null;
        DB::beginTransaction();
        try {
            $response = $this->storeInformConsent($request, $storedPdf);
            DB::commit();

            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($storedPdf) {
                Storage::disk('public')->delete($storedPdf);
            }
            throw $e;
        }
    }

    /**
     * Inform consent PDF of a riwayat tindakan from the signed form ($data: visitation_id, tindakan_id, tanggal,
     * signature, witness_signature, notes, nama_pasien, nama_saksi). $storedPdf gets the file so a failed save can remove it.
     */
    private function createInformConsentDocument(Request $request, array $data, Visitation $visitation, Tindakan $tindakan, RiwayatTindakan $riwayatTindakan, ?string &$storedPdf): InformConsent
    {
        $pasien = $visitation->pasien;
        $viewName = strtolower(str_replace(' ', '_', $tindakan->nama));
        $bladeViewCandidate = "erm.tindakan.inform-consent.{$viewName}";
        // Fallback to default view when tindakan-specific template doesn't exist
        $bladeView = View::exists($bladeViewCandidate) ? $bladeViewCandidate : 'erm.tindakan.inform-consent.default';
        $consentText = '';
        // Render the Blade view to HTML
        $html = View::make($bladeView, [
            'tindakan' => $tindakan,
            'pasien' => $pasien,
            'visitation' => $visitation,
            'data' => $data,
        ])->render();
            // Parse the HTML to extract .card-body before Catatan Tambahan
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            libxml_clear_errors();
            $finder = new \DOMXPath($dom);
            $cardBodies = $finder->query("//div[contains(@class, 'card-body')]");
            if ($cardBodies->length > 0) {
                // Find the card-body that contains the consent text (the one with Catatan Tambahan inside)
                /** @var \DOMElement $cardBody */
                foreach ($cardBodies as $cardBody) {
                    $hasCatatan = false;
                    foreach ($cardBody->getElementsByTagName('label') as $label) {
                        if (stripos($label->nodeValue, 'Catatan Tambahan') !== false) {
                            $hasCatatan = true;
                            break;
                        }
                    }
                    if ($hasCatatan) {
                        // Only get content before Catatan Tambahan
                        $content = '';
                        foreach ($cardBody->childNodes as $child) {
                            // Stop at the Catatan Tambahan label
                            if ($child instanceof \DOMElement && $child->nodeName === 'div') {
                                $label = $child->getElementsByTagName('label')->item(0);
                                if ($label && stripos($label->nodeValue, 'Catatan Tambahan') !== false) {
                                    break;
                                }
                            }
                            $content .= $dom->saveHTML($child);
                        }
                        $consentText = $content;
                        break;
                    }
                }
            }
        // Prepare dokter and perawat (current user) names for QR
        $dokterName = '';
        if ($visitation->dokter) {
            $dokterUser = $visitation->dokter->user ?? null;
            $dokterName = $dokterUser ? ($dokterUser->name ?? '') : ($visitation->dokter->nama ?? '');
        }
        $perawatName = optional(Auth::user())->name ?? '';

        // QR data URI generated locally (PNG, or SVG when PNG needs an extension the server lacks)
        $generateQrDataUri = function ($text) {
            if (empty($text)) {
                return '';
            }
            try {
                return 'data:image/png;base64,' . base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(200)->generate($text));
            } catch (\Throwable $e) {
                try {
                    return 'data:image/svg+xml;base64,' . base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)->generate($text));
                } catch (\Throwable $e) {
                    return '';
                }
            }
        };

        // Prefer client-submitted QR data URIs (from browser); an empty one (QR library not loaded) falls back
        $dokterQr = $request->input('dokter_qr') ?: $generateQrDataUri('Dokter: ' . $dokterName);
        $perawatQr = $request->input('perawat_qr') ?: $generateQrDataUri('Perawat: ' . $perawatName);

        // Generate PDF using the generic template
        $pdf = PDF::loadView("erm.tindakan.inform-consent.pdf.generic", [
            'data' => $data,
            'pasien' => $pasien,
            'visitation' => $visitation,
            'tindakan' => $tindakan,
            'klinik_id' => $visitation->klinik_id, // pass klinik_id for logo
            'content' => $consentText,
            'dokter_qr' => $dokterQr,
            'perawat_qr' => $perawatQr,
            'dokter_name' => $dokterName,
            'perawat_name' => $perawatName,
        ]);
        $timestamp = now()->format('YmdHis');
        $pdfPath = 'inform-consent/' . $pasien->id . '-' . $visitation->id . '-' . $tindakan->id . '-' . $timestamp . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());
        $storedPdf = $pdfPath;
        $informConsent = InformConsent::create([
            'visitation_id' => $data['visitation_id'],
            'tindakan_id' => $data['tindakan_id'],
            'file_path' => $pdfPath,
            'riwayat_tindakan_id' => $riwayatTindakan->id,
            'created_at' => now(),
        ]);

        return $informConsent;
    }

    /** The consent form posts signatureData / witnessSignatureData; the controller reads signature / witness_signature. */
    private function mapSignatureFields(Request $request): void
    {
        if ($request->has('signatureData') && !$request->has('signature')) {
            $request->merge(['signature' => $request->signatureData]);
        }
        if ($request->has('witnessSignatureData') && !$request->has('witness_signature')) {
            $request->merge(['witness_signature' => $request->witnessSignatureData]);
        }
    }

    /**
     * Inform consent filled after the tindakan was added: only the consent PDF for that riwayat,
     * no new riwayat / billing.
     */
    public function saveRiwayatInformConsent(Request $request, $id)
    {
        $riwayat = RiwayatTindakan::findOrFail($id);
        if (InformConsent::where('riwayat_tindakan_id', $riwayat->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Inform consent tindakan ini sudah diisi.'], 422);
        }

        $this->mapSignatureFields($request);
        $data = $request->validate([
            'signature' => 'required',
            'witness_signature' => 'required',
            'notes' => 'nullable|string',
            'nama_pasien' => 'nullable|string',
            'nama_saksi' => 'nullable|string',
        ], [
            'signature.required' => 'Tanda tangan pasien wajib diisi.',
            'witness_signature.required' => 'Tanda tangan saksi wajib diisi.',
        ]);
        $data += [
            'visitation_id' => $riwayat->visitation_id,
            'tindakan_id' => $riwayat->tindakan_id,
            'tanggal' => Carbon::parse($riwayat->tanggal_tindakan ?? now())->format('Y-m-d'),
        ];

        $visitation = Visitation::findOrFail($riwayat->visitation_id);
        $tindakan = Tindakan::findOrFail($riwayat->tindakan_id);

        $storedPdf = null;
        DB::beginTransaction();
        try {
            $informConsent = $this->createInformConsentDocument($request, $data, $visitation, $tindakan, $riwayat, $storedPdf);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($storedPdf) {
                Storage::disk('public')->delete($storedPdf);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Inform consent berhasil disimpan.',
            'informConsent' => $informConsent,
        ]);
    }

    private function storeInformConsent(Request $request, ?string &$storedPdf)
    {
        $this->mapSignatureFields($request);

        // Make signature fields optional
        $rules = [
            'visitation_id' => 'required|exists:erm_visitations,id',
            'tindakan_id' => 'required|exists:erm_tindakan,id',
            'tanggal' => 'required|date',
            'signature' => 'nullable',
            'witness_signature' => 'nullable',
            'notes' => 'nullable|string',
            'nama_pasien' => 'nullable|string',
            'nama_saksi' => 'nullable|string',
            'reuse_existing_inform_consent' => 'nullable|boolean',
        ];
        $data = $request->validate($rules);

        $visitation = Visitation::findOrFail($data['visitation_id']);
        $pasien = $visitation->pasien;
        $tindakan = Tindakan::with('kodeTindakans.kategoris.icd10s')->findOrFail($data['tindakan_id']);

        // Always create RiwayatTindakan (we may attach multi-visit usage after)
        $riwayatTindakan = RiwayatTindakan::create([
            'visitation_id' => $data['visitation_id'],
            'tanggal_tindakan' => $data['tanggal'],
            'tindakan_id' => $data['tindakan_id'],
            // Snapshot slimming RF/inject quota from master tindakan
            'total_rf' => $tindakan->is_slimming ? $tindakan->total_rf : null,
            'total_inject' => $tindakan->is_slimming ? $tindakan->total_inject : null,
        ]);

        $informConsent = null;
        // Only create InformConsent and PDF if signatures are present
        if (!empty($data['signature']) && !empty($data['witness_signature'])) {
            $informConsent = $this->createInformConsentDocument($request, $data, $visitation, $tindakan, $riwayatTindakan, $storedPdf);
        } elseif ($request->boolean('reuse_existing_inform_consent')) {
            $existingInformConsent = InformConsent::where('visitation_id', $data['visitation_id'])
                ->where('tindakan_id', $data['tindakan_id'])
                ->whereNotNull('file_path')
                ->where('riwayat_tindakan_id', '!=', $riwayatTindakan->id)
                ->latest('id')
                ->first();

            if ($existingInformConsent) {
                $informConsent = InformConsent::create([
                    'visitation_id' => $data['visitation_id'],
                    'tindakan_id' => $data['tindakan_id'],
                    'file_path' => $existingInformConsent->file_path,
                    'before_image_path' => $existingInformConsent->before_image_path,
                    'after_image_path' => $existingInformConsent->after_image_path,
                    'riwayat_tindakan_id' => $riwayatTindakan->id,
                    'allow_post' => $existingInformConsent->allow_post,
                    'created_at' => now(),
                ]);
            }
        }

        // Automatically create SPK record
        $spk = \App\Models\ERM\Spk::create([
            'visitation_id' => $data['visitation_id'],
            'pasien_id' => $visitation->pasien_id,
            'tindakan_id' => $data['tindakan_id'],
            'dokter_id' => $visitation->dokter_id,
            'tanggal_tindakan' => $data['tanggal'],
            'riwayat_tindakan_id' => $riwayatTindakan->id,
        ]);

        $billing = null;
        // Determine harga type: 'normal' or '3x'
        $hargaType = $request->input('harga_type') ?? 'normal';

        // Handle normal vs 3x visit pricing & multi-visit usage
        $shouldCreateBilling = true;
        $billingAmount = $tindakan->harga;
        $multiVisitTotal = max((int) ($tindakan->multi_visit_total ?: 3), 2);
        $multiVisitLabel = $multiVisitTotal . 'x Visit';

        if ($hargaType === '3x' && !empty($tindakan->harga_paket_visit)) {
            // Check for existing unused multi-visit usage for this patient & tindakan
            $existingUsage = MultiVisitUsage::where('pasien_id', $visitation->pasien_id)
                ->where('tindakan_id', $tindakan->id)
                ->whereColumn('used', '<', 'total')
                ->first();

            if ($existingUsage) {
                // This patient already has remaining sessions: consume one
                $existingUsage->used = $existingUsage->used + 1;
                $existingUsage->save();
                $riwayatTindakan->multi_visit_usage_id = $existingUsage->id;
                $riwayatTindakan->save();
                // For subsequent visits (2/3 and 3/3) we still create a billing record,
                // but with amount 0 so it appears in billing history without charging.
                $billingAmount = 0;
                $shouldCreateBilling = true;
            } else {
                // create new multi-visit usage record using tindakan-configured total
                $newUsage = MultiVisitUsage::create([
                    'pasien_id' => $visitation->pasien_id,
                    'tindakan_id' => $tindakan->id,
                    'first_visitation_id' => $data['visitation_id'],
                    'total' => $multiVisitTotal,
                    'used' => 1,
                ]);
                $riwayatTindakan->multi_visit_usage_id = $newUsage->id;
                $riwayatTindakan->save();
                // charge the multi-visit package price on first use
                $billingAmount = $tindakan->harga_paket_visit;
                $shouldCreateBilling = true;
            }
        }

        if ($shouldCreateBilling) {
            // Build a contextual keterangan. If this is a multi-visit flow, include progress (e.g. 2/3)
            $billingKeterangan = 'Tindakan: ' . $tindakan->nama;
            if ($hargaType === '3x') {
                if (isset($existingUsage) && $existingUsage) {
                    $billingKeterangan .= ' (' . ($existingUsage->used ?? 0) . '/' . ($existingUsage->total ?? 3) . ') - No Charge';
                } elseif (isset($newUsage) && $newUsage) {
                    $billingKeterangan .= ' (1/' . ($newUsage->total ?? $multiVisitTotal) . ') - ' . $multiVisitLabel . ' Charge';
                }
            }

            // Calculate discount amount based on harga type. For 3x visits we do not apply the
            // normal tindakan discount (unless you want a separate 3x discount field).
            $discountAmount = 0;
            $billingDiscountType = null;

            $activePromoDiscountAmount = 0.0;
            if ($hargaType !== '3x' && floatval($billingAmount) > 0 && floatval($tindakan->harga ?? 0) > 0) {
                try {
                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                    $activePromoItems = \App\Models\Marketing\PromoItem::where('item_type', 'tindakan')
                        ->where('item_id', $tindakan->id)
                        ->whereHas('promo', function ($q) use ($today) {
                            $q->where(function ($q2) use ($today) {
                                $q2->whereNotNull('start_date')->whereNotNull('end_date')
                                    ->where('start_date', '<=', $today)
                                    ->where('end_date', '>=', $today);
                            })->orWhere(function ($q2) use ($today) {
                                $q2->whereNotNull('start_date')->whereNull('end_date')
                                    ->where('start_date', '<=', $today);
                            })->orWhere(function ($q2) use ($today) {
                                $q2->whereNull('start_date')->whereNotNull('end_date')
                                    ->where('end_date', '>=', $today);
                            });
                        })
                        ->get(['discount_type', 'discount_value', 'discount_percent']);

                    foreach ($activePromoItems as $promoItem) {
                        $discountAmount = $promoItem->calculateDiscountAmount((float) $billingAmount);
                        if ($discountAmount > $activePromoDiscountAmount) {
                            $activePromoDiscountAmount = $discountAmount;
                        }
                    }
                } catch (\Exception $e) {
                    $activePromoDiscountAmount = 0.0;
                }
            }

            if ($activePromoDiscountAmount > 0) {
                // Promo replaces tindakan harga_diskon when both exist.
                $discountAmount = round($activePromoDiscountAmount, 2);
                $billingDiscountType = $discountAmount > 0 ? 'nominal' : null;
            } elseif ($tindakan->diskon_active) {
                if ($hargaType === '3x') {
                    // No automatic discount applied to 3x-visit pricing by default
                    $discountAmount = 0;
                } else {
                    $discountAmount = ($tindakan->harga - $tindakan->harga_diskon);
                    $billingDiscountType = $discountAmount > 0 ? ($tindakan->diskon_type ?? 'nominal') : null;
                }
            }

            $billingData = [
                'visitation_id' => $data['visitation_id'],
                'billable_id' => $riwayatTindakan->id,
                'billable_type' => 'App\\Models\\ERM\\RiwayatTindakan',
                'jumlah' => $billingAmount,
                'diskon' => $discountAmount,
                'diskon_type' => $billingDiscountType,
                'keterangan' => $billingKeterangan
            ];
            if (!empty($data['jumlah'])) {
                $billingData['jumlah'] = $data['jumlah'];
            }
            if (!empty($data['keterangan'])) {
                $billingData['keterangan'] = $data['keterangan'];
            }
            $billing = Billing::create($billingData);
        } else {
            $billing = null; // intentionally no billing for consumed session
        }
        // Copy obat from kode tindakan to riwayat tindakan obat pivot table
        $kodeTindakans = $tindakan->kodeTindakans;
        foreach ($kodeTindakans as $kodeTindakan) {
            foreach ($kodeTindakan->obats as $obat) {
                DB::table('erm_riwayat_tindakan_obat')->insert([
                    'riwayat_tindakan_id' => $riwayatTindakan->id,
                    'kode_tindakan_id' => $kodeTindakan->id,
                    'obat_id' => $obat->id,
                    'qty' => $obat->pivot->qty ?? 1,
                    'dosis' => $obat->pivot->dosis ?? null,
                    'satuan_dosis' => $obat->pivot->satuan_dosis ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Obat of a paket come from its tindakan (copied to erm_riwayat_tindakan_obat above); there is no
        // separate per-paket obat bundle anymore
        $obatBillings = [];

        $this->syncKategoriIcd10ToAsesmenPenunjang($visitation->id, $tindakan);

        if ($request->filled('return_url') && !$request->expectsJson()) {
            return redirect($request->input('return_url'))
                ->with('success', 'Tindakan slimming berhasil ditambahkan.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Riwayat tindakan dan billing berhasil disimpan. Inform consent akan dibuat jika tersedia.',
            'informConsent' => $informConsent,
            'spk' => $spk,
            'billing' => $billing,
            'obatBillings' => $obatBillings,
            'riwayatTindakan' => $riwayatTindakan
        ]);
    }

    private function syncKategoriIcd10ToAsesmenPenunjang($visitationId, Tindakan $tindakan): void
    {
        $diagnosisCandidates = [];

        foreach ($tindakan->kodeTindakans as $kodeTindakan) {
            foreach ($kodeTindakan->kategoris as $kategori) {
                foreach ($kategori->icd10s as $icd10) {
                    $label = $this->formatIcd10DiagnosaKerja($icd10);
                    if ($label !== null) {
                        $diagnosisCandidates[$label] = $label;
                    }
                }
            }
        }

        if (empty($diagnosisCandidates)) {
            return;
        }

        $asesmen = AsesmenPenunjang::firstOrCreate(['visitation_id' => $visitationId]);
        $fields = ['diagnosakerja_1', 'diagnosakerja_2', 'diagnosakerja_3', 'diagnosakerja_4', 'diagnosakerja_5', 'diagnosakerja_6'];

        $existingValues = [];
        foreach ($fields as $field) {
            $value = trim((string) ($asesmen->{$field} ?? ''));
            if ($value !== '') {
                $existingValues[mb_strtolower($value)] = true;
            }
        }

        foreach ($diagnosisCandidates as $label) {
            if (isset($existingValues[mb_strtolower($label)])) {
                continue;
            }

            foreach ($fields as $field) {
                $currentValue = trim((string) ($asesmen->{$field} ?? ''));
                if ($currentValue === '') {
                    $asesmen->{$field} = $label;
                    $existingValues[mb_strtolower($label)] = true;
                    break;
                }
            }
        }

        if ($asesmen->isDirty($fields)) {
            $asesmen->save();
        }
    }

    private function formatIcd10DiagnosaKerja($icd10): ?string
    {
        $code = trim((string) ($icd10->code ?? ''));
        $description = trim((string) ($icd10->description ?? ''));

        if ($code !== '' && $description !== '') {
            return $code . ' - ' . $description;
        }

        if ($code !== '') {
            return $code;
        }

        return $description !== '' ? $description : null;
    }

    public function getRiwayatTindakanHistory($visitationId)
    {
        // First, get the patient ID from the current visitation
        $currentVisitation = Visitation::findOrFail($visitationId);
        $patientId = $currentVisitation->pasien_id;

        // Get all visitations for this patient
        $patientVisitations = Visitation::where('pasien_id', $patientId)
            ->pluck('id')->toArray();

        // Get all riwayat tindakan for all visitations of this patient and collapse
        // duplicate tindakan within the same visit into one row with a count badge.
        $history = RiwayatTindakan::whereIn('visitation_id', $patientVisitations)
            ->with(['tindakan', 'visitation.dokter.user', 'visitation.dokter.spesialisasi', 'visitation.spesialisasi', 'informConsent'])
            ->get()
            ->groupBy(function ($item) {
                return $item->visitation_id . '-' . ($item->tindakan_id ?? 0);
            })
            ->map(function ($group) use ($visitationId) {
                $representative = $group->sortByDesc('id')->first();
                $informConsentSource = $group->sortByDesc('id')->first(function ($candidate) {
                    return !empty($candidate->informConsent);
                }) ?: $representative;

                $tanggalRaw = $representative->visitation->tanggal_visitation ?? null;
                $tanggalFormatted = '-';
                if ($tanggalRaw) {
                    $tanggalFormatted = Carbon::parse($tanggalRaw)
                        ->locale('id')
                        ->isoFormat('D MMMM YYYY');
                }

                return (object) [
                    'id' => $representative->id,
                    'visitation_id' => $representative->visitation_id,
                    'tindakan_id' => $representative->tindakan_id,
                    'tanggal' => $tanggalFormatted,
                    'tanggal_raw' => $tanggalRaw,
                    'tindakan' => $representative->tindakan->nama ?? '-',
                    'dokter' => $representative->visitation->dokter->user->name ?? '-',
                    'spesialisasi' => $representative->visitation->listSpesialisasiNama() ?? '-',
                    'inform_consent' => $informConsentSource->informConsent,
                    'duplicate_count' => $group->count(),
                    // A riwayat of this row still without its inform consent (added without filling it)
                    'pending_consent_id' => optional($group->sortByDesc('id')->first(function ($candidate) {
                        return empty($candidate->informConsent);
                    }))->id,
                    'current' => ($representative->visitation_id == $visitationId)
                ];
            })
            ->sortByDesc(function ($item) {
                // Sort by raw visitation date (string, so convert to timestamp)
                return $item->tanggal_raw ? strtotime($item->tanggal_raw) : 0;
            })
            ->values();

        return datatables()->of($history)
            // Tindakan of this visit still without inform consent (warning banner)
            ->with('pending_consent', RiwayatTindakan::where('visitation_id', $visitationId)->whereDoesntHave('informConsent')->count())
            ->addColumn('dokumen', function ($row) {
                $buttons = '';
                
                // Add document button if inform consent exists
                if ($row->inform_consent) {
                    $url = Storage::url($row->inform_consent->file_path);
                    $buttons .= '<a href="' . $url . '" target="_blank" class="btn btn-info btn-sm mr-1">Inform Consent</a>';
                    
                    // Always add foto hasil button
                    $hasBefore = isset($row->inform_consent->before_image_path) && trim($row->inform_consent->before_image_path) !== '';
                    $hasAfter = isset($row->inform_consent->after_image_path) && trim($row->inform_consent->after_image_path) !== '';
                    $fotoBtnText = ($hasBefore && $hasAfter) ? 'Lihat Foto' : 'Upload Foto';
                    $buttons .= '<button class="btn btn-primary btn-sm foto-hasil-btn mr-1" ' .
                        'data-id="' . $row->inform_consent->id . '" ' .
                        'data-before="' . ($row->inform_consent->before_image_path ?? '') . '" ' .
                        'data-after="' . ($row->inform_consent->after_image_path ?? '') . '">' .
                        $fotoBtnText . '</button>';
                    
                    // Add SPK button
                    $buttons .= '<button class="btn btn-warning btn-sm spk-btn" ' .
                        'data-id="' . $row->inform_consent->id . '">' .
                        'SPK</button>';
                } else {
                    $buttons = '<span class="text-muted">Belum ada inform consent</span>';
                }
                
                return $buttons;
            })
            ->addColumn('status', function ($row) {
                return $row->current ?
                    '<span class="badge badge-success">Kunjungan Saat Ini</span>' :
                    '<span class="badge badge-secondary">Kunjungan Sebelumnya</span>';
            })
            ->addColumn('spk_status_color', function($row) {
                // Check SPK detail status for this riwayat
                $spk = \App\Models\ERM\Spk::where('riwayat_tindakan_id', $row->id)->first();
                if ($spk) {
                    $hasSelesai = !empty($spk->waktu_selesai);
                    if ($hasSelesai) return 'green';
                    $hasMulai = !empty($spk->waktu_mulai);
                    if ($hasMulai) return 'yellow';
                }
                return '';
            })
            ->setRowClass(function($row) {
                $spk = \App\Models\ERM\Spk::where('riwayat_tindakan_id', $row->id)->first();
                if ($spk) {
                    $hasSelesai = !empty($spk->waktu_selesai);
                    if ($hasSelesai) return 'table-success';
                    $hasMulai = !empty($spk->waktu_mulai);
                    if ($hasMulai) return 'table-warning';
                }
                return '';
            })
            ->rawColumns(['dokumen', 'status', 'aksi'])
            ->make(true);
    }


    public function uploadFoto(Request $request, $id)
    {
        $request->validate([
            'before_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'after_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $informConsent = InformConsent::findOrFail($id);

        if ($request->hasFile('before_image')) {
            // Delete existing image if it exists
            if ($informConsent->before_image_path && Storage::disk('public')->exists($informConsent->before_image_path)) {
                Storage::disk('public')->delete($informConsent->before_image_path);
            }

            // Store new image
            $beforePath = $request->file('before_image')->store('tindakan-images', 'public');
            $informConsent->before_image_path = $beforePath;
        }

        if ($request->hasFile('after_image')) {
            // Delete existing image if it exists
            if ($informConsent->after_image_path && Storage::disk('public')->exists($informConsent->after_image_path)) {
                Storage::disk('public')->delete($informConsent->after_image_path);
            }

            // Store new image
            $afterPath = $request->file('after_image')->store('tindakan-images', 'public');
            $informConsent->after_image_path = $afterPath;
        }

        // Handle allow_post checkbox
        if ($request->has('allow_post')) {
            $informConsent->allow_post = $request->input('allow_post') ? true : false;
        } else {
            $informConsent->allow_post = false;
        }

        $informConsent->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto hasil berhasil diupload',
            'before_path' => $informConsent->before_image_path ? Storage::url($informConsent->before_image_path) : null,
            'after_path' => $informConsent->after_image_path ? Storage::url($informConsent->after_image_path) : null,
        ]);
    }

    // SPK: Show all riwayat tindakan (index)
    public function spkIndex(Request $request)
    {
        if ($request->ajax()) {
            // Get all riwayat tindakan and group them by visitation
            $riwayat = \App\Models\ERM\RiwayatTindakan::with(['visitation.pasien', 'tindakan', 'visitation.dokter.user']);

            // Filter by date range
            $tanggalStart = $request->input('tanggal_start');
            $tanggalEnd = $request->input('tanggal_end');
            if ($tanggalStart && $tanggalEnd) {
                $riwayat = $riwayat->whereBetween('tanggal_tindakan', [$tanggalStart, $tanggalEnd]);
            } elseif ($tanggalStart) {
                $riwayat = $riwayat->whereDate('tanggal_tindakan', $tanggalStart);
            }

            // Filter by dokter
            $dokterId = $request->input('dokter_id');
            if ($dokterId) {
                $riwayat = $riwayat->whereHas('visitation', function($q) use ($dokterId) {
                    $q->where('dokter_id', $dokterId);
                });
            }
            
            // Get results
            $results = $riwayat->get();
            
            // Group by visitation_id
            $groupedResults = $results->groupBy('visitation_id');
            
            // Prepare data for datatables
            $data = $groupedResults->map(function($group) {
                // Use first item for common data
                $first = $group->first();
                $riwayatIds = $group->pluck('id')->toArray();
                
                // Check if SPK data exists AND has meaningful data for any riwayat in this group
                $hasSpkData = \App\Models\ERM\Spk::whereIn('riwayat_tindakan_id', $riwayatIds)
                    ->where(fn ($q) => $q->whereNotNull('waktu_mulai')->orWhereNotNull('waktu_selesai'))
                    ->exists();
                
                return [
                    'id' => $first->id,
                    'visitation_id' => $first->visitation_id,
                    'tanggal_tindakan' => $first->tanggal_tindakan,
                    'visitation' => $first->visitation,
                    'has_spk_data' => $hasSpkData,
                    'all_tindakan' => $group->map(function($item) {
                        return [
                            'id' => $item->id,
                            'tindakan' => $item->tindakan,
                        ];
                    })->toArray()
                ];
            })->values();
            
            // Filter by status layanan
            $statusLayanan = $request->input('status_layanan');
            if ($statusLayanan) {
                $data = $data->filter(function($row) use ($statusLayanan) {
                    if ($statusLayanan === 'sudah_dilayani') {
                        return $row['has_spk_data'];
                    } elseif ($statusLayanan === 'belum_dilayani') {
                        return !$row['has_spk_data'];
                    }
                    return true;
                })->values();
            }
            
            return datatables()->of($data)
                ->addColumn('tanggal', function($row) {
                    // Format tanggal to '1 Januari 2025'
                    if ($row['tanggal_tindakan']) {
                        try {
                            return \Carbon\Carbon::parse($row['tanggal_tindakan'])
                                ->locale('id')
                                ->isoFormat('D MMMM YYYY');
                        } catch (\Exception $e) {
                            return $row['tanggal_tindakan'];
                        }
                    }
                    return '-';
                })
                ->addColumn('jam_kunjungan', function($row) {
                    // Use waktu from visitation (format as H:i if datetime)
                    if ($row['visitation'] && $row['visitation']->waktu_kunjungan) {
                        try {
                            return \Carbon\Carbon::parse($row['visitation']->waktu_kunjungan)->format('H:i');
                        } catch (\Exception $e) {
                            return $row['visitation']->waktu_kunjungan;
                        }
                    }
                    return '-';
                })
                ->addColumn('pasien', function($row) {
                    return $row['visitation']?->pasien?->nama ?? '-';
                })
                ->addColumn('tindakan', function($row) {
                    // Create a formatted list of tindakan
                    $tindakanList = collect($row['all_tindakan'])->map(function($item) {
                        return $item['tindakan']->nama ?? '-';
                    })->join('<br>');
                    
                    return $tindakanList ?: '-';
                })
                ->addColumn('dokter', function($row) {
                    return $row['visitation']?->dokter?->user?->name ?? '-';
                })
                ->addColumn('status_layanan', function($row) {
                    return $row['has_spk_data'] ? 'sudah_dilayani' : 'belum_dilayani';
                })
                ->addColumn('aksi', function($row) {
                    $firstRiwayatId = $row['all_tindakan'][0]['id'];
                    $visitationId = $row['visitation_id'];
                    
                    // Get SPK timestamps for the first riwayat (representative of the group)
                    $spk = \App\Models\ERM\Spk::where('riwayat_tindakan_id', $firstRiwayatId)->first();
                    
                    $actionHtml = '<button class="btn btn-primary btn-sm mb-1 open-spk-modal" data-visitation-id="'.$visitationId.'" data-current-index="0">Input/Edit SPK</button>';
                    
                    if ($spk) {
                        $createdAt = $spk->created_at ? $spk->created_at->format('d M Y, H:i') : '-';
                        $updatedAt = $spk->updated_at ? $spk->updated_at->format('d M Y, H:i') : '-';
                        
                        $actionHtml .= '<div class="mt-1 text-muted" style="font-size: 0.75rem;">';
                        $actionHtml .= '<div><strong>Dibuat:</strong> ' . $createdAt . '</div>';
                        $actionHtml .= '<div><strong>Diubah:</strong> ' . $updatedAt . '</div>';
                        $actionHtml .= '</div>';
                    } else {
                        $actionHtml .= '<div class="mt-1 text-muted" style="font-size: 0.75rem;">';
                        $actionHtml .= '<div><strong>Dibuat:</strong> -</div>';
                        $actionHtml .= '<div><strong>Diubah:</strong> -</div>';
                        $actionHtml .= '</div>';
                    }
                    
                    return $actionHtml;
                })
                ->addColumn('spk_filled', function($row) {
                    // Check if any SPK detail for any riwayat in this group has waktu_mulai filled
                    $riwayatIds = collect($row['all_tindakan'])->pluck('id')->toArray();
                    $spk = \App\Models\ERM\Spk::whereIn('riwayat_tindakan_id', $riwayatIds)->first();
                    if ($spk && !empty($spk->waktu_mulai)) {
                        return true;
                    }
                    return false;
                })
                ->addColumn('spk_status_color', function($row) {
                    $riwayatIds = collect($row['all_tindakan'])->pluck('id')->toArray();
                    $spks = \App\Models\ERM\Spk::whereIn('riwayat_tindakan_id', $riwayatIds)->get();
                    
                    if ($spks->isNotEmpty()) {
                        $allCompleted = true;
                        $anyStarted = false;
                        
                        foreach ($spks as $spk) {
                            $hasSelesai = !empty($spk->waktu_selesai);
                            if (!$hasSelesai) {
                                $allCompleted = false;
                            }
                            
                            $hasMulai = !empty($spk->waktu_mulai);
                            if ($hasMulai) {
                                $anyStarted = true;
                            }
                        }
                        
                        if ($allCompleted) return 'green';
                        if ($anyStarted) return 'yellow';
                    }
                    return '';
                })
                ->setRowClass(function($row) {
                    $riwayatIds = collect($row['all_tindakan'])->pluck('id')->toArray();
                    $spks = \App\Models\ERM\Spk::whereIn('riwayat_tindakan_id', $riwayatIds)->get();
                    
                    if ($spks->isNotEmpty()) {
                        $allCompleted = true;
                        $anyStarted = false;
                        
                        foreach ($spks as $spk) {
                            $hasSelesai = !empty($spk->waktu_selesai);
                            if (!$hasSelesai) {
                                $allCompleted = false;
                            }
                            
                            $hasMulai = !empty($spk->waktu_mulai);
                            if ($hasMulai) {
                                $anyStarted = true;
                            }
                        }
                        
                        if ($allCompleted) return 'table-success';
                        if ($anyStarted) return 'table-warning';
                    }
                    return '';
                })
                ->rawColumns(['aksi', 'tindakan', 'status_layanan'])
                ->make(true);
        }
        return view('erm.spk.index');
    }

    public function spkCreate(Request $request)
    {
        $riwayatId = $request->query('riwayat_id');
        $visitationId = $request->query('visitation_id');
        $currentIndex = (int)$request->query('index', 0);
        $riwayat = null;
        $allRiwayat = null;
        
        if ($riwayatId) {
            // Single riwayat_id provided (legacy support)
            $riwayat = RiwayatTindakan::with([
                'visitation.pasien',
                'visitation.dokter.user',
                'tindakan',
            ])->find($riwayatId);
            
            if (!$riwayat) {
                return redirect()->back()->with('error', 'Riwayat tindakan tidak ditemukan');
            }
            
            // Get all riwayat for this visitation for navigation
            $allRiwayat = RiwayatTindakan::with([
                'tindakan',
            ])->where('visitation_id', $riwayat->visitation_id)->get();
            
            // Find the index of the current riwayat
            $currentIndex = $allRiwayat->search(function($item) use ($riwayatId) {
                return $item->id == $riwayatId;
            });
        } elseif ($visitationId) {
            // Get all riwayat for this visitation
            $allRiwayat = RiwayatTindakan::with([
                'visitation.pasien',
                'visitation.dokter.user',
                'tindakan',
            ])->where('visitation_id', $visitationId)->get();
            
            if ($allRiwayat->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada riwayat tindakan untuk kunjungan ini');
            }
            
            // Use the specified index or default to 0
            $currentIndex = min($currentIndex, $allRiwayat->count() - 1);
            $riwayat = $allRiwayat[$currentIndex];
        }
        
        return view('erm.spk.create', compact('riwayat', 'allRiwayat', 'currentIndex'));
    }

    public function spkModal(Request $request)
    {
        $visitationId = $request->query('visitation_id');
        $currentIndex = (int)$request->query('index', 0);
        $riwayat = null;
        $allRiwayat = null;
        
        if ($visitationId) {
            // Get all riwayat for this visitation
            $allRiwayat = RiwayatTindakan::with([
                'visitation.pasien',
                'visitation.dokter.user',
                'tindakan',
            ])->where('visitation_id', $visitationId)->get();
            
            if ($allRiwayat->isEmpty()) {
                return response()->json(['error' => 'Tidak ada riwayat tindakan untuk kunjungan ini'], 404);
            }
            
            // Use the specified index or default to 0
            $currentIndex = min($currentIndex, $allRiwayat->count() - 1);
            $riwayat = $allRiwayat[$currentIndex];
        }
        
        return view('erm.spk.modal-content', compact('riwayat', 'allRiwayat', 'currentIndex'));
    }
    
    public function getSpkDataByRiwayat($riwayatId)
    {
        // Use eager loading to reduce queries
        $riwayat = RiwayatTindakan::with([
            'visitation.pasien',
            'visitation.dokter.user',
            'tindakan',
        ])->findOrFail($riwayatId);

        $spk = Spk::where('riwayat_tindakan_id', $riwayatId)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'riwayat' => $riwayat,
                'spk' => $spk,
                'pasien_nama' => $riwayat->visitation?->pasien?->nama ?? '',
                'pasien_id' => $riwayat->visitation?->pasien?->id ?? '',
                'tindakan_nama' => $riwayat->tindakan?->nama ?? '',
                'dokter_nama' => $riwayat->visitation?->dokter?->user?->name ?? '',
                'harga' => $riwayat->tindakan?->harga ?? ''
            ]
        ]);
    }

    public function saveSpk(Request $request)
    {
        try {
            $request->validate([
                'riwayat_tindakan_id' => 'required'
            ]);

            $riwayatTindakanId = $request->riwayat_tindakan_id;
            $riwayatTindakan = \App\Models\ERM\RiwayatTindakan::with(['visitation', 'tindakan', 'visitation.pasien', 'visitation.dokter'])->find($riwayatTindakanId);
            
            if (!$riwayatTindakan) {
                return response()->json([
                    'success' => false,
                    'message' => "Riwayat tindakan tidak ditemukan"
                ], 404);
            }
            
            // Check required relationships
            if (!$riwayatTindakan->visitation || !$riwayatTindakan->visitation->pasien || 
                !$riwayatTindakan->visitation->dokter || !$riwayatTindakan->tindakan) {
                return response()->json([
                    'success' => false,
                    'message' => "Data tidak lengkap. Pastikan visitation, pasien, dokter, dan tindakan tersedia."
                ], 400);
            }

            try {
                DB::beginTransaction();
                
                // Get or create SPK
                $spkData = [
                    'visitation_id' => $riwayatTindakan->visitation->id,
                    'pasien_id' => $riwayatTindakan->visitation->pasien->id,
                    'tindakan_id' => $riwayatTindakan->tindakan->id,
                    'dokter_id' => $riwayatTindakan->visitation->dokter->id,
                    'tanggal_tindakan' => $request->tanggal_tindakan ?? Carbon::now()->toDateString(),
                    'waktu_mulai' => $request->filled('waktu_mulai') ? $request->waktu_mulai : null,
                    'waktu_selesai' => $request->filled('waktu_selesai') ? $request->waktu_selesai : null,
                ];

                $spk = Spk::updateOrCreate(
                    ['riwayat_tindakan_id' => $riwayatTindakanId],
                    $spkData
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'SPK berhasil disimpan!',
                    'data' => $spk
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error saving SPK: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menyimpan SPK'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in saveSpk: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data'
            ], 500);
        }
    }

    public function destroyRiwayatTindakan($id)
    {
        try {
            $riwayat = \App\Models\ERM\RiwayatTindakan::findOrFail($id);

            // A multi visit session is cancelled on its own and gives its quota back. The first (paid) session
            // carries the package charge, so it goes only once no later session is left.
            $usageId = $riwayat->multi_visit_usage_id ? (int) $riwayat->multi_visit_usage_id : null;
            $otherSessions = $usageId
                ? RiwayatTindakan::where('multi_visit_usage_id', $usageId)->where('id', '!=', $riwayat->id)->get(['id'])
                : collect();
            if ($otherSessions->isNotEmpty() && (int) $riwayat->id === (int) $otherSessions->pluck('id')->push($riwayat->id)->min()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ini sesi pertama paket (yang ditagih). Batalkan dulu ' . $otherSessions->count() . ' sesi lanjutannya.',
                ], 422);
            }

            DB::beginTransaction();

            $this->deleteSingleRiwayatTindakanGraph($riwayat);

            $removedMultiVisitUsageId = null;
            if ($usageId) {
                if ($otherSessions->isEmpty()) {
                    MultiVisitUsage::where('id', $usageId)->delete();
                    $removedMultiVisitUsageId = $usageId;
                } else {
                    MultiVisitUsage::where('id', $usageId)->where('used', '>', 0)->decrement('used');
                }
            }

            DB::commit();

            $message = $usageId && $otherSessions->isNotEmpty()
                ? 'Sesi tindakan berhasil dibatalkan, kuota paket dikembalikan 1 sesi.'
                : 'Riwayat tindakan berhasil dibatalkan.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'removed_riwayat_ids' => [(int) $riwayat->id],
                'removed_multi_visit_usage_id' => $removedMultiVisitUsageId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error deleting riwayat tindakan in destroyRiwayatTindakan: ' . $e->getMessage(), ['riwayat_id' => $id]);
            return response()->json(['success' => false, 'message' => 'Failed to remove riwayat tindakan.'], 500);
        }
    }

    private function deleteSingleRiwayatTindakanGraph(RiwayatTindakan $riwayat): void
    {
        $visitationIdRaw = $riwayat->visitation_id;
        $visitationIdString = !is_null($visitationIdRaw) ? (string) $visitationIdRaw : null;

        if (empty($visitationIdString)) {
            Log::warning('destroyRiwayatTindakan: empty visitation_id for riwayat', ['riwayat_id' => $riwayat->id, 'visitation_id_raw' => $visitationIdRaw]);
            throw new \RuntimeException('Invalid visitation id for this record.');
        }

        $quotedVisitation = str_replace("'", "\\'", $visitationIdString);
        $quotedBillableTindakan = str_replace("'", "\\'", (string) $riwayat->tindakan_id);
        $quotedBillableRiwayat = str_replace("'", "\\'", (string) $riwayat->id);

        Billing::whereRaw("billable_id = '{$quotedBillableTindakan}'")
            ->where('billable_type', 'App\\Models\\ERM\\Tindakan')
            ->whereRaw("visitation_id = '{$quotedVisitation}'")
            ->delete();

        Billing::whereRaw("billable_id = '{$quotedBillableRiwayat}'")
            ->where('billable_type', 'App\\Models\\ERM\\RiwayatTindakan')
            ->whereRaw("visitation_id = '{$quotedVisitation}'")
            ->delete();

        Billing::whereRaw("visitation_id = '{$quotedVisitation}'")
            ->where('billable_type', 'App\\Models\\ERM\\Obat')
            ->where('keterangan', 'like', '%Obat Bundled%')
            ->delete();

        DB::table('erm_riwayat_tindakan_obat')
            ->where('riwayat_tindakan_id', $riwayat->id)
            ->delete();

        \App\Models\ERM\InformConsent::where('riwayat_tindakan_id', $riwayat->id)->delete();
        \App\Models\ERM\Spk::where('riwayat_tindakan_id', $riwayat->id)->delete();

        // The SPK Tindakan page is gone and no new records are created, but older tindakan may still have them
        $spkTindakanIds = \App\Models\ERM\SpkTindakan::where('riwayat_tindakan_id', $riwayat->id)->pluck('id');
        if ($spkTindakanIds->isNotEmpty()) {
            \App\Models\ERM\SpkTindakanItem::whereIn('spk_tindakan_id', $spkTindakanIds)->delete();
        }
        \App\Models\ERM\SpkTindakan::where('riwayat_tindakan_id', $riwayat->id)->delete();

        $riwayat->delete();
    }

    public function getSopList($tindakanId)
    {
        // Return kode tindakan list (with obat bundles) instead of SOP so the Detail button shows kode details.
        $tindakan = \App\Models\ERM\Tindakan::with(['kodeTindakans.obats'])->findOrFail($tindakanId);

        $kodeTindakans = $tindakan->kodeTindakans->map(function($kode, $i) {
            return [
                'no' => $i + 1,
                'id' => $kode->id,
                'kode' => $kode->kode ?? '',
                'nama' => $kode->nama ?? '',
                'obats' => $kode->obats->map(function($obat) {
                    return [
                        'id' => $obat->id,
                        'nama' => $obat->nama,
                        'jumlah' => $obat->pivot->qty ?? null,
                        'dosis' => $obat->pivot->dosis ?? null,
                        'satuan_dosis' => $obat->pivot->satuan_dosis ?? null,
                    ];
                })->values()->toArray(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'tindakan' => $tindakan->nama,
            'kode_tindakans' => $kodeTindakans,
        ]);
    }
    
    /**
     * Printable detail of tindakan history grouped by visitation for the same patient.
     * Shows each tindakan with its kode tindakan and bundled/substituted obat (from riwayat pivot).
     */
    public function printHistoryDetail($visitationId)
    {
        $currentVisitation = Visitation::with(['pasien', 'dokter.user'])->findOrFail($visitationId);
        $patientId = $currentVisitation->pasien_id;

        // If ?all=1 provided, include all visitations of this patient; otherwise only the current visitation
        if (request()->boolean('all')) {
            $visitations = Visitation::where('pasien_id', $patientId)
                ->orderBy('tanggal_visitation', 'asc')
                ->get(['id','tanggal_visitation','dokter_id']);
        } else {
            $visitations = collect([$currentVisitation]);
        }

        if ($visitations->isEmpty()) {
            return view('erm.tindakan.riwayat-print', [
                'currentVisitation' => $currentVisitation,
                'groups' => collect(),
            ]);
        }

        // Fetch all riwayat for these visitations
        $riwayat = RiwayatTindakan::with(['tindakan'])
            ->whereIn('visitation_id', $visitations->pluck('id'))
            ->orderBy('tanggal_tindakan', 'asc')
            ->get();

        $riwayatIds = $riwayat->pluck('id');
        $tindakanIds = $riwayat->pluck('tindakan_id')->unique();

        // Load tindakan -> kode tindakans map
        $tindakans = Tindakan::with('kodeTindakans')
            ->whereIn('id', $tindakanIds)
            ->get()
            ->keyBy('id');

        // Load pivot rows (riwayat->kode->obat with dosage)
        $pivotRows = DB::table('erm_riwayat_tindakan_obat as rto')
            ->leftJoin('erm_obat as o', 'o.id', '=', 'rto.obat_id')
            ->leftJoin('erm_kode_tindakan as kt', 'kt.id', '=', 'rto.kode_tindakan_id')
            ->whereIn('rto.riwayat_tindakan_id', $riwayatIds)
            ->select(
                'rto.riwayat_tindakan_id',
                'rto.kode_tindakan_id',
                'kt.kode as kode',
                'kt.nama as kode_nama',
                'o.nama as obat_nama',
                'o.satuan as obat_satuan',
                'rto.qty',
                'rto.dosis',
                'rto.satuan_dosis'
            )
            ->orderBy('kt.kode')
            ->get()
            ->groupBy('riwayat_tindakan_id');

        // Group data by visitation
        $groups = [];
        foreach ($visitations as $v) {
            $groups[$v->id] = [
                'visitation' => $v->load(['dokter.user']),
                'riwayats' => []
            ];
        }

        foreach ($riwayat as $r) {
            $tindakan = $tindakans->get($r->tindakan_id);
            $kodeItems = [];
            if ($tindakan) {
                foreach ($tindakan->kodeTindakans as $kt) {
                    $kodeItems[$kt->id] = [
                        'kode' => $kt->kode,
                        'nama' => $kt->nama,
                        'obats' => []
                    ];
                }
            }

            $rows = $pivotRows->get($r->id) ?: collect();
            foreach ($rows as $row) {
                if (!isset($kodeItems[$row->kode_tindakan_id])) {
                    $kodeItems[$row->kode_tindakan_id] = [
                        'kode' => $row->kode,
                        'nama' => $row->kode_nama,
                        'obats' => []
                    ];
                }
                $dose = '';
                if (!empty($row->dosis)) {
                    $dose = $row->dosis . (!empty($row->satuan_dosis) ? (' ' . $row->satuan_dosis) : '');
                }
                $kodeItems[$row->kode_tindakan_id]['obats'][] = [
                    'nama' => $row->obat_nama,
                    'satuan' => $row->obat_satuan,
                    'qty' => $row->qty,
                    'dosis' => $dose,
                ];
            }

            $groups[$r->visitation_id]['riwayats'][] = [
                'tindakan_nama' => $r->tindakan->nama ?? '-',
                'kode_items' => array_values($kodeItems)
            ];
        }

        return view('erm.tindakan.riwayat-print', [
            'currentVisitation' => $currentVisitation,
            'groups' => collect($groups)
        ]);
    }
};
