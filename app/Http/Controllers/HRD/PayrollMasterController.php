<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrMasterGajipokok;
use App\Models\HRD\PrMasterTunjanganJabatan;
use App\Models\HRD\PrMasterTunjanganLain;
use App\Models\HRD\PrMasterBenefit;
use App\Models\HRD\PrMasterPotongan;

class PayrollMasterController extends Controller
{
    public function index()
    {
        return view('hrd.payroll.master.index');
    }

    // Gaji Pokok
    public function datatableGajiPokok()
    {
        $data = PrMasterGajipokok::all();
        return response()->json(['data' => $data->map(function($row) {
            return [
                'id' => $row->id,
                'golongan' => $row->golongan,
                'nominal' => number_format($row->nominal, 0, ',', '.'),
                'aksi' => '<button class="btn btn-sm btn-warning edit-btn">Edit</button> <button class="btn btn-sm btn-danger delete-btn">Delete</button>'
            ];
        })]);
    }
    public function storeGajiPokok(Request $request)
    {
        $row = PrMasterGajipokok::create($request->only(['golongan','nominal']));
        return response()->json(['message' => 'Data berhasil ditambah']);
    }
    public function updateGajiPokok(Request $request, $id)
    {
        $row = PrMasterGajipokok::findOrFail($id);
        $row->update($request->only(['golongan','nominal']));
        return response()->json(['message' => 'Data berhasil diupdate']);
    }
    public function destroyGajiPokok($id)
    {
        PrMasterGajipokok::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }

    // Tunjangan Jabatan
    public function datatableTunjanganJabatan()
    {
        $data = PrMasterTunjanganJabatan::all();
        return response()->json(['data' => $data->map(function($row) {
            return [
                'id' => $row->id,
                'golongan' => $row->golongan,
                'nominal' => number_format($row->nominal, 0, ',', '.'),
                'aksi' => '<button class="btn btn-sm btn-warning edit-btn">Edit</button> <button class="btn btn-sm btn-danger delete-btn">Delete</button>'
            ];
        })]);
    }
    public function storeTunjanganJabatan(Request $request)
    {
        $row = PrMasterTunjanganJabatan::create($request->only(['golongan','nominal']));
        return response()->json(['message' => 'Data berhasil ditambah']);
    }
    public function updateTunjanganJabatan(Request $request, $id)
    {
        $row = PrMasterTunjanganJabatan::findOrFail($id);
        $row->update($request->only(['golongan','nominal']));
        return response()->json(['message' => 'Data berhasil diupdate']);
    }
    public function destroyTunjanganJabatan($id)
    {
        PrMasterTunjanganJabatan::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }

    // Tunjangan Lain
    public function datatableTunjanganLain()
    {
        $data = PrMasterTunjanganLain::all();
        return response()->json(['data' => $data->map(function($row) {
            return [
                'id' => $row->id,
                'nama_tunjangan' => $row->nama_tunjangan,
                'nominal' => number_format($row->nominal, 0, ',', '.'),
                'aksi' => '<button class="btn btn-sm btn-warning edit-btn">Edit</button> <button class="btn btn-sm btn-danger delete-btn">Delete</button>'
            ];
        })]);
    }
    public function storeTunjanganLain(Request $request)
    {
        $row = PrMasterTunjanganLain::create($request->only(['nama_tunjangan','nominal']));
        return response()->json(['message' => 'Data berhasil ditambah']);
    }
    public function updateTunjanganLain(Request $request, $id)
    {
        $row = PrMasterTunjanganLain::findOrFail($id);
        $row->update($request->only(['nama_tunjangan','nominal']));
        return response()->json(['message' => 'Data berhasil diupdate']);
    }
    public function destroyTunjanganLain($id)
    {
        PrMasterTunjanganLain::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }

    // Benefit
    public function datatableBenefit()
    {
        $data = PrMasterBenefit::all();
        return response()->json(['data' => $data->map(function($row) {
            return [
                'id' => $row->id,
                'nama_benefit' => $row->nama_benefit,
                'nominal' => number_format($row->nominal, 0, ',', '.'),
                'aksi' => '<button class="btn btn-sm btn-warning edit-btn">Edit</button> <button class="btn btn-sm btn-danger delete-btn">Delete</button>'
            ];
        })]);
    }
    public function storeBenefit(Request $request)
    {
        $row = PrMasterBenefit::create($request->only(['nama_benefit','nominal']));
        return response()->json(['message' => 'Data berhasil ditambah']);
    }
    public function updateBenefit(Request $request, $id)
    {
        $row = PrMasterBenefit::findOrFail($id);
        $row->update($request->only(['nama_benefit','nominal']));
        return response()->json(['message' => 'Data berhasil diupdate']);
    }
    public function destroyBenefit($id)
    {
        PrMasterBenefit::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }

    // Potongan
    public function datatablePotongan()
    {
        $data = PrMasterPotongan::all();
        return response()->json(['data' => $data->map(function($row) {
            return [
                'id' => $row->id,
                'nama_potongan' => $row->nama_potongan,
                'nominal' => number_format($row->nominal, 0, ',', '.'),
                'aksi' => '<button class="btn btn-sm btn-warning edit-btn">Edit</button> <button class="btn btn-sm btn-danger delete-btn">Delete</button>'
            ];
        })]);
    }
    public function storePotongan(Request $request)
    {
        $row = PrMasterPotongan::create($request->only(['nama_potongan','nominal']));
        return response()->json(['message' => 'Data berhasil ditambah']);
    }
    public function updatePotongan(Request $request, $id)
    {
        $row = PrMasterPotongan::findOrFail($id);
        $row->update($request->only(['nama_potongan','nominal']));
        return response()->json(['message' => 'Data berhasil diupdate']);
    }
    public function destroyPotongan($id)
    {
        PrMasterPotongan::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
