<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Merchandise;

class MasterMerchandiseController extends Controller
{
    public function index(Request $request)
    {
        return view('marketing.master_merchandise.index');
    }

    // Data for yajra DataTable
    public function data(Request $request)
    {
        $query = Merchandise::query();

        return datatables()->of($query)
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-primary btn-edit" data-id="'.$row->id.'">Edit</button> '
                     . '<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function edit($id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);
        return response()->json($m);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
        ]);

        $m = Merchandise::create($data);

        return response()->json(['success' => true, 'data' => $m]);
    }

    public function update(Request $request, $id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
        ]);

        $m->update($data);

        return response()->json(['success' => true, 'data' => $m]);
    }

    public function destroy($id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);
        $m->delete();
        return response()->json(['success' => true]);
    }
}
