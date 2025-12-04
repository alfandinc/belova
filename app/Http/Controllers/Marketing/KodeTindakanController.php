<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\KodeTindakan;
use Yajra\DataTables\DataTables;

class KodeTindakanController extends Controller
{
    /**
     * Get connected obats for a kode tindakan (AJAX)
     */
    public function getObats($id)
    {
        $kodeTindakan = \App\Models\ERM\KodeTindakan::with(['obats'])->findOrFail($id);
        $obats = [];
        foreach ($kodeTindakan->obats as $obat) {
            $pivot = $obat->pivot;
            $obats[] = [
                'id' => $obat->id,
                'nama' => $obat->nama,
                'qty' => $pivot->qty,
                'dosis' => $pivot->dosis,
                'satuan_dosis' => $pivot->satuan_dosis,
                'hpp_jual' => $obat->hpp_jual ?? null,
            ];
        }
        return response()->json($obats);
    }
    /**
     * AJAX search for kode tindakan (for Select2)
     */
    public function search(Request $request)
    {
        $q = $request->input('q');
        $query = KodeTindakan::query();
        if ($q) {
            $query->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%");
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama', 'kode']);
        return response()->json([
            'results' => $results->map(function($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kode' => $item->kode,
                    'text' => $item->nama . ' (' . $item->kode . ')',
                ];
            })
        ]);
    }
    /**
     * Display the main view for kode tindakan management.
     */
    public function index()
    {
        return view('marketing.kode_tindakan.index');
    }

    /**
     * Return DataTable JSON for kode tindakan.
     */
    public function data(Request $request)
    {
        $query = KodeTindakan::with(['obats' => function($q) {
            // include `satuan` so the listing can show units when building the summary
            $q->select('erm_obat.id', 'nama', 'satuan');
        }])->select('erm_kode_tindakan.*');

        return DataTables::of($query)
            ->addColumn('obats_summary', function($row) {
                // Build summary like: "Paracetamol (0.5 Pcs), Ibuprofen (1 Tablet)"
                $parts = [];
                if ($row->obats && $row->obats->count()) {
                    foreach ($row->obats as $obat) {
                        $pivot = $obat->pivot;
                        $qty = isset($pivot->qty) ? (float)$pivot->qty : 0;
                        $satuan = $pivot->satuan_dosis ?? ($obat->satuan ?? '');
                        // Normalize unit to lowercase for display (e.g., 'Pcs' -> 'pcs', 'Ml' -> 'ml')
                        $unitDisplay = $satuan ? strtolower(trim($satuan)) : '';
                        // Format qty: remove trailing zeros (1.00 -> 1, 0.50 -> 0.5)
                        $qtyStr = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
                        $label = htmlspecialchars($obat->nama, ENT_QUOTES, 'UTF-8');
                        if ($unitDisplay) {
                            $parts[] = $label . ' (' . $qtyStr . ' ' . htmlspecialchars($unitDisplay, ENT_QUOTES, 'UTF-8') . ')';
                        } else {
                            $parts[] = $label . ' (' . $qtyStr . ')';
                        }
                    }
                }
                // Return HTML with <br> separators so each obat appears on its own line
                return implode('<br>', $parts);
            })
            ->rawColumns(['obats_summary'])
            ->make(true);
    }

    /**
     * Store a new kode tindakan (AJAX).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:erm_kode_tindakan,kode',
            'nama' => 'required|string',
            'hpp' => 'nullable|numeric',
            'harga_jasmed' => 'nullable|numeric',
            'harga_jual' => 'nullable|numeric',
            'harga_bottom' => 'nullable|numeric',
            'obats' => 'array',
            'obats.*.obat_id' => 'required|exists:erm_obat,id',
            'obats.*.qty' => 'required|numeric|min:0.01',
            'obats.*.dosis' => 'nullable|string',
            'obats.*.satuan_dosis' => 'nullable|string',
        ]);
        $kodeTindakan = KodeTindakan::create($validated);
        // Attach obat to pivot
        if ($request->has('obats')) {
            $pivotData = [];
            foreach ($request->obats as $obat) {
                $pivotData[$obat['obat_id']] = [
                    'qty' => floatval($obat['qty']),
                    'dosis' => $obat['dosis'] ?? null,
                    'satuan_dosis' => $obat['satuan_dosis'] ?? null,
                ];
            }
            $kodeTindakan->obats()->attach($pivotData);
        }
        return response()->json(['success' => true, 'data' => $kodeTindakan]);
    }

    /**
     * Update an existing kode tindakan (AJAX).
     */
    public function update(Request $request, $id)
    {
        $kodeTindakan = KodeTindakan::findOrFail($id);
        $validated = $request->validate([
            'kode' => 'required|string|unique:erm_kode_tindakan,kode,' . $id,
            'nama' => 'required|string',
            'hpp' => 'nullable|numeric',
            'harga_jasmed' => 'nullable|numeric',
            'harga_jual' => 'nullable|numeric',
            'harga_bottom' => 'nullable|numeric',
            'obats' => 'array',
            'obats.*.obat_id' => 'required|exists:erm_obat,id',
            'obats.*.qty' => 'required|numeric|min:0.01',
            'obats.*.dosis' => 'nullable|string',
            'obats.*.satuan_dosis' => 'nullable|string',
        ]);
        $kodeTindakan->update($validated);
        // Sync obat to pivot
        $pivotData = [];
        if ($request->has('obats')) {
            foreach ($request->obats as $obat) {
                $pivotData[$obat['obat_id']] = [
                    'qty' => floatval($obat['qty']),
                    'dosis' => $obat['dosis'] ?? null,
                    'satuan_dosis' => $obat['satuan_dosis'] ?? null,
                ];
            }
        }
        $kodeTindakan->obats()->sync($pivotData);
        return response()->json(['success' => true, 'data' => $kodeTindakan]);
    }

    /**
     * Show a kode tindakan with its related obat for edit modal
     */
    public function show($id)
    {
        $kodeTindakan = KodeTindakan::with(['obats' => function($q) {
            $q->select('erm_obat.id', 'nama', 'satuan');
        }])->findOrFail($id);
        $obats = [];
        foreach ($kodeTindakan->obats as $obat) {
            $pivot = $obat->pivot;
            $obats[] = [
                'obat_id' => $obat->id,
                'obat_nama' => $obat->nama,
                'qty' => $pivot->qty,
                'dosis' => $pivot->dosis,
                'satuan_dosis' => $pivot->satuan_dosis,
                'satuan' => $obat->satuan ?? null,
            ];
        }
        $data = $kodeTindakan->toArray();
        $data['obats'] = $obats;
        return response()->json($data);
    }

    /**
     * Delete a kode tindakan (AJAX).
     */
    public function destroy($id)
    {
        $kodeTindakan = KodeTindakan::findOrFail($id);
        $kodeTindakan->delete();
        return response()->json(['success' => true]);
    }
}
