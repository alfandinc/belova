<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class RawatJalanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar'])->select('erm_visitations.*');

            // Filter berdasarkan tanggal jika dikirim
            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }

            // Dapatkan user yang sedang login
            $user = Auth::user();

            // Filter berdasarkan role user yang login
            if ($user->hasRole('Perawat')) {
                $visitations->where('progress', 1);
            } elseif ($user->hasRole('Dokter')) {
                $visitations->where('progress', 2);
            }

            return datatables()->of($visitations)
                ->addColumn('no_rm', fn($v) => $v->pasien->id ?? '-') // 🆕 Tambahkan ini
                ->addColumn('nama_pasien', fn($v) => $v->pasien->nama ?? '-')
                ->addColumn('tanggal', fn($v) => $v->tanggal_visitation)
                ->addColumn('status', fn($v) => ucfirst($v->status))
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('dokumen', function ($v) {
                    // Conditionally set the route based on the role
                    $user = Auth::user();
                    if ($user->hasRole('Perawat')) {
                        $url = route('erm.asesmenperawat.create', $v->id);  // Route for Perawat
                    } elseif ($user->hasRole('Dokter')) {
                        $url = route('erm.asesmendokter.create', $v->id);  // Route for Dokter
                    } else {
                        $url = '#';  // Fallback if no role matches (optional)
                    }
                    return '<a href="' . $url . '" class="btn btn-sm btn-primary">Lihat</a>';
                })
                ->rawColumns(['dokumen'])
                ->make(true);
        }

        return view('erm.rawatjalans.index');
    }
}
