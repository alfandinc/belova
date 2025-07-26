<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\FollowUp;
use App\Models\ERM\Pasien;
use App\Models\HRD\Employee;
use Yajra\DataTables\DataTables;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = FollowUp::with(['pasien', 'sales']);
            return DataTables::of($data)
                ->addColumn('pasien_nama', function($row){
                    return $row->pasien ? $row->pasien->nama : '-';
                })
                ->addColumn('kategori', function($row){
                    return json_decode($row->kategori, true) ?: [];
                })
                ->addColumn('sales_nama', function($row){
                    return $row->sales ? $row->sales->nama : '-';
                })
                ->addColumn('action', function($row){
                    return '<button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'" title="Edit"><i class="fa fa-eye"></i></button> '
                        . '<button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'" title="Delete"><i class="fa fa-trash"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('marketing.followup.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'kategori' => 'required|array|min:1',
            'kategori.*' => 'string',
            'sales_id' => 'nullable|exists:hrd_employee,id',
            'status_respon' => 'required|string',
            'bukti_respon' => 'nullable|file|mimes:jpg,jpeg,png,gif',
            'rencana_tindak_lanjut' => 'nullable|string',
            'status_booking' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        $validated['kategori'] = json_encode($validated['kategori']);
        if ($request->hasFile('bukti_respon')) {
            $file = $request->file('bukti_respon');
            $path = $file->store('marketing/followup', 'public');
            $validated['bukti_respon'] = '/storage/' . $path;
        }
        $followup = FollowUp::create($validated);
        return response()->json(['success' => true, 'message' => 'Follow up berhasil ditambahkan']);
    }

    public function show($id)
    {
        $followup = FollowUp::with(['pasien', 'sales'])->findOrFail($id);
        return response()->json($followup);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'kategori' => 'required|array|min:1',
            'kategori.*' => 'string',
            'sales_id' => 'nullable|exists:hrd_employee,id',
            'status_respon' => 'required|string',
            'bukti_respon' => 'nullable|file|mimes:jpg,jpeg,png,gif',
            'rencana_tindak_lanjut' => 'nullable|string',
            'status_booking' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        $validated['kategori'] = json_encode($validated['kategori']);
        $followup = FollowUp::findOrFail($id);
        if ($request->hasFile('bukti_respon')) {
            $file = $request->file('bukti_respon');
            $path = $file->store('marketing/followup', 'public');
            $validated['bukti_respon'] = '/storage/' . $path;
        } else {
            unset($validated['bukti_respon']);
        }
        $followup->update($validated);
        return response()->json(['success' => true, 'message' => 'Follow up berhasil diupdate']);
    }

    public function destroy($id)
    {
        $followup = FollowUp::findOrFail($id);
        $followup->delete();
        return response()->json(['success' => true, 'message' => 'Follow up berhasil dihapus']);
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
