<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\Visitation;
use Illuminate\Http\Request;
use App\Models\ERM\Cppt;
use Illuminate\Support\Facades\Auth;

class CPPTController extends Controller
{
    // Update CPPT entry
    public function update(Request $request, $id)
    {
    $cppt = Cppt::findOrFail($id);
    $cppt->s = $request->input('s');
    $cppt->o = $request->input('o');
    $cppt->a = $request->input('a');
    $cppt->p = $request->input('p');
    $cppt->save();
    return response()->json(['message' => 'CPPT berhasil diupdate.', 'data' => $cppt]);
    }
    // Mark all CPPTs for a visitation/patient as read
    public function markAllAsRead($visitationId)
    {
        $visitation = \App\Models\ERM\Visitation::findOrFail($visitationId);
        $pasienId = $visitation->pasien_id;
        $visitationIds = \App\Models\ERM\Visitation::where('pasien_id', $pasienId)->pluck('id');
        $cpptList = Cppt::whereIn('visitation_id', $visitationIds)->get();
        $count = 0;
        foreach ($cpptList as $cppt) {
            if ($cppt->dibaca !== Auth::id()) {
                $cppt->update([
                    'dibaca' => Auth::id(),
                    'waktu_baca' => now()
                ]);
                $count++;
            }
        }
        return response()->json([
            'message' => "Ditandai sudah dibaca: $count catatan.",
            'count' => $count
        ]);
    }
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        // Step 1: Get pasien_id
        $visitationIds = Visitation::where('pasien_id', $visitation->pasien_id)->pluck('id');

        // 2) Eager‑load the user AND their roles, plus reader
        $cpptList = Cppt::with(['user.roles', 'reader'])
            ->whereIn('visitation_id', $visitationIds)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3) Format the date for JS
            $cpptList->each(function ($cppt) {
                $cppt->formatted_date = $cppt->created_at
                    ? $cppt->created_at->translatedFormat('d M Y H:i')
                    : '';
        });

        // Get konsultasi list for select
        $jenisKonsultasi = \App\Models\ERM\Konsultasi::get();

        return view('erm.cppt.create', array_merge([
            'visitation' => $visitation,
            'cpptList' => $cpptList,
            'jenisKonsultasi' => $jenisKonsultasi,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        $visitation = Visitation::findOrFail($request->visitation_id);

        // Update status_dokumen & progress berdasarkan role
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->hasRole('Perawat')) {
                $visitation->status_kunjungan = 1;
                $visitation->status_dokumen = 'cppt';
            } elseif ($user->hasRole('Dokter')) {
                $visitation->status_kunjungan = 2;
                $visitation->status_dokumen = 'cppt';
            }

            $visitation->save();
        }

        // Siapkan data
        $data = $request->all();
        $data['user_id'] = Auth::id();

        // Buat atau update CPPT hanya berdasarkan visitation_id + user_id + jenis_dokumen
        $cppt = Cppt::updateOrCreate(
            [
                'visitation_id' => $data['visitation_id'],
                'user_id' => $data['user_id'],
                'jenis_dokumen' => $data['jenis_dokumen'],
            ],
            $data
        );

        // Store konsultasi billing if selected
        if ($request->filled('jenis_konsultasi')) {
            $this->storeJenisKonsultasi($request);
        }

        return response()->json([
            'message' => 'CPPT berhasil disimpan.',
            'data'    => $cppt,
        ]);
    }

    // Copy-paste from AsesmenController, but simplified
    private function storeJenisKonsultasi(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required',
            'jenis_konsultasi' => 'required|exists:erm_konsultasi,id',
        ]);

        $visitationId = $request->visitation_id;
        $jenis_konsultasi = $request->jenis_konsultasi;

        $jasa = \App\Models\ERM\Konsultasi::findOrFail($jenis_konsultasi);

        $existing = \App\Models\Finance\Billing::where('visitation_id', $visitationId)
            ->where('billable_id', $jasa->id)
            ->where('billable_type', \App\Models\ERM\Konsultasi::class)
            ->first();

        if (!$existing) {
            \App\Models\Finance\Billing::create([
                'visitation_id' => $visitationId,
                'billable_id' => $jasa->id,
                'billable_type' => \App\Models\ERM\Konsultasi::class,
                'keterangan' => 'Tindakan: ' . $jasa->nama,
                'jumlah' => $jasa->harga,
            ]);
        }
    }


    public function historyJson($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $pasienId = $visitation->pasien_id;

        // get all visitation ids for the same patient
        $visitationIds = Visitation::where('pasien_id', $pasienId)->pluck('id');

        $cpptList = Cppt::with(['user.roles', 'reader'])
            ->whereIn('visitation_id', $visitationIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $cpptList->each(function ($cppt) {
            $cppt->formatted_date = \Carbon\Carbon::parse($cppt->created_at)->translatedFormat('d M Y H:i');
        });

        return response()->json($cpptList);
    }

    public function markAsRead($id)
    {
        $cppt = Cppt::findOrFail($id);
        
        $cppt->update([
            'dibaca' => Auth::id(),
            'waktu_baca' => now()
        ]);

        return response()->json([
            'message' => 'CPPT berhasil ditandai sudah dibaca.',
            'reader_name' => Auth::user()->name,
            'waktu_baca' => now()->translatedFormat('d M Y H:i')
        ]);
    }
}
