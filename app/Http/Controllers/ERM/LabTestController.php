<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\LabTest;
use App\Models\ERM\LabKategori;
use App\Models\ERM\LabPaket;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ERM\LabTestsExport;
use App\Support\LabDataChecks;

class LabTestController extends Controller
{
    public function index(Request $request)
    {
        return view('erm.elab.master', [
            'kategoris' => LabKategori::orderBy('nama')->get(['id', 'nama']),
            'counts' => $this->counts(),
            'actionCounts' => LabDataChecks::counts(),
        ]);
    }

    /** Tab badges and the Tersedia / Tidak tersedia buttons */
    private function counts(): array
    {
        $row = LabTest::query()->selectRaw("COUNT(*) as semua, COALESCE(SUM(available = 'no'), 0) as tidak")->first();

        return [
            'test' => ['all' => (int) $row->semua, 'yes' => (int) $row->semua - (int) $row->tidak, 'no' => (int) $row->tidak],
            'paket' => LabPaket::count(),
            'kategori' => LabKategori::count(),
        ];
    }

    /**
     * Lab test list for the DataTable: plain data, the page renders it. Search covers nama, kategori and obat.
     * Counts are only sent when asked (?counts=1: after a change).
     */
    public function data(Request $request)
    {
        $query = LabTest::query()
            ->with([
                'labKategori:id,nama',
                // Inactive obat stay visible: the test still uses them
                'obats' => fn ($q) => $q->withoutGlobalScope('active')->select('erm_obat.id', 'erm_obat.nama', 'erm_obat.satuan'),
                'labPakets:id,nama',
            ])
            ->select('erm_lab_test.*')
            ->withCount('labPermintaan');

        if (in_array($request->input('available'), ['yes', 'no'], true)) {
            $query->where('available', $request->input('available'));
        }
        if ($request->filled('kategori_id')) {
            $query->where('lab_kategori_id', $request->input('kategori_id'));
        }
        // "Data yang perlu action" card filter
        LabDataChecks::apply($query, $request->input('action'), 'test');
        $q = trim((string) $request->input('search.value'));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('erm_lab_test.nama', 'like', "%{$q}%")
                    ->orWhereHas('labKategori', fn ($k) => $k->where('nama', 'like', "%{$q}%"))
                    ->orWhereHas('obats', fn ($o) => $o->withoutGlobalScope('active')->where('erm_obat.nama', 'like', "%{$q}%"));
            });
        }

        $table = DataTables::eloquent($query)
            ->only(['id', 'nama', 'kategori', 'harga', 'available', 'obat', 'paket', 'dipakai'])
            ->addColumn('kategori', fn ($row) => optional($row->labKategori)->nama)
            ->addColumn('obat', fn ($row) => $row->obats->map(fn ($o) => [
                'nama' => $o->nama,
                'dosis' => rtrim(rtrim(number_format((float) $o->pivot->dosis, 2, '.', ''), '0'), '.'),
                'satuan' => strtolower((string) $o->satuan),
            ])->values()->all())
            ->addColumn('paket', fn ($row) => $row->labPakets->pluck('nama')->sort()->values()->all())
            ->addColumn('dipakai', fn ($row) => (int) $row->lab_permintaan_count)
            ->editColumn('harga', fn ($row) => $row->harga !== null ? (float) $row->harga : null)
            ->orderColumn('nama', 'erm_lab_test.nama $1')
            ->orderColumn('kategori', '(SELECT nama FROM erm_lab_kategori WHERE erm_lab_kategori.id = erm_lab_test.lab_kategori_id) $1')
            ->orderColumn('harga', 'erm_lab_test.harga $1')
            ->orderColumn('available', 'erm_lab_test.available $1')
            // Rendered and escaped by the page
            ->escapeColumns([]);

        if ($request->boolean('counts')) {
            $table->with('counts', $this->counts())->with('action_counts', LabDataChecks::counts());
        }

        return $table->make(true);
    }

    private function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'lab_kategori_id' => 'required|exists:erm_lab_kategori,id',
            'harga' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'obat' => 'nullable|array',
            'obat.*.obat_id' => 'required|exists:erm_obat,id',
            'obat.*.dosis' => 'nullable|numeric|min:0',
        ];
    }

    private const MESSAGES = [
        'nama.required' => 'Nama test wajib diisi.',
        'lab_kategori_id.required' => 'Pilih kategori.',
        'obat.*.obat_id.exists' => 'Obat tidak ditemukan.',
        'obat.*.dosis.numeric' => 'Dosis obat harus berupa angka.',
    ];

    /** Obat rows from the form as pivot data; a repeated obat keeps its last dosis */
    private function obatSync(Request $request): array
    {
        $sync = [];
        foreach ((array) $request->input('obat', []) as $item) {
            if (!empty($item['obat_id'])) {
                $sync[$item['obat_id']] = ['dosis' => (float) ($item['dosis'] ?? 0)];
            }
        }

        return $sync;
    }

    private function namaTerpakai(array $validated, ?int $ignoreId = null): bool
    {
        return LabTest::where('lab_kategori_id', $validated['lab_kategori_id'])
            ->where('nama', $validated['nama'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), self::MESSAGES);
        if ($this->namaTerpakai($validated)) {
            return response()->json(['message' => 'Nama test sudah ada di kategori ini', 'errors' => ['nama' => ['Nama test sudah ada di kategori ini']]], 422);
        }

        $test = DB::transaction(function () use ($request, $validated) {
            $test = LabTest::create(collect($validated)->except('obat')->all());
            $test->obats()->sync($this->obatSync($request));
            return $test;
        });

        return response()->json(['message' => 'Lab test ditambahkan', 'data' => $test]);
    }

    public function update(Request $request, $id)
    {
        $test = LabTest::findOrFail($id);
        $validated = $request->validate($this->rules(), self::MESSAGES);
        if ($this->namaTerpakai($validated, $test->id)) {
            return response()->json(['message' => 'Nama test sudah ada di kategori ini', 'errors' => ['nama' => ['Nama test sudah ada di kategori ini']]], 422);
        }

        DB::transaction(function () use ($request, $validated, $test) {
            $test->update(collect($validated)->except('obat')->all());
            $test->obats()->sync($this->obatSync($request));
        });

        return response()->json(['message' => 'Lab test disimpan', 'data' => $test]);
    }

    /**
     * Show a single lab test with associated obats (for edit form)
     */
    public function show($id)
    {
        $test = LabTest::with(['obats' => fn ($q) => $q->withoutGlobalScope('active')])->findOrFail($id);
        return response()->json($test);
    }

    /**
     * A test that was ever requested, billed or is in a paket keeps its history: it can only be made unavailable.
     */
    public function destroy($id)
    {
        $test = LabTest::findOrFail($id);
        $usage = [
            'permintaan lab' => $test->labPermintaan()->count(),
            'billing' => DB::table('finance_billing')->where('billable_type', LabTest::class)->where('billable_id', $test->id)->count(),
            'paket lab' => $test->labPakets()->count(),
        ];
        $usedIn = collect($usage)->filter()->map(fn ($n, $label) => "{$n} {$label}")->values();
        if ($usedIn->isNotEmpty()) {
            return response()->json([
                'message' => 'Lab test sudah dipakai di ' . $usedIn->implode(', ') . '. Ubah menjadi tidak tersedia saja agar riwayat pasien tetap utuh.',
            ], 422);
        }

        DB::transaction(function () use ($test) {
            $test->obats()->detach();
            $test->delete();
        });

        return response()->json(['message' => 'Lab test dihapus']);
    }

    /**
     * Toggle availability status for a lab test (yes/no)
     */
    public function toggleAvailability(Request $request, $id)
    {
        $test = LabTest::findOrFail($id);
        $current = $test->available ?? 'yes';
        $new = $current === 'yes' ? 'no' : 'yes';
        $test->available = $new;
        $test->save();
        return response()->json(['message' => 'Updated', 'available' => $new]);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        $query = LabTest::query();
        if ($q) {
            $query->where('nama', 'like', "%$q%");
        }
        $results = $query->limit(20)->get(['id', 'nama', 'harga']);
        return response()->json($results);
    }

    public function list(Request $request)
    {
        $query = LabTest::query()
            ->with('labKategori:id,nama')
            ->orderBy('nama');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('nama', 'like', "%$q%");
        }

        $results = $query->get(['id', 'nama', 'lab_kategori_id', 'harga', 'available']);

        return response()->json($results->map(function ($test) {
            return [
                'id' => $test->id,
                'nama' => $test->nama,
                'harga' => $test->harga,
                'kategori' => optional($test->labKategori)->nama,
                'available' => $test->available,
            ];
        })->values());
    }

    /**
     * Export lab tests (nama, kategori, harga) to Excel
     */
    public function export()
    {
        return Excel::download(new LabTestsExport(), 'master_lab_tests.xlsx');
    }
}
