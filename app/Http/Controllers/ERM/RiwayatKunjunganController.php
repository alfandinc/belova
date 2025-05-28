<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class RiwayatKunjunganController extends Controller
{
    public function index(Request $request, $pasien)
    {

        $visitation = Visitation::where('pasien_id', $pasien)->latest()->first();
        $visitId = $visitation->id;
        $pasienData = Cache::remember("pasien_data_{$visitId}", 60, function () use ($visitId) {
            return PasienHelperController::getDataPasien($visitId);
        });
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitId);

        // \Log::info('Masuk controller riwayatkunjungan');
        if ($request->ajax()) {
            $kunjungan = Visitation::with(['metodeBayar:id,nama', 'dokter.user:id,name', 'dokter.spesialisasi:id,nama'])
                ->select('erm_visitations.*') // ini penting!
                ->where('pasien_id', $pasien)
                ->orderBy('erm_visitations.created_at', 'desc');

            return DataTables::of($kunjungan)
                ->addIndexColumn()
                ->addColumn('metode', fn($row) => $row->metodeBayar->nama ?? '-')
                ->addColumn('spesialisasi', function ($row) {
                    return optional(optional($row->dokter)->spesialisasi)->nama ?? '-';
                })
                ->addColumn('dokter', function ($row) {
                    return optional(optional($row->dokter)->user)->name ?? '-';
                })
                ->editColumn('tanggal_visitation', function ($row) {
                    return \Carbon\Carbon::parse($row->tanggal_visitation)->translatedFormat('d F Y');
                })
                // ->editColumn('created_at', fn($row) => $row->created_at->translatedFormat('d F Y'))
                ->addColumn('aksi', function ($row) {
                    // $resumeUrl = route('resume.show', $row->id);   // Ubah sesuai kebutuhan
                    $resumeUrl = route('resume.medis', $row->id);
                    $dokumenUrl = route('erm.asesmendokter.create', ['visitation' => $row->id]);

                    return '
                        <a href="' . $resumeUrl . '" class="btn btn-sm btn-primary" target="_blank">Resume</a>
                         <a href="' . $dokumenUrl . '" class="btn btn-sm btn-secondary" target="_blank">Dokumen</a>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }



        return view('erm.riwayatkunjungan.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
        ], $pasienData, $createKunjunganData));
    }

    public function resumeMedis($visitationId)
    {
        $visitation = Visitation::with(['dokter.user', 'dokter.spesialisasi'])->findOrFail($visitationId);

        $pasien = PasienHelperController::getDataPasien($visitationId);

        // Debug the $pasien data
        // dd($visitation);
        $diagnosisList = array_filter([
            $visitation->asesmenPenunjang->diagnosakerja_1 ?? '',
            $visitation->asesmenPenunjang->diagnosakerja_2 ?? '',
            $visitation->asesmenPenunjang->diagnosakerja_3 ?? '',
            $visitation->asesmenPenunjang->diagnosakerja_4 ?? '',
            $visitation->asesmenPenunjang->diagnosakerja_5 ?? '',
        ]);

        $obatNames = $visitation->resepDokter()->with('obat')->get()->map(function ($resep) {
            return $resep->obat->nama ?? 'Unknown';
        })->toArray();


        $data = [
            'visitation_id' => $visitation->id,
            'tanggal_visit' => $visitation->tanggal_visitation,
            'nama_dokter' => $visitation->dokter->user->name,
            'spesialisasi' => $visitation->dokter->spesialisasi->nama ?? '-',
            'pasien' => $pasien['pasien'],
            'keluhan_utama' => $visitation->asesmenDalam->keluhan_utama,
            'keadaan_umum' => $visitation->asesmenDalam->keadaan_umum,
            'n' => $visitation->asesmenDalam->n,
            'td' => $visitation->asesmenDalam->td,
            'r' => $visitation->asesmenDalam->r,
            's' => $visitation->asesmenDalam->s,
            'nama_obat' => implode(', ', $obatNames),
            'diagnosis' => implode(', ', $diagnosisList),
            'tindak_lanjut' => $visitation->asesmenPenunjang->standing_order ?? '',

        ];

        // dd($data);

        $pdf = PDF::loadView('erm.riwayatkunjungan.resume-medis', $data);

        return $pdf->stream('resume-medis.pdf');
    }
}
