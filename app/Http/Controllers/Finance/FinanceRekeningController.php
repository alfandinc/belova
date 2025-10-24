<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\FinanceRekening;
use Yajra\DataTables\DataTables;

class FinanceRekeningController extends Controller
{
    public function index()
    {
        return view('finance.rekening.index');
    }

    public function data()
    {
        $query = FinanceRekening::query();
        return DataTables::of($query)
            ->addColumn('actions', function($row) {
                $btn = '<button class="btn btn-sm btn-primary edit-rekening" data-id="'.$row->id.'">Edit</button>';
                $btn .= ' <button class="btn btn-sm btn-danger delete-rekening" data-id="'.$row->id.'">Delete</button>';
                return $btn;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank' => 'nullable|string',
            'no_rekening' => 'nullable|string',
            'atas_nama' => 'nullable|string',
        ]);
        $rec = FinanceRekening::create($data);
        return response()->json(['success' => true, 'data' => $rec]);
    }

    public function show($id)
    {
        $rec = FinanceRekening::findOrFail($id);
        return response()->json($rec);
    }

    public function update(Request $request, $id)
    {
        $rec = FinanceRekening::findOrFail($id);
        $data = $request->validate([
            'bank' => 'nullable|string',
            'no_rekening' => 'nullable|string',
            'atas_nama' => 'nullable|string',
        ]);
        $rec->update($data);
        return response()->json(['success' => true, 'data' => $rec]);
    }

    public function destroy($id)
    {
        $rec = FinanceRekening::findOrFail($id);
        $rec->delete();
        return response()->json(['success' => true]);
    }
}
