<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\MasterFaktur;
use App\Models\ERM\Obat;
use App\Models\ERM\Pemasok;
use Illuminate\Http\Request;

class MasterFakturController extends Controller
{
    // AJAX for select2 Obat
    public function ajaxObat(Request $request)
    {
        $q = $request->q;
        $data = Obat::where('nama', 'like', "%$q%")
            ->limit(20)
            ->get(['id', 'nama as text']);
        return response()->json($data);
    }

    // AJAX for select2 Pemasok
    public function ajaxPemasok(Request $request)
    {
        $q = $request->q;
        $data = Pemasok::where('nama', 'like', "%$q%")
            ->limit(20)
            ->get(['id', 'nama as text']);
        return response()->json($data);
    }
    public function show($id)
    {
        $masterFaktur = MasterFaktur::with(['obat', 'pemasok'])->findOrFail($id);
        return response()->json([
            'id' => $masterFaktur->id,
            'obat_id' => $masterFaktur->obat_id,
            'obat_nama' => $masterFaktur->obat->nama ?? '',
            'pemasok_id' => $masterFaktur->pemasok_id,
            'pemasok_nama' => $masterFaktur->pemasok->nama ?? '',
            'harga' => $masterFaktur->harga,
            'qty_per_box' => $masterFaktur->qty_per_box,
            'diskon' => $masterFaktur->diskon,
            'diskon_type' => $masterFaktur->diskon_type,
        ]);
    }
    public function index()
    {
        // For AJAX DataTables, the view just loads the table and JS
        return view('erm.masterfaktur.index');
    }
    public function form(Request $request, $id = null)
    {
        $masterFaktur = null;
        if (is_numeric($id) && $id > 0) {
            $masterFaktur = MasterFaktur::findOrFail($id);
        }
        $obats = \App\Models\ERM\Obat::all();
        $pemasoks = \App\Models\ERM\Pemasok::all();
        return view('erm.masterfaktur.partials.form', compact('masterFaktur', 'obats', 'pemasoks'))->render();
    }

    public function data(Request $request)
    {
        $query = MasterFaktur::with(['obat', 'pemasok']);
        $total = $query->count();
        // DataTables server-side params
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $search = $request->input('search.value');
        if ($search) {
            $query->whereHas('obat', function($q) use ($search) {
                $q->where('nama', 'like', "%$search%");
            })->orWhereHas('pemasok', function($q) use ($search) {
                $q->where('nama', 'like', "%$search%");
            });
        }
        $filtered = $query->count();
        $data = $query->skip($start)->take($length)->get()->map(function($mf) {
            return [
                'id' => $mf->id,
                'obat' => $mf->obat->nama ?? '-',
                'pemasok' => $mf->pemasok->nama ?? '-',
                'harga' => number_format($mf->harga,2),
                'qty_per_box' => $mf->qty_per_box,
                'diskon' => $mf->diskon,
                'diskon_type' => $mf->diskon_type,
                'action' => view('erm.masterfaktur.partials.action', compact('mf'))->render(),
            ];
        });
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.masterfaktur.create', compact('obats', 'pemasoks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'harga' => 'required|numeric',
            'qty_per_box' => 'required|integer',
            'diskon' => 'required|numeric',
            'diskon_type' => 'required|in:percent,nominal',
        ]);
        $mf = MasterFaktur::create($request->all());
        if ($request->ajax()) {
            return response()->json(['success' => true, 'id' => $mf->id]);
        }
        return redirect()->route('erm.masterfaktur.index')->with('success', 'Master Faktur created!');
    }

    public function edit($id)
    {
        $masterFaktur = MasterFaktur::findOrFail($id);
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.masterfaktur.edit', compact('masterFaktur', 'obats', 'pemasoks'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'harga' => 'required|numeric',
            'qty_per_box' => 'required|integer',
            'diskon' => 'required|numeric',
            'diskon_type' => 'required|in:percent,nominal',
        ]);
        $masterFaktur = MasterFaktur::findOrFail($id);
        $masterFaktur->update($request->all());
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('erm.masterfaktur.index')->with('success', 'Master Faktur updated!');
    }

    public function destroy($id)
    {
        $masterFaktur = MasterFaktur::findOrFail($id);
        $masterFaktur->delete();
        return redirect()->route('erm.masterfaktur.index')->with('success', 'Master Faktur deleted!');
    }
}
