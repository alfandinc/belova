<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Ruangan;
use App\Models\Inventory\Gedung;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RuanganController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Ruangan::with('gedung')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('gedung', function($row){
                    return $row->gedung ? $row->gedung->name : 'N/A';
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<a href="javascript:void(0)" data-id="'.$row->id.'" class="edit btn btn-success btn-sm">Edit</a> <a href="javascript:void(0)" data-id="'.$row->id.'" class="delete btn btn-danger btn-sm">Delete</a>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->order(function ($query) {
                    $query->orderBy('created_at', 'desc');
                })
                ->make(true);
        }

        $gedungs = Gedung::all();
        return view('inventory.ruangan.index', compact('gedungs'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gedung_id' => 'required|exists:inv_gedung,id',
            'description' => 'nullable|string|max:500',
        ]);

        $ruangan = Ruangan::updateOrCreate(
            ['id' => $request->ruangan_id],
            [
                'name' => $request->name,
                'gedung_id' => $request->gedung_id,
                'description' => $request->description,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Ruangan saved successfully.']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        return response()->json($ruangan);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $ruangan->delete();
        
        return response()->json(['success' => true, 'message' => 'Ruangan deleted successfully.']);
    }

    /**
     * Get rooms by building ID
     * 
     * @param int $gedungId
     * @return \Illuminate\Http\Response
     */
    public function getRuanganByGedung($gedungId)
    {
        $ruangans = Ruangan::where('gedung_id', $gedungId)->get();
        return response()->json($ruangans);
    }
}
