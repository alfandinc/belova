<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Cache;


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
                    $dokumenUrl = route('erm.asesmendokter.create', ['visitation' => $row->id]);

                    return '
                        <a href="#" class="btn btn-sm btn-primary">Resume</a>
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
}
