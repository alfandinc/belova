<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\MaintenanceBarang;
use App\Models\Inventory\Barang;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class MaintenanceBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = MaintenanceBarang::with('barang')->latest();

            if ($request->filled('tanggal_range')) {
                $dates = explode(' - ', $request->tanggal_range);
                if (count($dates) == 2) {
                    $start = \Carbon\Carbon::createFromFormat('d-m-Y', trim($dates[0]))->startOfDay();
                    $end = \Carbon\Carbon::createFromFormat('d-m-Y', trim($dates[1]))->endOfDay();
                    $data = $data->whereBetween('tanggal_maintenance', [$start, $end]);
                }
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('barang', function($row){
                    return $row->barang ? $row->barang->name : 'N/A';
                })
                ->addColumn('tanggal_maintenance', function($row){
                    $date = Carbon::parse($row->tanggal_maintenance);
                    $date->locale('id');
                    return $date->translatedFormat('j F Y');
                })
                ->addColumn('biaya_maintenance', function($row){
                    return 'Rp ' . number_format($row->biaya_maintenance, 2, ',', '.');
                })
                ->addColumn('nama_vendor', function($row){
                    return $row->nama_vendor ?? '-';
                })
                ->addColumn('no_faktur', function($row){
                    return $row->no_faktur ?? '-';
                })
                ->addColumn('tanggal_next_maintenance', function($row){
                    if ($row->tanggal_next_maintenance) {
                        $date = Carbon::parse($row->tanggal_next_maintenance);
                        $date->locale('id');
                        return $date->translatedFormat('j F Y');
                    }
                    return '-';
                })
                ->addColumn('status', function($row){
                    return $row->status ?? '-';
                })
                ->addColumn('keterangan', function($row){
                    return $row->keterangan ?? '-';
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
        
        $barangs = Barang::whereHas('tipeBarang', function($query) {
            $query->where('maintenance', true);
        })->get();
        
        return view('inventory.maintenance_barang.index', compact('barangs'));
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
            'barang_id' => 'required|exists:inv_barang,id',
            'tanggal_maintenance' => 'required|date',
            'biaya_maintenance' => 'required|numeric|min:0',
            'nama_vendor' => 'nullable|string|max:255',
            'no_faktur' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'tanggal_next_maintenance' => 'nullable|date',
            'status' => 'nullable|string|max:50',
        ]);

        $maintenance = MaintenanceBarang::updateOrCreate(
            ['id' => $request->maintenance_id],
            [
                'barang_id' => $request->barang_id,
                'tanggal_maintenance' => $request->tanggal_maintenance,
                'biaya_maintenance' => $request->biaya_maintenance,
                'nama_vendor' => $request->nama_vendor,
                'no_faktur' => $request->no_faktur,
                'keterangan' => $request->keterangan,
                'tanggal_next_maintenance' => $request->tanggal_next_maintenance,
                'status' => $request->status,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Maintenance Barang saved successfully.']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $maintenance = MaintenanceBarang::findOrFail($id);
        return response()->json($maintenance);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $maintenance = MaintenanceBarang::findOrFail($id);
        $maintenance->delete();
        
        return response()->json(['success' => true, 'message' => 'Maintenance Barang deleted successfully.']);
    }
}
