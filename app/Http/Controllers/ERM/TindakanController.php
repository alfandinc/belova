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
        foreach ($obats as $kodeTindakanId => $obatIds) {
            if (!is_array($obatIds)) continue;
            foreach ($obatIds as $obatId) {
                \DB::table('erm_riwayat_tindakan_obat')->insert([
                    'riwayat_tindakan_id' => $id,
                    'kode_tindakan_id' => $kodeTindakanId,
                    'obat_id' => $obatId,
                    'qty' => isset($qty[$kodeTindakanId][$obatId]) ? $qty[$kodeTindakanId][$obatId] : 1,
                    'dosis' => isset($dosis[$kodeTindakanId][$obatId]) ? $dosis[$kodeTindakanId][$obatId] : null,
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

    /**
     * Store a custom tindakan created from the modal (AJAX)
     */
    public function storeCustomTindakan(Request $request)
    {
        $data = $request->validate([
            'nama_tindakan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'spesialis_id' => 'nullable|exists:erm_spesialisasis,id',
            'harga' => 'nullable|numeric',
            'kode_tindakans' => 'required|array|min:1',
            'kode_tindakans.*.kode_id' => 'required|integer|exists:erm_kode_tindakan,id',
            'kode_tindakans.*.obats' => 'nullable|array',
            'kode_tindakans.*.obats.*.obat_id' => 'required|integer|exists:erm_obat,id',
            'kode_tindakans.*.obats.*.qty' => 'nullable|numeric',
            'kode_tindakans.*.obats.*.dosis' => 'nullable|string',
            'create_new_kode_for' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $tindakan = new Tindakan();
            $tindakan->nama = $data['nama_tindakan'];
            $tindakan->deskripsi = $data['deskripsi'] ?? null;
            // prefer visitation's dokter spesialis if provided via request visitation_id
            $spesialisId = $data['spesialis_id'] ?? null;
            if (isset($data['visitation_id'])) {
                $vis = Visitation::find($data['visitation_id']);
                if ($vis && $vis->dokter) {
                    $spesialisId = $vis->dokter->spesialisasi_id;
                }
            }
            $tindakan->spesialis_id = $spesialisId ?? null;
            // harga is explicitly provided by user (not auto HPP); save if present
            if (isset($data['harga'])) {
                $tindakan->harga = $data['harga'];
            }
            $tindakan->save();

            $createdKode = [];
            $totalHpp = 0;
            // create_new_kode_for can be an array of kode ids or array of objects {kode_id, new_name}
            $createNewForRaw = $data['create_new_kode_for'] ?? [];
            $createNewFor = [];
            $createNewNames = [];
            if (!empty($createNewForRaw)) {
                // normalize
                foreach ($createNewForRaw as $c) {
                    if (is_array($c) && isset($c['kode_id'])) {
                        $createNewFor[] = $c['kode_id'];
                        if (!empty($c['new_name'])) $createNewNames[$c['kode_id']] = $c['new_name'];
                    } else {
                        // maybe plain id
                        $createNewFor[] = $c;
                    }
                }
            }
            foreach ($data['kode_tindakans'] as $kodeEntry) {
                $kodeId = $kodeEntry['kode_id'];

                // decide whether to clone kode (user requested create_new_kode_for)
                $finalKodeId = $kodeId;
                if (in_array($kodeId, $createNewFor)) {
                    // clone kode tindakan and its pivot obats
                    $orig = \App\Models\ERM\KodeTindakan::with('obats')->find($kodeId);
                    if ($orig) {
                        $clone = $orig->replicate();
                        // use provided new name if available
                        $providedName = $createNewNames[$kodeId] ?? null;
                        $clone->kode = $orig->kode . '-copy-' . time();
                        $clone->nama = $providedName ?: ($orig->nama . ' (salin)');
                        $clone->created_at = now();
                        $clone->updated_at = now();
                        $clone->save();
                        // If frontend provided edited obats for this kodeEntry, use them to populate kode_tindakan_obat for the clone;
                        // otherwise copy obat pivots from original kode.
                        $providedObats = $kodeEntry['obats'] ?? null;
                        if (!empty($providedObats) && is_array($providedObats)) {
                            foreach ($providedObats as $provided) {
                                DB::table('erm_kode_tindakan_obat')->insert([
                                    'kode_tindakan_id' => $clone->id,
                                    'obat_id' => $provided['obat_id'],
                                    'qty' => $provided['qty'] ?? 1,
                                    'dosis' => $provided['dosis'] ?? null,
                                    'satuan_dosis' => $provided['satuan_dosis'] ?? null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        } else {
                            foreach ($orig->obats as $ko) {
                                $pivot = $ko->pivot;
                                DB::table('erm_kode_tindakan_obat')->insert([
                                    'kode_tindakan_id' => $clone->id,
                                    'obat_id' => $ko->id,
                                    'qty' => $pivot->qty ?? 1,
                                    'dosis' => $pivot->dosis ?? null,
                                    'satuan_dosis' => $pivot->satuan_dosis ?? null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                        $finalKodeId = $clone->id;
                    }
                }

                // attach kode to tindakan (either original or clone)
                $tindakan->kodeTindakans()->attach($finalKodeId, ['created_at' => now(), 'updated_at' => now()]);
                $createdKode[] = $finalKodeId;

                // sum kode.hpp from model (ignore frontend input)
                $kodeModel = \App\Models\ERM\KodeTindakan::find($finalKodeId);
                if ($kodeModel) {
                    $totalHpp += (float) $kodeModel->hpp;
                }

                // determine obats to attach: frontend-provided or fallback to kode's default
                $obatsToAttach = [];
                if (!empty($kodeEntry['obats']) && is_array($kodeEntry['obats'])) {
                    // normalize provided obats
                    foreach ($kodeEntry['obats'] as $ob) {
                        $obatsToAttach[] = [
                            'obat_id' => $ob['obat_id'],
                            'qty' => $ob['qty'] ?? 1,
                            'dosis' => $ob['dosis'] ?? null,
                            'satuan_dosis' => $ob['satuan_dosis'] ?? null,
                        ];
                    }
                } else {
                    $kode = \App\Models\ERM\KodeTindakan::with('obats')->find($kodeId);
                    if ($kode && $kode->obats) {
                        foreach ($kode->obats as $ko) {
                            $pivot = $ko->pivot ?? null;
                                    $obatsToAttach[] = [
                                        'obat_id' => $ko->id,
                                        'qty' => $pivot->qty ?? 1,
                                        'dosis' => $pivot->dosis ?? null,
                                        'satuan_dosis' => $pivot->satuan_dosis ?? null,
                                    ];
                        }
                    }
                }

                // Note: bundling of obat per kode is persisted on the kode side (erm_kode_tindakan_obat).
                // We intentionally do not insert qty/dosis into erm_tindakan_obat here because that table
                // in this project does not accept those columns. The relationship is: kode_tindakan -> obat,
                // and tindakan -> kode_tindakan via pivot; obat bundling should be read from kode_tindakan_obat when needed.
            }

            DB::commit();

            return response()->json(['success' => true, 'tindakan_id' => $tindakan->id, 'kode_tindakans' => $createdKode]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('storeCustomTindakan error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membuat custom tindakan'], 500);
        }
    }

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

        // Always create RiwayatTindakan
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
            $bladeView = "erm.tindakan.inform-consent.{$viewName}";
            $consentText = '';
            if (View::exists($bladeView)) {
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
            }
            // Generate PDF using the generic template
            $pdf = PDF::loadView("erm.tindakan.inform-consent.pdf.generic", [
                'data' => $data,
                'pasien' => $pasien,
                'visitation' => $visitation,
                'tindakan' => $tindakan,
                'klinik_id' => $visitation->klinik_id, // pass klinik_id for logo
                'content' => $consentText,
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
            $billingData = [
                'visitation_id' => $data['visitation_id'],
                'billable_id' => $riwayatTindakan->id,
                'billable_type' => 'App\\Models\\ERM\\RiwayatTindakan',
                'jumlah' => $tindakan->harga,
                'diskon' => $tindakan->diskon_active ? ($tindakan->harga - $tindakan->harga_diskon) : 0,
                'diskon_type' => $tindakan->diskon_active ? ($tindakan->diskon_type ?? 'nominal') : null,
                'keterangan' => 'Tindakan: ' . $tindakan->nama
            ];
            if (!empty($data['jumlah'])) {
                $billingData['jumlah'] = $data['jumlah'];
            }
            if (!empty($data['keterangan'])) {
                $billingData['keterangan'] = $data['keterangan'];
            }
            $billing = Billing::create($billingData);
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

        // Delete all related billing records for this tindakan, riwayat tindakan, and bundled obats
        // 1. Billing for tindakan
        \App\Models\Finance\Billing::where('billable_id', $riwayat->tindakan_id)
            ->where('billable_type', 'App\\Models\\ERM\\Tindakan')
            ->where('visitation_id', $riwayat->visitation_id)
            ->delete();

        // 2. Billing for riwayat tindakan
        \App\Models\Finance\Billing::where('billable_id', $riwayat->id)
            ->where('billable_type', 'App\\Models\\ERM\\RiwayatTindakan')
            ->where('visitation_id', $riwayat->visitation_id)
            ->delete();

        // 3. Billing for bundled obats
        \App\Models\Finance\Billing::where('visitation_id', $riwayat->visitation_id)
            ->where('billable_type', 'App\\Models\\ERM\\Obat')
            ->where('keterangan', 'like', '%Obat Bundled%')
            ->delete();
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
};