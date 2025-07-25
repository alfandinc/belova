<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\CatatanKeluhan;
use App\Models\ERM\Pasien;
use Yajra\DataTables\DataTables;



class CatatanKeluhanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CatatanKeluhan::with('pasien')->select('marketing_catatan_keluhan.*');
            // Date range filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $data = $data->whereBetween('visit_date', [$request->start_date, $request->end_date]);
            }
            // Perusahaan filter
            if ($request->filled('perusahaan')) {
                $data = $data->where('perusahaan', $request->perusahaan);
            }
            // Unit filter
            if ($request->filled('unit')) {
                $data = $data->where('unit', $request->unit);
            }
            // Kategori filter
            if ($request->filled('kategori')) {
                $data = $data->where('kategori', $request->kategori);
            }
            // Status filter
            if ($request->filled('status')) {
                $data = $data->where('status', $request->status);
            }
            return DataTables::of($data)
                ->addColumn('no_rm', function($row){
                    return $row->pasien ? $row->pasien->id : '-';
                })
                ->addColumn('no_hp', function($row){
                    return $row->pasien ? $row->pasien->no_hp : '-';
                })
                ->addColumn('action', function($row){
                    return '<button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'" title="Lihat/Edit"><i class="fa fa-eye"></i></button> '
                        . '<button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'" title="Hapus"><i class="fa fa-trash"></i></button>';
                })
                ->addColumn('pasien_nama', function($row){
                    return $row->pasien ? $row->pasien->nama : '-';
                })
                ->editColumn('visit_date', function($row){
                    if (!$row->visit_date) return '-';
                    $bulan = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    $date = date('Y-m-d', strtotime($row->visit_date));
                    [$y, $m, $d] = explode('-', $date);
                    $m = (int)$m;
                    $d = (int)$d;
                    return $d.' '.$bulan[$m].' '.$y;
                })
                ->editColumn('status', function($row){
                    $color = 'secondary';
                    if ($row->status === 'Diproses') $color = 'warning';
                    elseif ($row->status === 'Selesai') $color = 'success';
                    elseif ($row->status === 'Ditolak') $color = 'danger';
                    return '<span class="badge badge-' . $color . '">' . e($row->status) . '</span>';
                })
                ->rawColumns(['action', 'status', 'visit_date'])
                ->make(true);
        }
        $pasiens = Pasien::all();
        return view('marketing.catatan_keluhan.index', compact('pasiens'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'perusahaan' => 'required',
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'visit_date' => 'required|date',
            'unit' => 'required',
            'kategori' => 'required',
            'keluhan' => 'required',
            'penyelesaian' => 'nullable',
            'rencana_perbaikan' => 'nullable',
            'deadline_perbaikan' => 'nullable|date',
            'status' => 'required',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf,gif',
        ]);
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $path = $file->store('bukti-keluhan', 'public');
            $validated['bukti'] = '/storage/' . $path;
        }
        $catatan = CatatanKeluhan::create($validated);
        return response()->json(['success' => true, 'message' => 'Catatan keluhan berhasil ditambahkan']);
    }

    public function show($id)
    {
        $catatan = CatatanKeluhan::with('pasien')->findOrFail($id);
        return response()->json($catatan);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'perusahaan' => 'required',
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'visit_date' => 'required|date',
            'unit' => 'required',
            'kategori' => 'required',
            'keluhan' => 'required',
            'penyelesaian' => 'nullable',
            'rencana_perbaikan' => 'nullable',
            'deadline_perbaikan' => 'nullable|date',
            'status' => 'required',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf,gif',
        ]);
        $catatan = CatatanKeluhan::findOrFail($id);
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $path = $file->store('bukti-keluhan', 'public');
            $validated['bukti'] = '/storage/' . $path;
        } else {
            unset($validated['bukti']);
        }
        $catatan->update($validated);
        return response()->json(['success' => true, 'message' => 'Catatan keluhan berhasil diupdate']);
    }

    public function destroy($id)
    {
        $catatan = CatatanKeluhan::findOrFail($id);
        $catatan->delete();
        return response()->json(['success' => true, 'message' => 'Catatan keluhan berhasil dihapus']);
    }

    // AJAX search pasien for select2
    public function pasienSearch(Request $request)
    {
        $search = $request->input('search');
        $query = Pasien::query();
        if ($search) {
            $query->where('nama', 'like', "%$search%");
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama']);
        return response()->json($results);
    }
}
