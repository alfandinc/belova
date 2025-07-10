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
            $pdf = PDF::loadView("erm.tindakan.inform-consent.pdf.{$viewName}", [
                'data' => $data,
                'pasien' => $pasien,
                'visitation' => $visitation,
                'tindakan' => $tindakan,
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
                'billable_id' => $data['tindakan_id'],
                'billable_type' => 'App\\Models\\ERM\\Tindakan',
                'jumlah' => $tindakan->harga,
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

        return response()->json([
            'success' => true,
            'message' => 'Riwayat tindakan dan billing berhasil disimpan. Inform consent akan dibuat jika tersedia.',
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

    // SPK: Show all riwayat tindakan (index)
    public function spkIndex(Request $request)
    {
        if ($request->ajax()) {
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

            return datatables()->of($riwayat)
                ->addColumn('tanggal', function($row) {
                    // Format tanggal to '1 Januari 2025'
                    if ($row->tanggal_tindakan) {
                        try {
                            return \Carbon\Carbon::parse($row->tanggal_tindakan)
                                ->locale('id')
                                ->isoFormat('D MMMM YYYY');
                        } catch (\Exception $e) {
                            return $row->tanggal_tindakan;
                        }
                    }
                    return '-';
                })
                ->addColumn('pasien', function($row) {
                    return $row->visitation?->pasien?->nama ?? '-';
                })
                ->addColumn('tindakan', function($row) {
                    return $row->tindakan?->nama ?? '-';
                })
                ->addColumn('dokter', function($row) {
                    return $row->visitation?->dokter?->user?->name ?? '-';
                })
                ->addColumn('paket', function($row) {
                    return $row->paketTindakan?->nama ?? '-';
                })
                ->addColumn('aksi', function($row) {
                    $editBtn = '<a href="'.route('erm.spk.create', ['riwayat_id' => $row->id]).'" class="btn btn-primary btn-sm mr-1">Input/Edit SPK</a>';
                    $printBtn = '<a href="'.route('erm.spk.print', ['riwayatId' => $row->id]).'" class="btn btn-info btn-sm" target="_blank"><i class="fas fa-print"></i> Print SPK</a>';
                    return $editBtn . $printBtn;
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
        return view('erm.spk.index');
    }

    public function spkCreate(Request $request)
    {
        $riwayatId = $request->query('riwayat_id');
        $riwayat = null;
        if ($riwayatId) {
            $riwayat = RiwayatTindakan::with([
                'visitation.pasien',
                'tindakan',
                'visitation.dokter.user',
                'paketTindakan',
            ])->find($riwayatId);
        }
        return view('erm.spk.create', compact('riwayat'));
    }
    
    public function getSpkDataByRiwayat($riwayatId)
    {
        $riwayat = RiwayatTindakan::with([
            'tindakan.sop',
            'visitation.pasien',
            'visitation.dokter.user',
            'paketTindakan',
        ])->findOrFail($riwayatId);

        $spk = Spk::with('details.sop')->where('riwayat_tindakan_id', $riwayatId)->first();
        $users = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Dokter', 'Beautician']);
        })->get(['id', 'name']);

        $sopList = $riwayat->tindakan && $riwayat->tindakan->sop
            ? $riwayat->tindakan->sop->sortBy('urutan')->values()
            : collect();

        $pasienNama = $riwayat->visitation?->pasien?->nama ?? '';
        $pasienId = $riwayat->visitation?->pasien?->id ?? '';
        $tindakanNama = $riwayat->tindakan?->nama ?? '';
        $dokterNama = $riwayat->visitation?->dokter?->user?->name ?? '';
        $harga = $riwayat->tindakan?->harga ?? '';

        return response()->json([
            'success' => true,
            'data' => [
                'spk' => $spk,
                'users' => $users,
                'sop_list' => $sopList,
                'riwayat' => $riwayat,
                'pasien_nama' => $pasienNama,
                'pasien_id' => $pasienId,
                'tindakan_nama' => $tindakanNama,
                'dokter_nama' => $dokterNama,
                'harga' => $harga,
            ]
        ]);
    }

    public function saveSpk(Request $request)
    {
        $request->validate([
            'details.*.penanggung_jawab' => 'required|string',
        ]);

        $riwayatTindakan = \App\Models\ERM\RiwayatTindakan::with(['visitation', 'tindakan'])->find($request->riwayat_tindakan_id);
        if (!$riwayatTindakan) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat tindakan tidak ditemukan'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Get existing SPK or create new one
            $spk = Spk::firstOrCreate(['riwayat_tindakan_id' => $riwayatTindakan->id]);
            $spk->tanggal = now();
            $spk->inform_consent_id = $request->inform_consent_id;
            $spk->save();

            // Delete existing details if any
            $spk->details()->delete();

            // Create new details
            $details = $request->input('details', []);
            foreach ($details as $detail) {
                if (!isset($detail['sop_id'])) continue;
                
                $spk->details()->create([
                    'sop_id' => $detail['sop_id'],
                    'penanggung_jawab' => $detail['penanggung_jawab'] ?? null,
                    'sbk' => isset($detail['sbk']),
                    'sba' => isset($detail['sba']),
                    'sdc' => isset($detail['sdc']),
                    'sdk' => isset($detail['sdk']),
                    'sdl' => isset($detail['sdl']),
                    'waktu_mulai' => $detail['waktu_mulai'] ?? null,
                    'waktu_selesai' => $detail['waktu_selesai'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SPK berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan SPK: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function printSpk($riwayatId)
    {
        $riwayat = RiwayatTindakan::with([
            'paketTindakan',
            'tindakan',
            'visitation.pasien',
            'visitation.dokter.user',
            'spk.details.sop'
        ])->findOrFail($riwayatId);
        
        $users = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Dokter', 'Beautician']);
        })->get(['id', 'name']);
        
        $sopList = $riwayat->tindakan && $riwayat->tindakan->sop ? $riwayat->tindakan->sop : collect();
        
        $pasienNama = $riwayat->visitation?->pasien?->nama ?? '';
        $pasienId = $riwayat->visitation?->pasien?->id ?? '';
        $tindakanNama = $riwayat->tindakan?->nama ?? '';
        $dokterNama = $riwayat->visitation?->dokter?->user?->name ?? '';
        $harga = $riwayat->tindakan?->harga ?? '';
        $tanggalTindakan = $riwayat->created_at ? Carbon::parse($riwayat->created_at)->format('d F Y') : date('d F Y');
        
        $pdf = PDF::loadView('erm.spk.print', [
            'riwayat' => $riwayat,
            'spk' => $riwayat->spk,
            'sopList' => $sopList,
            'users' => $users,
            'pasienNama' => $pasienNama,
            'pasienId' => $pasienId,
            'tindakanNama' => $tindakanNama,
            'dokterNama' => $dokterNama,
            'harga' => $harga,
            'tanggalTindakan' => $tanggalTindakan
        ]);
        
        return $pdf->stream('SPK-' . $pasienNama . '-' . $tanggalTindakan . '.pdf');
    }
}
