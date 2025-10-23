<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Principal;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PrincipalExport;

class PrincipalController extends Controller
{
    // Export principal data to Excel
    public function exportExcel()
    {
        return Excel::download(new PrincipalExport, 'principal.xlsx');
    }

    // Display all principal for DataTable (AJAX)
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Principal::query();
            return DataTables::of($data)
                ->addColumn('aksi', function($row) {
                    $dataJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    return '<button class="btn btn-sm btn-info btn-edit" data-principal="'.$dataJson.'">Edit</button> '
                        .'<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Hapus</button>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
        return view('erm.principal.index');
    }

    // Store new principal
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
        ]);
        $principal = Principal::create($validated);
        return response()->json(['message' => 'Principal berhasil ditambahkan', 'data' => $principal]);
    }

    // Update principal
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
        ]);
        $principal = Principal::findOrFail($id);
        $principal->update($validated);
        return response()->json(['message' => 'Principal berhasil diupdate', 'data' => $principal]);
    }

    // Delete principal
    public function destroy($id)
    {
        $principal = Principal::findOrFail($id);
        $principal->delete();
        return response()->json(['message' => 'Principal berhasil dihapus']);
    }

}
