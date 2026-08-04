<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Icd10;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class Icd10Controller extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Icd10::query()->select('id', 'code', 'description', 'category');

            return DataTables::of($query)
                ->addColumn('actions', function ($row) {
                    return '<button type="button" class="btn btn-warning btn-sm btn-edit-icd10" data-id="' . $row->id . '">Edit</button>
                        <button type="button" class="btn btn-danger btn-sm btn-delete-icd10" data-id="' . $row->id . '" data-name="' . e($row->code) . '">Delete</button>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.icd10.index');
    }

    public function show($id)
    {
        return response()->json(Icd10::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:erm_icd10,code',
            'description' => 'required|string',
            'category' => 'nullable|string|max:255',
        ]);

        $icd10 = Icd10::create($validated);

        return response()->json([
            'success' => true,
            'data' => $icd10,
        ]);
    }

    public function update(Request $request, $id)
    {
        $icd10 = Icd10::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:erm_icd10,code,' . $id,
            'description' => 'required|string',
            'category' => 'nullable|string|max:255',
        ]);

        $icd10->update($validated);

        return response()->json([
            'success' => true,
            'data' => $icd10,
        ]);
    }

    public function destroy($id)
    {
        Icd10::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
