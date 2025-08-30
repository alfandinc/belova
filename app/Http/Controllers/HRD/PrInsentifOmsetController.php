<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrInsentifOmset;

class PrInsentifOmsetController extends Controller
{
    public function index()
    {
        return view('hrd.payroll.insentif_omset.index');
    }

    public function data(Request $request)
    {
            $query = PrInsentifOmset::query();
            return datatables()->of($query)
                ->addColumn('aksi', function($row) {
                    return '<button class="btn btn-sm btn-warning btn-edit">Edit</button> <button class="btn btn-sm btn-danger btn-delete">Delete</button>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_penghasil' => 'required|string',
            'omset_min' => 'required|numeric',
            'omset_max' => 'required|numeric',
            'insentif_normal' => 'required|numeric',
            'insentif_up' => 'required|numeric',
        ]);
        $row = PrInsentifOmset::create($validated);
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_penghasil' => 'required|string',
            'omset_min' => 'required|numeric',
            'omset_max' => 'required|numeric',
            'insentif_normal' => 'required|numeric',
            'insentif_up' => 'required|numeric',
        ]);
        $row = PrInsentifOmset::findOrFail($id);
        $row->update($validated);
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function destroy($id)
    {
        $row = PrInsentifOmset::findOrFail($id);
        $row->delete();
        return response()->json(['success' => true]);
    }
}
