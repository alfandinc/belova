<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Tindakan;
use App\Models\ERM\PaketTindakan;
use App\Models\ERM\RiwayatTindakan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\InformConsent;
use App\Models\ERM\Spk;
use App\Models\User;
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

        // Remove all existing pivot entries for this riwayat tindakan
        \DB::table('erm_riwayat_tindakan_obat')->where('riwayat_tindakan_id', $id)->delete();

        // Insert new pivot entries with qty, dosis, satuan_dosis
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

        foreach ($obats as $kodeTindakanId => $obatIds) {
            if (!is_array($obatIds)) continue;
            foreach ($obatIds as $obatId) {
                $rawDosis = isset($dosis[$kodeTindakanId][$obatId]) ? $dosis[$kodeTindakanId][$obatId] : null;
                $normDosis = $normalizeDecimal($rawDosis);
                \DB::table('erm_riwayat_tindakan_obat')->insert([
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

            // set tindakan harga to total HPP of attached kode tindakan
            $tindakan->harga = $totalHpp;
            $tindakan->save();
        }

        return response()->json(['success' => true, 'message' => 'Obat berhasil disimpan']);
    }
    /**
     * Show detail for riwayat tindakan (kode tindakan & obat list, substitution UI)
     */
    public function getRiwayatDetail($id)
    {
        $riwayatTindakan = \App\Models\ERM\RiwayatTindakan::with(['tindakan.kodeTindakans.obats', 'tindakan', 'tindakan.kodeTindakans', 'tindakan.obats'])->find($id);
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
        $visitation = Visitation::findOrFail($visitationId);
        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);
        $spesialisasiId = $visitation->dokter->spesialisasi_id;


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

        return datatables()->of($tindakan)
            ->addColumn('action', function ($row) {
                return '
                <button class="btn btn-success btn-sm buat-tindakan" data-id="' . $row->id . '" data-type="tindakan">Buat Tindakan</button>
            ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getPaketTindakanData(Request $request, $spesialisasiId)
    {
        $paketTindakan = PaketTindakan::whereHas('tindakan', function ($query) use ($spesialisasiId) {
            $query->where('spesialis_id', $spesialisasiId);
        })->with('tindakan')->get();

        return datatables()->of($paketTindakan)
            ->addColumn('action', function ($row) {
                // Properly encode tindakan data as JSON
                $tindakanJson = json_encode($row->tindakan);
                return '<button class="btn btn-success btn-sm buat-paket-tindakan" data-id="' . $row->id . '" data-tindakan=\'' . $tindakanJson . '\'>Buat</button>';
            })
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
            'harga_3_kali' => $tindakan->harga_3_kali
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

    public function saveInformConsent(Request $request)
    {
        // Map field names from JavaScript to what controller expects
        if ($request->has('signatureData') && !$request->has('signature')) {
            $request->merge(['signature' => $request->signatureData]);
        }

        if ($request->has('witnessSignatureData') && !$request->has('witness_signature')) {
            $request->merge(['witness_signature' => $request->witnessSignatureData]);
        }

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
            'paket_id' => 'nullable|exists:erm_paket_tindakan,id',
        ];
        $data = $request->validate($rules);

        $visitation = Visitation::findOrFail($data['visitation_id']);
        $pasien = $visitation->pasien;
        $tindakan = Tindakan::findOrFail($data['tindakan_id']);

        // Always create RiwayatTindakan (we may attach multi-visit usage after)
        $riwayatTindakan = RiwayatTindakan::create([
            'visitation_id' => $data['visitation_id'],
            'tanggal_tindakan' => $data['tanggal'],
            'tindakan_id' => $data['tindakan_id'],
            'paket_tindakan_id' => $data['paket_id'] ?? null,
        ]);

        $informConsent = null;
        // Only create InformConsent and PDF if signatures are present
        if (!empty($data['signature']) && !empty($data['witness_signature'])) {
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
                                if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'div') {
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
            $perawatName = optional(auth()->user())->name ?? '';

            // Helper to generate QR data URI via Google Chart API (fallback to empty string on failure)
            $generateQrDataUri = function ($text) {
                $dataUri = '';
                if (empty($text)) return $dataUri;
                $url = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($text) . '&choe=UTF-8';

                // Try file_get_contents first
                $img = false;
                if (ini_get('allow_url_fopen')) {
                    $img = @file_get_contents($url);
                }

                // If file_get_contents failed, try curl
                if ($img === false) {
                    if (function_exists('curl_init')) {
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        $img = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        if ($img === false || $httpCode >= 400) {
                            $img = false;
                        }
                    }
                }

                if ($img !== false && $img !== null) {
                    $dataUri = 'data:image/png;base64,' . base64_encode($img);
                }

                return $dataUri;
            };

            // Prefer client-submitted QR data URIs (from browser) if provided
            $dokterQr = $request->input('dokter_qr') ?? $generateQrDataUri('Dokter: ' . $dokterName);
            $perawatQr = $request->input('perawat_qr') ?? $generateQrDataUri('Perawat: ' . $perawatName);

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
            $informConsent = InformConsent::create([
                'visitation_id' => $data['visitation_id'],
                'tindakan_id' => $data['tindakan_id'],
                'file_path' => $pdfPath,
                'paket_id' => $data['paket_id'] ?? null,
                'riwayat_tindakan_id' => $riwayatTindakan->id,
                'created_at' => now(),
            ]);
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

        if (isset($data['paket_id'])) {
            $paketId = $data['paket_id'];
            $visitationId = $data['visitation_id'];
            $existingBilling = Billing::where('visitation_id', $visitationId)
                ->where('billable_id', $paketId)
                ->where('billable_type', 'App\\Models\\ERM\\PaketTindakan')
                ->first();
            if (!$existingBilling) {
                $paketTindakan = PaketTindakan::find($paketId);
                $billingData = [
                    'visitation_id' => $visitationId,
                    'billable_id' => $paketId,
                    'billable_type' => 'App\\Models\\ERM\\PaketTindakan',
                    'jumlah' => !empty($data['jumlah']) ? $data['jumlah'] : ($paketTindakan->harga_paket ?? 0),
                    'keterangan' => !empty($data['keterangan']) ? $data['keterangan'] : 'Paket Tindakan: ' . ($paketTindakan->nama ?? '')
                ];
                $billing = Billing::create($billingData);
            } else {
                $billing = $existingBilling;
            }
        } else {
            // Handle normal vs 3x visit pricing & multi-visit usage
            $shouldCreateBilling = true;
            $billingAmount = $tindakan->harga;

            if ($hargaType === '3x' && !empty($tindakan->harga_3_kali)) {
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
                    // create new multi-visit usage record (default total 3)
                    $newUsage = MultiVisitUsage::create([
                        'pasien_id' => $visitation->pasien_id,
                        'tindakan_id' => $tindakan->id,
                        'first_visitation_id' => $data['visitation_id'],
                        'total' => 3,
                        'used' => 1,
                    ]);
                    $riwayatTindakan->multi_visit_usage_id = $newUsage->id;
                    $riwayatTindakan->save();
                    // charge the 3x visit price on first use
                    $billingAmount = $tindakan->harga_3_kali;
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
                        $billingKeterangan .= ' (1/' . ($newUsage->total ?? 3) . ') - 3x Charge';
                    }
                }

                // Calculate discount amount based on harga type. For 3x visits we do not apply the
                // normal tindakan discount (unless you want a separate 3x discount field).
                $discountAmount = 0;
                if ($tindakan->diskon_active) {
                    if ($hargaType === '3x') {
                        // No automatic discount applied to 3x-visit pricing by default
                        $discountAmount = 0;
                    } else {
                        $discountAmount = ($tindakan->harga - $tindakan->harga_diskon);
                    }
                }

                $billingData = [
                    'visitation_id' => $data['visitation_id'],
                    'billable_id' => $riwayatTindakan->id,
                    'billable_type' => 'App\\Models\\ERM\\RiwayatTindakan',
                    'jumlah' => $billingAmount,
                    'diskon' => $discountAmount,
                    'diskon_type' => $tindakan->diskon_active ? ($tindakan->diskon_type ?? 'nominal') : null,
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

        // Create SPK Tindakan (new detailed SPK system)
        $spkTindakan = \App\Models\ERM\SpkTindakan::create([
            'riwayat_tindakan_id' => $riwayatTindakan->id,
            'tanggal_tindakan' => $data['tanggal'],
            'status' => 'pending',
        ]);

        // Create SPK Tindakan Items for each kode tindakan
        $spkTindakanItems = [];
        foreach ($kodeTindakans as $kodeTindakan) {
            $spkTindakanItem = \App\Models\ERM\SpkTindakanItem::create([
                'spk_tindakan_id' => $spkTindakan->id,
                'kode_tindakan_id' => $kodeTindakan->id,
                'penanggung_jawab' => null, // Will be filled later by staff
                'sbk' => null,
                'sba' => null,
                'sdc' => null,
                'sdk' => null,
                'sdl' => null,
                'notes' => null,
            ]);
            $spkTindakanItems[] = $spkTindakanItem;
        }

        // Create billing for bundled obats
        $obatBillings = [];
        foreach ($tindakan->obats as $obat) {
            $obatBilling = Billing::create([
                'visitation_id' => $data['visitation_id'],
                'billable_id' => $obat->id,
                'billable_type' => 'App\\Models\\ERM\\Obat',
                'jumlah' => 0,
                'qty' => 1,
                'keterangan' => 'Obat Bundled: ' . $obat->nama
            ]);
            $obatBillings[] = $obatBilling;
        }

        return response()->json([
            'success' => true,
            'message' => 'Riwayat tindakan dan billing berhasil disimpan. Inform consent akan dibuat jika tersedia.',
            'informConsent' => $informConsent,
            'spk' => $spk,
            'spkTindakan' => $spkTindakan,
            'spkTindakanItems' => $spkTindakanItems,
            'billing' => $billing,
            'obatBillings' => $obatBillings,
            'riwayatTindakan' => $riwayatTindakan
        ]);
    }

    public function getRiwayatTindakanHistory($visitationId)
    {
        // First, get the patient ID from the current visitation
        $currentVisitation = Visitation::findOrFail($visitationId);
        $patientId = $currentVisitation->pasien_id;

        // Get all visitations for this patient
        $patientVisitations = Visitation::where('pasien_id', $patientId)
            ->pluck('id')->toArray();

        // Get all riwayat tindakan for all visitations of this patient
            $history = RiwayatTindakan::whereIn('visitation_id', $patientVisitations)
                ->with(['tindakan', 'paketTindakan', 'visitation.dokter.user', 'visitation.dokter.spesialisasi', 'informConsent'])
                ->get()
                ->map(function ($item) use ($visitationId) {
                    $tanggalRaw = $item->visitation->tanggal_visitation ?? null;
                    $tanggalFormatted = '-';
                    if ($tanggalRaw) {
                        $tanggalFormatted = Carbon::parse($tanggalRaw)
                            ->locale('id')
                            ->isoFormat('D MMMM YYYY');
                    }
                    return (object) [
                        'id' => $item->id,
                        'visitation_id' => $item->visitation_id,
                        'tanggal' => $tanggalFormatted,
                        'tanggal_raw' => $tanggalRaw,
                        'tindakan' => $item->tindakan->nama ?? '-',
                        'paket' => $item->paketTindakan->nama ?? '-',
                        'dokter' => $item->visitation->dokter->user->name ?? '-',
                        'spesialisasi' => $item->visitation->dokter->spesialisasi->nama ?? '-',
                        'inform_consent' => $item->informConsent,
                        'current' => ($item->visitation_id == $visitationId) ? true : false
                    ];
                })
                ->sortByDesc(function ($item) {
                    // Sort by raw visitation date (string, so convert to timestamp)
                    return $item->tanggal_raw ? strtotime($item->tanggal_raw) : 0;
                })
                ->values();

        return datatables()->of($history)
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
                    $hasSelesai = $spk->details()->whereNotNull('waktu_selesai')->where('waktu_selesai', '!=', '')->exists();
                    if ($hasSelesai) return 'green';
                    $hasMulai = $spk->details()->whereNotNull('waktu_mulai')->where('waktu_mulai', '!=', '')->exists();
                    if ($hasMulai) return 'yellow';
                }
                return '';
            })
            ->setRowClass(function($row) {
                $spk = \App\Models\ERM\Spk::where('riwayat_tindakan_id', $row->id)->first();
                if ($spk) {
                    $hasSelesai = $spk->details()->whereNotNull('waktu_selesai')->where('waktu_selesai', '!=', '')->exists();
                    if ($hasSelesai) return 'table-success';
                    $hasMulai = $spk->details()->whereNotNull('waktu_mulai')->where('waktu_mulai', '!=', '')->exists();
                    if ($hasMulai) return 'table-warning';
                }
                return '';
            })
            ->rawColumns(['dokumen', 'status', 'aksi'])
            ->make(true);
    }

    // public function generateSopPdf($id)
    // {
    //     $tindakan = Tindakan::with('sop')->findOrFail($id);

    //     // If SOP doesn't exist, return a message
    //     if ($tindakan->sop->isEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'SOP belum tersedia untuk tindakan ini'
    //         ]);
    //     }

    //     $pdf = PDF::loadView('erm.tindakan.sop.pdf', [
    //         'tindakan' => $tindakan,
    //         'sopList' => $tindakan->sop->sortBy('urutan')
    //     ]);

    //     $filename = 'SOP-' . str_replace(' ', '-', $tindakan->nama) . '.pdf';

    //     return $pdf->stream($filename);
    // }

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
            $riwayat = \App\Models\ERM\RiwayatTindakan::with(['visitation.pasien', 'tindakan', 'visitation.dokter.user', 'paketTindakan']);

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
                    ->whereHas('details', function($q) {
                        $q->where(function($subQ) {
                            $subQ->whereNotNull('waktu_mulai')
                                 ->where('waktu_mulai', '!=', '')
                                 ->orWhereNotNull('waktu_selesai')
                                 ->where('waktu_selesai', '!=', '')
                                 ->orWhere('sbk', true)
                                 ->orWhere('sba', true)
                                 ->orWhere('sdc', true)
                                 ->orWhere('sdk', true)
                                 ->orWhere('sdl', true)
                                 ->orWhereNotNull('notes')
                                 ->where('notes', '!=', '');
                        });
                    })
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
                            'paket_tindakan' => $item->paketTindakan
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
                        if ($item['tindakan']) {
                            return $item['tindakan']->nama;
                        } elseif ($item['paket_tindakan']) {
                            return 'Paket: ' . $item['paket_tindakan']->nama;
                        }
                        return '-';
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
                    if ($spk && $spk->details()->whereNotNull('waktu_mulai')->exists()) {
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
                            $hasSelesai = $spk->details()->whereNotNull('waktu_selesai')->where('waktu_selesai', '!=', '')->exists();
                            if (!$hasSelesai) {
                                $allCompleted = false;
                            }
                            
                            $hasMulai = $spk->details()->whereNotNull('waktu_mulai')->where('waktu_mulai', '!=', '')->exists();
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
                            $hasSelesai = $spk->details()->whereNotNull('waktu_selesai')->where('waktu_selesai', '!=', '')->exists();
                            if (!$hasSelesai) {
                                $allCompleted = false;
                            }
                            
                            $hasMulai = $spk->details()->whereNotNull('waktu_mulai')->where('waktu_mulai', '!=', '')->exists();
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
                'tindakan.sop',
                'paketTindakan'
            ])->find($riwayatId);
            
            if (!$riwayat) {
                return redirect()->back()->with('error', 'Riwayat tindakan tidak ditemukan');
            }
            
            // Get all riwayat for this visitation for navigation
            $allRiwayat = RiwayatTindakan::with([
                'tindakan',
                'paketTindakan'
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
                'tindakan.sop',
                'paketTindakan'
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
                'tindakan.sop',
                'paketTindakan'
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
            'paketTindakan',
            'visitation.pasien',
            'visitation.dokter.user',
            'tindakan.sop' => function($query) {
                $query->orderBy('urutan');
            }
        ])->findOrFail($riwayatId);

        // Get SPK with details in one query
        $spk = Spk::with(['details.sop' => function($query) {
            $query->orderBy('urutan');
        }])->where('riwayat_tindakan_id', $riwayatId)->first();
        
        // Cache users query for better performance
        $users = cache()->remember('spk_users', 300, function() {
            return User::whereHas('roles', function($query) {
                $query->whereIn('name', ['Dokter', 'Beautician']);
            })->get(['id', 'name']);
        });

        $sopList = $riwayat->tindakan && $riwayat->tindakan->sop 
            ? $riwayat->tindakan->sop->sortBy('urutan')->values()->toArray()
            : [];

        return response()->json([
            'success' => true,
            'data' => [
                'riwayat' => $riwayat,
                'spk' => $spk,
                'users' => $users,
                'sop_list' => $sopList,
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
                    'tanggal_tindakan' => $request->tanggal_tindakan ?? Carbon::now()->toDateString()
                ];
                
                $spk = Spk::updateOrCreate(
                    ['riwayat_tindakan_id' => $riwayatTindakanId],
                    $spkData
                );
                
                // Delete existing details if any
                $spk->details()->delete();
                
                // Create new details
                if ($request->has('details')) {
                    foreach ($request->details as $detail) {
                        if (empty($detail['sop_id'])) {
                            continue;
                        }
                        
                        $spk->details()->create([
                            'sop_id' => $detail['sop_id'],
                            'penanggung_jawab' => $detail['penanggung_jawab'] ?? 'Not specified',
                            'sbk' => isset($detail['sbk']),
                            'sba' => isset($detail['sba']),
                            'sdc' => isset($detail['sdc']),
                            'sdk' => isset($detail['sdk']),
                            'sdl' => isset($detail['sdl']),
                            'waktu_mulai' => !empty($detail['waktu_mulai']) ? $detail['waktu_mulai'] : null,
                            'waktu_selesai' => !empty($detail['waktu_selesai']) ? $detail['waktu_selesai'] : null,
                            'notes' => $detail['notes'] ?? null
                        ]);
                    }
                }
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'SPK berhasil disimpan!',
                    'data' => $spk->load('details')
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
        $riwayat = \App\Models\ERM\RiwayatTindakan::findOrFail($id);
        // Resolve visitation id as a string. The `finance_billing.visitation_id` column is a string,
        // so avoid casting to integer which can trigger numeric comparisons in MySQL and errors
        // for alphanumeric values. Prefer the loaded relation id when available.
        $visitationIdRaw = $riwayat->visitation_id;
        $visitationIdString = null;
        if ($riwayat->relationLoaded('visitation') && $riwayat->visitation && isset($riwayat->visitation->id)) {
            $visitationIdString = (string) $riwayat->visitation->id;
        } elseif (!is_null($visitationIdRaw)) {
            $visitationIdString = (string) $visitationIdRaw;
        }

        if (empty($visitationIdString)) {
            Log::warning('destroyRiwayatTindakan: empty visitation_id for riwayat', ['riwayat_id' => $id, 'visitation_id_raw' => $visitationIdRaw]);
            return response()->json(['success' => false, 'message' => 'Invalid visitation id for this record.'], 400);
        }

        try {
            // Delete all related billing records for this tindakan, riwayat tindakan, and bundled obats
            // 1. Billing for tindakan
            // Use a raw quoted comparison to force string equality and avoid MySQL numeric coercion
            $quotedVisitation = str_replace("'", "\\'", $visitationIdString);
            $quotedBillableTindakan = str_replace("'", "\\'", (string) $riwayat->tindakan_id);
            \App\Models\Finance\Billing::whereRaw("billable_id = '{$quotedBillableTindakan}'")
                ->where('billable_type', 'App\\Models\\ERM\\Tindakan')
                ->whereRaw("visitation_id = '{$quotedVisitation}'")
                ->delete();

            // 2. Billing for riwayat tindakan
            $quotedBillableRiwayat = str_replace("'", "\\'", (string) $riwayat->id);
            \App\Models\Finance\Billing::whereRaw("billable_id = '{$quotedBillableRiwayat}'")
                ->where('billable_type', 'App\\Models\\ERM\\RiwayatTindakan')
                ->whereRaw("visitation_id = '{$quotedVisitation}'")
                ->delete();

            // 3. Billing for bundled obats
            \App\Models\Finance\Billing::whereRaw("visitation_id = '{$quotedVisitation}'")
                ->where('billable_type', 'App\\Models\\ERM\\Obat')
                ->where('keterangan', 'like', '%Obat Bundled%')
                ->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting billing records in destroyRiwayatTindakan: ' . $e->getMessage(), ['riwayat_id' => $id, 'visitation_id_raw' => $visitationIdRaw, 'visitation_id_used' => $visitationIdString]);
            return response()->json(['success' => false, 'message' => 'Failed to remove billing records.'], 500);
        }
        // Delete associated InformConsent if exists
        $informConsent = \App\Models\ERM\InformConsent::where('riwayat_tindakan_id', $riwayat->id)->first();
        if ($informConsent) {
            $informConsent->delete();
        }
        // Delete associated Spk if exists
        $spk = \App\Models\ERM\Spk::where('riwayat_tindakan_id', $riwayat->id)->first();
        if ($spk) {
            $spk->delete();
        }

        $riwayat->delete();

        return response()->json(['success' => true, 'message' => 'Riwayat tindakan, billing, inform consent, dan SPK berhasil dibatalkan.']);
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
                        'jumlah' => $obat->pivot->jumlah ?? null,
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