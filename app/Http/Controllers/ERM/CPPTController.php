<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\Visitation;
use Illuminate\Http\Request;
use App\Models\ERM\Cppt;

class CPPTController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        // Step 1: Get pasien_id
        $visitationIds = Visitation::where('pasien_id', $visitation->pasien_id)->pluck('id');

        // 2) Eager‑load the user AND their roles
        $cpptList = Cppt::with('user.roles')
            ->whereIn('visitation_id', $visitationIds)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3) Format the date for JS
        $cpptList->each(function ($cppt) {
            $cppt->formatted_date = $cppt->created_at
                ->translatedFormat('d M Y H:i');
        });;


        return view('erm.cppt.create', array_merge([
            'visitation' => $visitation,
            'cpptList' => $cpptList,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        $visitation = Visitation::findOrFail($request->visitation_id);

        // Update status_dokumen & progress berdasarkan role
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->hasRole('Perawat')) {
                $visitation->progress = 2;
                $visitation->status_dokumen = 'cppt';
            } elseif ($user->hasRole('Dokter')) {
                $visitation->progress = 3;
                $visitation->status_dokumen = 'cppt';
            }

            $visitation->save();
        }

        // Siapkan data
        $data = $request->all();
        $data['user_id'] = auth()->id();

        // Buat atau update CPPT hanya berdasarkan visitation_id + user_id + jenis_dokumen
        $cppt = Cppt::updateOrCreate(
            [
                'visitation_id' => $data['visitation_id'],
                'user_id' => $data['user_id'],
                'jenis_dokumen' => $data['jenis_dokumen'],
            ],
            $data
        );

        return response()->json([
            'message' => 'CPPT berhasil disimpan.',
            'data'    => $cppt,
        ]);
    }


    public function historyJson($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $pasienId = $visitation->pasien_id;

        // get all visitation ids for the same patient
        $visitationIds = Visitation::where('pasien_id', $pasienId)->pluck('id');

        $cpptList = Cppt::with('user')
            ->whereIn('visitation_id', $visitationIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $cpptList->each(function ($cppt) {
            $cppt->formatted_date = \Carbon\Carbon::parse($cppt->created_at)->translatedFormat('d M Y H:i');
        });

        return response()->json($cpptList);
    }
}
