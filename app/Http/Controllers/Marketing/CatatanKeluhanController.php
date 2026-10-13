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
            // Status filter; "open" = not yet Selesai / Ditolak
            if ($request->status === 'open') {
                $data = $data->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', CatatanKeluhan::CLOSED_STATUSES));
            } elseif ($request->filled('status')) {
                $data = $data->where('status', $request->status);
            }
            // One patient (link from Pasien & Follow Up)
            if ($request->filled('pasien_id')) {
                $data = $data->where('pasien_id', $request->pasien_id);
            }
            return DataTables::of($data)
                ->addColumn('no_rm', function($row){
                    return $row->pasien ? $row->pasien->id : '-';
                })
                ->addColumn('no_hp', function($row){
                    return $row->pasien ? $row->pasien->no_hp : '-';
                })
                ->addColumn('pasien_nama', function($row){
                    return $row->pasien ? $row->pasien->nama : '-';
                })
                ->addColumn('action', function($row){
                    return '<button class="btn btn-sm btn-outline-primary editBtn" data-id="'.$row->id.'"><i class="fa fa-pen mr-1"></i>Edit</button> '
                        . '<button class="btn btn-sm btn-outline-info printBtn" data-id="'.$row->id.'"><i class="fa fa-print mr-1"></i>Print</button> '
                        . '<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="'.$row->id.'"><i class="fa fa-trash mr-1"></i>Hapus</button>';
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
        // Opened from a patient's "keluhan terbuka" badge
        $filterPasien = $request->filled('pasien_id') ? Pasien::find($request->pasien_id, ['id', 'nama']) : null;
        return view('marketing.catatan_keluhan.index', [
            'filterPasien' => $filterPasien,
            'filterStatus' => $request->query('status'),
        ]);
    }

    // Print PDF laporan catatan keluhan
    public function print($id)
    {
        $catatan = CatatanKeluhan::with('pasien')->findOrFail($id);
        // Determine header/footer images
        $headerImg = '';
        $footerImg = '';
        if (stripos($catatan->perusahaan, 'Pratama') !== false) {
            $headerImg = public_path('img/belova-header.png');
            $footerImg = public_path('img/belova-footer.png');
        } elseif (stripos($catatan->perusahaan, 'Premire') !== false || stripos($catatan->perusahaan, 'Premiere') !== false) {
            $headerImg = public_path('img/premiere-header.png');
            $footerImg = public_path('img/premiere-footer.png');
        }

        $mainContent = view('marketing.catatan_keluhan.print', compact('catatan'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        // Absolutely position header/footer images to be flush with page edges
        if ($headerImg) {
            $mpdf->SetHTMLHeader('<div style="position:absolute;top:0;left:0;width:100%;height:40mm;z-index:10;"><img src="' . $headerImg . '" style="width:100%;height:40mm;object-fit:cover;display:block;"></div>', 'O');
        }
        if ($footerImg) {
            $mpdf->SetHTMLFooter('<div style="position:absolute;bottom:0;left:0;width:100%;height:40mm;z-index:10;"><img src="' . $footerImg . '" style="width:100%;height:40mm;object-fit:cover;display:block;"></div>', 'O');
        }

        // Add left/right margin to content, keep header/footer edge-to-edge
        $mpdf->WriteHTML('<div style="padding-top:40mm;padding-bottom:40mm;padding-left:15mm;padding-right:15mm;">' . $mainContent . '</div>');
        return response($mpdf->Output('', 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="catatan-keluhan-' . $catatan->id . '.pdf"');
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
        // By name or No RM
        $search = $request->input('search');
        $query = Pasien::query();
        if ($search) {
            $query->where(fn ($q) => $q->where('nama', 'like', "%$search%")->orWhere('id', 'like', "$search%"));
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama', 'no_hp']);
        return response()->json($results);
    }
}
