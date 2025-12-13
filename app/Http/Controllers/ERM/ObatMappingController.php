<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ObatMapping;
use App\Models\ERM\MetodeBayar;

class ObatMappingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $items = ObatMapping::orderBy('id', 'desc')->get();
            $data = $items->map(function ($it) {
                $vis = MetodeBayar::find($it->visitation_metode_bayar_id);
                $ob = MetodeBayar::find($it->obat_metode_bayar_id);
                return [
                    'id' => $it->id,
                    'visitation_metode_bayar_id' => $it->visitation_metode_bayar_id,
                    'visitation_metode_bayar_name' => $vis ? $vis->nama : '-',
                    'obat_metode_bayar_id' => $it->obat_metode_bayar_id,
                    'obat_metode_bayar_name' => $ob ? $ob->nama : '-',
                    'is_active' => $it->is_active ? 'Aktif' : 'Non Aktif',
                    'created_at' => $it->created_at ? $it->created_at->format('Y-m-d H:i') : '',
                    'aksi' => '<button class="btn btn-sm btn-info" onclick="editMapping(' . $it->id . ')">Edit</button> <button class="btn btn-sm btn-danger" onclick="deleteMapping(' . $it->id . ')">Hapus</button>'
                ];
            });
            return response()->json(['data' => $data]);
        }

        $metodeBayars = MetodeBayar::all();
        return view('erm.obat-mapping.index', compact('metodeBayars'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'visitation_metode_bayar_id' => 'required|exists:erm_metode_bayar,id',
            'obat_metode_bayar_id' => 'required|exists:erm_metode_bayar,id',
        ]);

        $data = $request->only(['visitation_metode_bayar_id', 'obat_metode_bayar_id']);
        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        $mapping = ObatMapping::create($data);
        return response()->json(['success' => true, 'message' => 'Mapping berhasil disimpan', 'id' => $mapping->id]);
    }

    public function show($id)
    {
        $m = ObatMapping::findOrFail($id);
        return response()->json($m);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'visitation_metode_bayar_id' => 'required|exists:erm_metode_bayar,id',
            'obat_metode_bayar_id' => 'required|exists:erm_metode_bayar,id',
        ]);

        $m = ObatMapping::findOrFail($id);
        $m->visitation_metode_bayar_id = $request->visitation_metode_bayar_id;
        $m->obat_metode_bayar_id = $request->obat_metode_bayar_id;
        $m->is_active = $request->has('is_active') ? (bool)$request->is_active : false;
        $m->save();

        return response()->json(['success' => true, 'message' => 'Mapping diperbarui']);
    }

    public function destroy($id)
    {
        $m = ObatMapping::findOrFail($id);
        $m->delete();
        return response()->json(['success' => true, 'message' => 'Mapping dihapus']);
    }
}
