<?php

namespace App\Http\Controllers\Satusehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Carbon\Carbon;

class PasienController extends Controller
{
    public function index()
    {
        return view('satusehat.pasiens.index');
    }

    public function data(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $query = Visitation::with(['pasien', 'dokter', 'klinik'])
            ->whereDate('tanggal_visitation', $today)
            ->orderBy('waktu_kunjungan', 'asc');

        $rows = $query->get()->map(function ($v) {
            return [
                'id' => $v->id,
                'tanggal_visitation' => $v->tanggal_visitation,
                'waktu_kunjungan' => $v->waktu_kunjungan,
                'no_antrian' => $v->no_antrian,
                'pasien' => $v->pasien->nama ?? null,
                'dokter' => $v->dokter->nama ?? null,
                'klinik' => $v->klinik->nama ?? $v->klinik->name ?? null,
                'status_kunjungan' => $v->status_kunjungan,
            ];
        });

        return response()->json(['data' => $rows]);
    }
}
