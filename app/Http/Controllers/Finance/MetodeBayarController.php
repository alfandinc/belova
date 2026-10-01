<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\MetodeBayar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

/**
 * Master data Metode Bayar (Umum / Asuransi) used by visit registration and the billing tabs.
 */
class MetodeBayarController extends Controller
{
    public function index()
    {
        $summary = [
            'total' => MetodeBayar::count(),
            'umum' => MetodeBayar::umum()->active()->count(),
            'asuransi' => MetodeBayar::asuransi()->active()->count(),
            'inactive' => MetodeBayar::where('is_active', false)->count(),
        ];

        return view('finance.metode-bayar.index', compact('summary'));
    }

    public function data(Request $request)
    {
        $query = MetodeBayar::query()
            ->select('id', 'nama', 'is_asuransi', 'is_active')
            ->withCount('visitations');

        if (in_array($request->input('group'), ['umum', 'asuransi'], true)) {
            $query->where('is_asuransi', $request->input('group') === 'asuransi');
        }

        if (in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        return DataTables::of($query)
            ->editColumn('is_asuransi', fn ($row) => (bool) $row->is_asuransi)
            ->editColumn('is_active', fn ($row) => (bool) $row->is_active)
            ->make(true);
    }

    public function show(MetodeBayar $metodeBayar)
    {
        return response()->json($metodeBayar);
    }

    public function store(Request $request)
    {
        $metodeBayar = MetodeBayar::create($this->validated($request));
        MetodeBayar::forgetCache();

        return response()->json(['success' => true, 'message' => 'Metode bayar berhasil ditambahkan.', 'data' => $metodeBayar]);
    }

    public function update(Request $request, MetodeBayar $metodeBayar)
    {
        $metodeBayar->update($this->validated($request, $metodeBayar));
        MetodeBayar::forgetCache();

        return response()->json(['success' => true, 'message' => 'Metode bayar berhasil diperbarui.', 'data' => $metodeBayar]);
    }

    public function destroy(MetodeBayar $metodeBayar)
    {
        // Old visits keep pointing at it; deactivate instead of deleting.
        if ($metodeBayar->visitations()->exists() || $metodeBayar->obat()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Metode bayar sudah dipakai di kunjungan/obat. Nonaktifkan saja agar tidak muncul di pilihan.',
            ], 422);
        }

        $metodeBayar->delete();
        MetodeBayar::forgetCache();

        return response()->json(['success' => true, 'message' => 'Metode bayar berhasil dihapus.']);
    }

    private function validated(Request $request, ?MetodeBayar $metodeBayar = null): array
    {
        $data = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('erm_metode_bayar', 'nama')->ignore($metodeBayar?->id),
            ],
            'is_asuransi' => 'required|boolean',
            'is_active' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama metode bayar sudah ada.',
        ]);

        return [
            'nama' => trim($data['nama']),
            'is_asuransi' => (bool) $data['is_asuransi'],
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
