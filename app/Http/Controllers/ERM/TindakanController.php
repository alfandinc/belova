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

use Carbon\Carbon;


class TindakanController extends Controller
{
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
        $tindakan = Tindakan::where('spesialis_id', $spesialisasiId)->get();

        return datatables()->of($tindakan)
            ->addColumn('action', function ($row) {
                return '
                <button class="btn btn-success btn-sm buat-tindakan" data-id="' . $row->id . '" data-type="tindakan">Buat Tindakan</button>
                <a href="' . route('erm.tindakan.sop', $row->id) . '" class="btn btn-info btn-sm" target="_blank">SOP</a>
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
        $data = $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'tindakan_id' => 'required|exists:erm_tindakan,id',
            'tanggal' => 'required|date',
            'signature' => 'required',
            'witness_signature' => 'required',
            'notes' => 'nullable|string',
            'nama_pasien' => 'required|string',
            'nama_saksi' => 'required|string',
            'paket_id' => 'nullable|exists:erm_paket_tindakan,id',
        ]);

        $visitation = Visitation::findOrFail($data['visitation_id']);
        $pasien = $visitation->pasien;
        $tindakan = Tindakan::findOrFail($data['tindakan_id']);

        // Create RiwayatTindakan record first
        $riwayatTindakan = RiwayatTindakan::create([
            'visitation_id' => $data['visitation_id'],
            'tanggal_tindakan' => $data['tanggal'],
            'tindakan_id' => $data['tindakan_id'],
            'paket_tindakan_id' => $data['paket_id'] ?? null,
        ]);

        $viewName = strtolower(str_replace(' ', '_', $tindakan->nama));
        $pdf = PDF::loadView("erm.tindakan.inform-consent.pdf.{$viewName}", [
            'data' => $data,
            'pasien' => $pasien,
            'visitation' => $visitation,
            'tindakan' => $tindakan,
        ]);

        $timestamp = now()->format('YmdHis');
        $pdfPath = 'inform-consent/' . $pasien->id . '-' . $visitation->id . '-' . $tindakan->id . '-' . $timestamp . '.pdf';
        // Storage::put('public/' . $pdfPath, $pdf->output());
        Storage::disk('public')->put($pdfPath, $pdf->output());
        $informConsent = InformConsent::create([
            'visitation_id' => $data['visitation_id'],
            'tindakan_id' => $data['tindakan_id'],
            'file_path' => $pdfPath,
            'paket_id' => $data['paket_id'] ?? null,
            'riwayat_tindakan_id' => $riwayatTindakan->id,
            'created_at' => now(),
        ]);

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
            // This is a paket tindakan
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
                    'jumlah' => !empty($data['jumlah']) ? $data['jumlah'] : $paketTindakan->harga_paket,
                    'keterangan' => !empty($data['keterangan']) ? $data['keterangan'] : 'Paket Tindakan: ' . $paketTindakan->nama
                ];

                $billing = Billing::create($billingData);
            } else {
                $billing = $existingBilling;
            }
        } else {
            // This is a single tindakan
            $billingData = [
                'visitation_id' => $data['visitation_id'],
                'billable_id' => $data['tindakan_id'],
                'billable_type' => 'App\\Models\\ERM\\Tindakan',
                'jumlah' => $tindakan->harga,
                'keterangan' => 'Tindakan: ' . $tindakan->nama
            ];

            // Override with request data if provided
            if (!empty($data['jumlah'])) {
                $billingData['jumlah'] = $data['jumlah'];
            }

            if (!empty($data['keterangan'])) {
                $billingData['keterangan'] = $data['keterangan'];
            }

            $billing = Billing::create($billingData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inform consent, SPK, and billing created successfully',
            'informConsent' => $informConsent,
            'spk' => $spk,
            'billing' => $billing,
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
                // Format the date using Carbon
                $tanggalFormatted = '-';
                if ($item->tanggal_tindakan) {
                    $tanggalFormatted = Carbon::parse($item->tanggal_tindakan)
                        ->locale('id')
                        ->isoFormat('D MMMM YYYY');
                }

                return [
                    'id' => $item->id,
                    'tanggal' => $tanggalFormatted,
                    'tindakan' => $item->tindakan->nama ?? '-',
                    'paket' => $item->paketTindakan->nama ?? '-',
                    'dokter' => $item->visitation->dokter->user->name ?? '-',
                    'spesialisasi' => $item->visitation->dokter->spesialisasi->nama ?? '-',
                    'inform_consent' => $item->informConsent,
                    'current' => ($item->visitation_id == $visitationId) ? true : false
                ];
            });

        return datatables()->of($history)
            ->addColumn('dokumen', function ($row) {
                $buttons = '';
                
                // Add document button if inform consent exists
                if ($row['inform_consent']) {
                    $url = Storage::url($row['inform_consent']->file_path);
                    $buttons .= '<a href="' . $url . '" target="_blank" class="btn btn-info btn-sm mr-1">Inform Consent</a>';
                    
                    // Always add foto hasil button
                    $buttons .= '<button class="btn btn-primary btn-sm foto-hasil-btn mr-1" ' .
                        'data-id="' . $row['inform_consent']->id . '" ' .
                        'data-before="' . ($row['inform_consent']->before_image_path ?? '') . '" ' .
                        'data-after="' . ($row['inform_consent']->after_image_path ?? '') . '">' .
                        'Foto Hasil</button>';
                    
                    // Add SPK button
                    $buttons .= '<button class="btn btn-warning btn-sm spk-btn" ' .
                        'data-id="' . $row['inform_consent']->id . '">' .
                        'SPK</button>';
                } else {
                    $buttons = '<span class="text-muted">Belum ada inform consent</span>';
                }
                
                return $buttons;
            })
            ->addColumn('status', function ($row) {
                return $row['current'] ?
                    '<span class="badge badge-success">Kunjungan Saat Ini</span>' :
                    '<span class="badge badge-secondary">Kunjungan Sebelumnya</span>';
            })
            ->rawColumns(['dokumen', 'status'])
            ->make(true);
    }

    public function generateSopPdf($id)
    {
        $tindakan = Tindakan::with('sop')->findOrFail($id);

        // If SOP doesn't exist, return a message
        if ($tindakan->sop->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'SOP belum tersedia untuk tindakan ini'
            ]);
        }

        $pdf = PDF::loadView('erm.tindakan.sop.pdf', [
            'tindakan' => $tindakan,
            'sopList' => $tindakan->sop->sortBy('urutan')
        ]);

        $filename = 'SOP-' . str_replace(' ', '-', $tindakan->nama) . '.pdf';

        return $pdf->stream($filename);
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

        $informConsent->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto hasil berhasil diupload',
            'before_path' => $informConsent->before_image_path ? Storage::url($informConsent->before_image_path) : null,
            'after_path' => $informConsent->after_image_path ? Storage::url($informConsent->after_image_path) : null,
        ]);
    }

    public function getSpkData($informConsentId)
    {
        $informConsent = InformConsent::with(['tindakan.sop', 'visitation.pasien', 'visitation.dokter.user'])
            ->findOrFail($informConsentId);
        
        $spk = \App\Models\ERM\Spk::with('details.sop')->where('visitation_id', $informConsent->visitation_id)->first();
        
        // Get users with Dokter and Beautician roles
        $users = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Dokter', 'Beautician']);
        })->get(['id', 'name']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'inform_consent' => $informConsent,
                'spk' => $spk,
                'users' => $users,
                'sop_list' => $informConsent->tindakan->sop ?? []
            ]
        ]);
    }
    
    public function saveSpk(Request $request)
    {
        Log::info('SPK Save Request Data: ', $request->all());
        $informConsent = null;
        $riwayatTindakan = null;
        // Accept either inform_consent_id or riwayat_tindakan_id
        if ($request->has('inform_consent_id') && $request->inform_consent_id) {
            $informConsent = \App\Models\ERM\InformConsent::with(['visitation', 'tindakan', 'riwayatTindakan'])->find($request->inform_consent_id);
            $riwayatTindakan = $informConsent ? $informConsent->riwayatTindakan : null;
        } elseif ($request->has('riwayat_tindakan_id') && $request->riwayat_tindakan_id) {
            $riwayatTindakan = \App\Models\ERM\RiwayatTindakan::with(['visitation', 'tindakan'])->find($request->riwayat_tindakan_id);
        }
        if (!$riwayatTindakan) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat Tindakan tidak ditemukan.'
            ], 422);
        }
        try {
            $request->validate([
                'tanggal_tindakan' => 'required|date',
                'details' => 'required|array',
                'details.*.sop_id' => 'required|exists:erm_sop,id',
                'details.*.penanggung_jawab' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('SPK Validation Error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors())
            ], 422);
        }
        try {
            DB::beginTransaction();
            // Find or create SPK by riwayat_tindakan_id
            $spk = \App\Models\ERM\Spk::where('riwayat_tindakan_id', $riwayatTindakan->id)->first();
            if (!$spk) {
                $spk = \App\Models\ERM\Spk::create([
                    'visitation_id' => $riwayatTindakan->visitation_id,
                    'pasien_id' => $riwayatTindakan->visitation->pasien_id,
                    'tindakan_id' => $riwayatTindakan->tindakan_id,
                    'dokter_id' => $riwayatTindakan->visitation->dokter_id,
                    'tanggal_tindakan' => $request->tanggal_tindakan,
                    'riwayat_tindakan_id' => $riwayatTindakan->id,
                ]);
            } else {
                $spk->update([
                    'tanggal_tindakan' => $request->tanggal_tindakan,
                ]);
            }
            // Delete existing details
            $spk->details()->delete();
            // Create new details
            foreach ($request->details as $detail) {
                \App\Models\ERM\SpkDetail::create([
                    'spk_id' => $spk->id,
                    'sop_id' => $detail['sop_id'],
                    'penanggung_jawab' => $detail['penanggung_jawab'],
                    'sbk' => filter_var($detail['sbk'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sba' => filter_var($detail['sba'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sdc' => filter_var($detail['sdc'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sdk' => filter_var($detail['sdk'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sdl' => filter_var($detail['sdl'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'waktu_mulai' => !empty($detail['waktu_mulai']) ? $detail['waktu_mulai'] : null,
                    'waktu_selesai' => !empty($detail['waktu_selesai']) ? $detail['waktu_selesai'] : null,
                    'notes' => !empty($detail['notes']) ? $detail['notes'] : null,
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SPK berhasil disimpan',
                'spk' => $spk->load('details.sop')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('SPK Save Error: ' . $e->getMessage());
            Log::error('SPK Save Trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan SPK: ' . $e->getMessage()
            ], 500);
        }
    }

    // SPK: Show all riwayat tindakan (index)
    public function spkIndex()
    {
        return view('erm.spk.index');
    }

    // SPK: Data for DataTables (all riwayat tindakan)
    public function spkRiwayatData(Request $request)
    {
        $riwayat = \App\Models\ERM\RiwayatTindakan::with(['visitation.pasien', 'tindakan', 'visitation.dokter.user', 'paketTindakan']);
        return datatables()->of($riwayat)
            ->addColumn('tanggal', function($row) {
                return $row->tanggal_tindakan;
            })
            ->addColumn('pasien', function($row) {
                return $row->visitation && $row->visitation->pasien ? $row->visitation->pasien->nama : '-';
            })
            ->addColumn('tindakan', function($row) {
                return $row->tindakan ? $row->tindakan->nama : '-';
            })
            ->addColumn('dokter', function($row) {
                return $row->visitation && $row->visitation->dokter && $row->visitation->dokter->user ? $row->visitation->dokter->user->name : '-';
            })
            ->addColumn('paket', function($row) {
                return $row->paketTindakan ? $row->paketTindakan->nama : '-';
            })
            ->addColumn('aksi', function($row) {
                return '<a href="'.route('erm.spk.create', ['riwayat_id' => $row->id]).'" class="btn btn-primary btn-sm">Input/Edit SPK</a>';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    // SPK: Show create/edit form for SPK (pass informConsentId to view)
    public function spkCreate(Request $request)
    {
        $riwayatId = $request->query('riwayat_id');
        $riwayat = null;
        $informConsent = null;
        if ($riwayatId) {
            $riwayat = \App\Models\ERM\RiwayatTindakan::with(['visitation.pasien', 'tindakan', 'visitation.dokter.user', 'paketTindakan', 'informConsent'])->find($riwayatId);
            // Get the latest inform consent for this riwayat
            $informConsent = $riwayat && $riwayat->informConsent ? $riwayat->informConsent : \App\Models\ERM\InformConsent::where('riwayat_tindakan_id', $riwayatId)->latest()->first();
        }
        $informConsentId = $informConsent ? $informConsent->id : null;
        return view('erm.spk.create', compact('riwayat', 'informConsentId'));
    }

    // SPK: Store SPK data
    public function spkStore(Request $request)
    {
        // Implement your SPK saving logic here
        // Example: validate and save to Spk and SpkDetail models
        // ...
        return redirect()->route('erm.spk.index')->with('success', 'SPK berhasil disimpan!');
    }

    // Get SPK data by RiwayatTindakan ID (for SPK create page)
    public function getSpkDataByRiwayat($riwayatId)
    {
        // Find the riwayat and its inform consent (1:1)
        $riwayat = \App\Models\ERM\RiwayatTindakan::with([
            'tindakan.sop',
            'visitation.pasien',
            'visitation.dokter.user',
            'paketTindakan',
            'informConsent',
        ])->findOrFail($riwayatId);
        $informConsent = $riwayat->informConsent;
        // If not found, try to get the latest by riwayat_tindakan_id
        if (!$informConsent) {
            $informConsent = \App\Models\ERM\InformConsent::where('riwayat_tindakan_id', $riwayatId)->latest()->first();
        }
        // Use the same logic as the modal: fetch SOPs from tindakan, users, and SPK by riwayat_tindakan_id
        $spk = \App\Models\ERM\Spk::with('details.sop')->where('riwayat_tindakan_id', $riwayatId)->first();
        $users = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Dokter', 'Beautician']);
        })->get(['id', 'name']);
        $sopList = collect();
        if ($riwayat->tindakan && $riwayat->tindakan->sop && $riwayat->tindakan->sop->count()) {
            $sopList = $riwayat->tindakan->sop->sortBy('urutan')->values();
        } else if ($riwayat->tindakan_id) {
            $tindakan = \App\Models\ERM\Tindakan::with('sop')->find($riwayat->tindakan_id);
            if ($tindakan && $tindakan->sop) {
                $sopList = $tindakan->sop->sortBy('urutan')->values();
            }
        }
        return response()->json([
            'success' => true,
            'data' => [
                'inform_consent' => $informConsent,
                'spk' => $spk,
                'users' => $users,
                'sop_list' => $sopList,
            ]
        ]);
    }
}
