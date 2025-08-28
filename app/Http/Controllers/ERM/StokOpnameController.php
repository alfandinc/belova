<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\StokOpname;
use App\Models\ERM\StokOpnameItem;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StokOpnameTemplateExport;
use App\Imports\StokOpnameImport;

class StokOpnameController extends Controller
{
    /**
     * Inline update stok fisik and recalculate selisih
     */
    public function updateStokFisik(Request $request, $itemId)
    {
        $request->validate([
            'stok_fisik' => 'required|numeric',
        ]);
        $item = StokOpnameItem::findOrFail($itemId);
        $item->stok_fisik = $request->stok_fisik;
        $item->selisih = $item->stok_fisik - $item->stok_sistem;
        $item->save();
        return response()->json([
            'success' => true,
            'stok_fisik' => $item->stok_fisik,
            'selisih' => $item->selisih,
        ]);
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = StokOpname::with('gudang', 'user')->latest();
            return datatables()->of($data)
                ->addColumn('selisih_count', function($row) {
                    return $row->items()->whereRaw('ABS(selisih) > 0')->count();
                })
                ->addColumn('aksi', function($row) {
                    return '<a href="'.route('erm.stokopname.create', $row->id).'" class="btn btn-primary btn-sm">Lakukan Stok Opname</a>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
        $gudangs = Gudang::all();
        return view('erm.stokopname.index', compact('gudangs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_opname' => 'required|date',
            'gudang_id' => 'required|exists:erm_gudang,id',
            'periode_bulan' => 'required|integer',
            'periode_tahun' => 'required|integer',
        ]);
        $stokOpname = StokOpname::create([
            'tanggal_opname' => $request->tanggal_opname,
            'gudang_id' => $request->gudang_id,
            'periode_bulan' => $request->periode_bulan,
            'periode_tahun' => $request->periode_tahun,
            'notes' => $request->notes,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
        return response()->json(['success' => true, 'id' => $stokOpname->id]);
    }

    public function create($id)
    {
        $stokOpname = StokOpname::with('gudang')->findOrFail($id);
        $items = StokOpnameItem::with('obat')
            ->where('stok_opname_id', $stokOpname->id)
            ->get();
        return view('erm.stokopname.create', compact('stokOpname', 'items'));
    }

    public function downloadExcel($id)
    {
        $stokOpname = StokOpname::findOrFail($id);
        $obats = Obat::all();
        return Excel::download(new StokOpnameTemplateExport($obats), 'stok_opname_template.xlsx');
    }

    public function uploadExcel(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        $stokOpname = StokOpname::findOrFail($id);
        DB::beginTransaction();
        try {
            $import = new StokOpnameImport($stokOpname->id);
            Excel::import($import, $request->file('file'));
            DB::commit();
            if ($import->imported > 0) {
                return back()->with('success', 'Data stok opname berhasil diupload: ' . $import->imported . ' baris.');
            } else {
                $msg = 'Tidak ada data yang diimport. Pastikan header kolom: obat_id, stok_sistem, stok_fisik. Baris dilewati: ' . json_encode($import->skippedRows);
                return back()->with('error', $msg);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

        public function itemsData(Request $request, $id)
    {
        $items = StokOpnameItem::query()
            ->leftJoin('erm_obat', 'erm_stok_opname_items.obat_id', '=', 'erm_obat.id')
            ->select('erm_stok_opname_items.*', 'erm_obat.nama as nama_obat')
            ->where('stok_opname_id', $id)
            ->orderByRaw('ABS(selisih) DESC');
        return datatables()->of($items)
            ->filterColumn('nama_obat', function($query, $keyword) {
                $query->where('erm_obat.nama', 'like', "%$keyword%");
            })
            ->make(true);
    }

        public function updateItemNotes(Request $request, $itemId)
    {
        $request->validate([
            'notes' => 'nullable|string|max:255',
        ]);
        $item = StokOpnameItem::findOrFail($itemId);
        $item->notes = $request->notes;
        $item->save();
        return response()->json(['success' => true, 'notes' => $item->notes]);
    }

        public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,proses,selesai',
        ]);
        $stokOpname = StokOpname::findOrFail($id);
        $stokOpname->status = $request->status;
        $stokOpname->save();
        return response()->json(['success' => true, 'status' => $stokOpname->status]);
    }
        public function saveStokFisik($id)
    {
        $items = StokOpnameItem::where('stok_opname_id', $id)->get();
        $updated = 0;
        foreach ($items as $item) {
            $obat = \App\Models\ERM\Obat::withInactive()->find($item->obat_id);
            if ($obat) {
                $obat->stok = $item->stok_fisik;
                $obat->save();
                $updated++;
            }
        }
        return response()->json(['success' => true, 'message' => "$updated stok obat berhasil diperbarui."]);
    }
}
