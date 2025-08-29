<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Pemasok;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PemasokExport;

class PemasokController extends Controller
{
    // Export pemasok data to Excel
    public function exportExcel()
    {
        return Excel::download(new PemasokExport, 'pemasok.xlsx');
    }
    // Display all pemasok for DataTable (AJAX)
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Pemasok::query();
            return DataTables::of($data)
                ->addColumn('aksi', function($row) {
                    $dataJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    return '<button class="btn btn-sm btn-info btn-edit" data-pemasok="'.$dataJson.'">Edit</button> '
                        .'<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Hapus</button>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
        return view('erm.pemasok.index');
    }

    // Store new pemasok
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
        ]);
        $pemasok = Pemasok::create($validated);
        return response()->json(['message' => 'Pemasok berhasil ditambahkan', 'data' => $pemasok]);
    }

    // Update pemasok
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
        ]);
        $pemasok = Pemasok::findOrFail($id);
        $pemasok->update($validated);
        return response()->json(['message' => 'Pemasok berhasil diupdate', 'data' => $pemasok]);
    }

    // Delete pemasok
    public function destroy($id)
    {
        $pemasok = Pemasok::findOrFail($id);
        $pemasok->delete();
        return response()->json(['message' => 'Pemasok berhasil dihapus']);
    }

}