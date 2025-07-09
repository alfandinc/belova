<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\TipeBarang;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TipeBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TipeBarang::latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('maintenance', function($row){
                    return $row->maintenance ? 'Yes' : 'No';
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
        
        return view('inventory.tipe_barang.index');
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
            'description' => 'nullable|string|max:500',
            'maintenance' => 'nullable', // changed from 'nullable|boolean'
        ]);

        $maintenance = $request->has('maintenance') ? true : false;

        $tipeBarang = TipeBarang::updateOrCreate(
            ['id' => $request->tipe_barang_id],
            [
                'name' => $request->name,
                'description' => $request->description,
                'maintenance' => $maintenance,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Tipe Barang saved successfully.']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tipeBarang = TipeBarang::findOrFail($id);
        return response()->json($tipeBarang);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tipeBarang = TipeBarang::findOrFail($id);
        $tipeBarang->delete();
        
        return response()->json(['success' => true, 'message' => 'Tipe Barang deleted successfully.']);
    }
}
