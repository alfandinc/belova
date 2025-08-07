<?php
namespace App\Http\Controllers\Insiden;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Insiden\LaporanInsiden;
use App\Models\ERM\Pasien;
use App\Models\HRD\Employee;
use Yajra\DataTables\DataTables;

class LaporanInsidenController extends Controller
{
    // AJAX: Search division for Select2
    public function divisionSelect2(Request $request)
    {
        $q = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = 20;

        $query = \App\Models\HRD\Division::query();
        if ($q) {
            $query->where('name', 'like', "%$q%")
                  ->orWhere('id', 'like', "%$q%")
                  ;
        }
        $total = $query->count();
        $results = $query->orderBy('name')->skip(($page-1)*$perPage)->take($perPage)->get();

        return response()->json([
            'data' => $results->map(function($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->name,
                ];
            }),
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }
    // AJAX: Search spesialisasi for Select2
    public function spesialisasiSelect2(Request $request)
    {
        $q = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = 20;

        $query = \App\Models\ERM\Spesialisasi::query();
        if ($q) {
            $query->where('nama', 'like', "%$q%")
                  ->orWhere('id', 'like', "%$q%")
                  ;
        }
        $total = $query->count();
        $results = $query->orderBy('nama')->skip(($page-1)*$perPage)->take($perPage)->get();

        return response()->json([
            'data' => $results->map(function($s) {
                return [
                    'id' => $s->id,
                    'nama' => $s->nama,
                ];
            }),
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }
    public function index()
    {
        return view('insiden.laporan_insiden.index');
    }

    public function data()
    {
        $query = LaporanInsiden::with(['pasien', 'pembuatLaporan']);
        return DataTables::of($query)
            ->addColumn('pembuat_laporan_nama', function($row) {
                return $row->pembuatLaporan ? ($row->pembuatLaporan->name ?? $row->pembuatLaporan->nama ?? '-') : '-';
            })
            ->addColumn('action', function($row) {
                $url = route('insiden.laporan_insiden.edit', $row->id);
                return '<a href="'.$url.'" class="btn btn-sm btn-info">Edit</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $employees = Employee::all();
        return view('insiden.laporan_insiden.create', compact('employees'));
    }

    // AJAX: Search pasien for Select2
    public function searchPasien(Request $request)
    {
        $q = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = 20;

        $query = Pasien::query();
        if ($q) {
            $query->where('nama', 'like', "%$q%")
                  ->orWhere('id', 'like', "%$q%")
                  ->orWhere('nik', 'like', "%$q%")
                  ->orWhere('no_hp', 'like', "%$q%")
                  ->orWhere('alamat', 'like', "%$q%")
                  ;
        }
        $total = $query->count();
        $results = $query->orderBy('nama')->skip(($page-1)*$perPage)->take($perPage)->get();

        return response()->json([
            'data' => $results->map(function($p) {
                return [
                    'id' => $p->id,
                    'nama' => $p->nama . ' (' . $p->id . ')',
                    'tanggal_lahir' => $p->tanggal_lahir,
                    'gender' => $p->gender,
                ];
            }),
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    public function upsert(Request $request, $id = null)
    {
        $validated = $request->validate([
            'pasien_id' => 'required',
            'penanggung_biaya' => 'nullable|string',
            'tanggal_masuk' => 'nullable|date',
            'tanggal_insiden' => 'required|date',
            'insiden' => 'required|string',
            'kronologi_insiden' => 'nullable|string',
            'jenis_insiden' => 'nullable|string',
            'pertama_lapor' => 'nullable|string',
            'insiden_pada' => 'nullable|string',
            'jenis_pasien' => 'nullable|string',
            'lokasi_insiden' => 'nullable|string',
            'spesialisasi_id' => 'nullable|integer',
            'unit_penyebab' => 'nullable|integer',
            'akibat_insiden' => 'nullable|string',
            'tindakan_dilakukan' => 'nullable|string',
            'tindakan_oleh' => 'nullable|string',
            'pernah_terjadi' => 'nullable|boolean',
            'langkah_diambil' => 'nullable|string',
            'pencegahan' => 'nullable|string',
            'penerima_laporan' => 'nullable|integer',
            'tanggal_lapor' => 'nullable|date',
            'tanggal_diterima' => 'nullable|date',
            'grading_resiko' => 'nullable|string',
        ]);
        $validated['pembuat_laporan'] = auth()->id();
        $laporan = LaporanInsiden::updateOrCreate(
            ['id' => $id],
            $validated
        );
        return response()->json(['success' => true, 'data' => $laporan]);
    }
        public function edit($id)
    {
        $laporan = LaporanInsiden::findOrFail($id);
        return view('insiden.laporan_insiden.create', compact('laporan'));
    }

}
