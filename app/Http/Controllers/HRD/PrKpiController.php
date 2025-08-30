<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrKpi;

class PrKpiController extends Controller
{
    public function index()
    {
        return view('hrd.payroll.kpi.index');
    }

    public function data(Request $request)
    {
        $query = PrKpi::query();
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
            'nama_poin' => 'required|string',
            'initial_poin' => 'required|numeric',
        ]);
        $row = PrKpi::create($validated);
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_poin' => 'required|string',
            'initial_poin' => 'required|numeric',
        ]);
        $row = PrKpi::findOrFail($id);
        $row->update($validated);
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function destroy($id)
    {
        $row = PrKpi::findOrFail($id);
        $row->delete();
        return response()->json(['success' => true]);
    }
}
