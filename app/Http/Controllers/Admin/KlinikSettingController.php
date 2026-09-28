<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class KlinikSettingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Klinik::query()->select('id', 'nama', 'logo', 'color', 'report_cutoff_time');

            return DataTables::of($query)
                ->addColumn('logo_html', function ($row) {
                    if (!$row->logo) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<img src="' . e(asset('storage/' . $row->logo)) . '" alt="Logo" style="max-height:40px; max-width:120px;">';
                })
                ->addColumn('color_html', function ($row) {
                    if (!$row->color) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="d-inline-flex align-items-center">'
                        . '<span style="display:inline-block; width:20px; height:20px; border-radius:4px; border:1px solid rgba(0,0,0,.15); background:' . e($row->color) . ';"></span>'
                        . '<span class="ml-2">' . e($row->color) . '</span>'
                        . '</span>';
                })
                ->editColumn('report_cutoff_time', function ($row) {
                    return $row->report_cutoff_time ? substr($row->report_cutoff_time, 0, 5) : '-';
                })
                ->addColumn('actions', function ($row) {
                    return '<button type="button" class="btn btn-warning btn-sm btn-edit-klinik" data-id="' . $row->id . '">Edit</button>
                        <button type="button" class="btn btn-danger btn-sm btn-delete-klinik" data-id="' . $row->id . '" data-name="' . e($row->nama) . '">Delete</button>';
                })
                ->rawColumns(['logo_html', 'color_html', 'actions'])
                ->make(true);
        }

        return view('admin.klinik_settings.index');
    }

    public function show($id)
    {
        $klinik = Klinik::findOrFail($id);

        return response()->json(array_merge($klinik->toArray(), [
            'logo_url' => $klinik->logo ? asset('storage/' . $klinik->logo) : null,
            'report_cutoff_time' => $klinik->report_cutoff_time ? substr($klinik->report_cutoff_time, 0, 5) : null,
        ]));
    }

    public function store(Request $request)
    {
        $validated = $this->validateKlinik($request);

        $data = [
            'nama' => $validated['nama'],
            'report_cutoff_time' => $validated['report_cutoff_time'] ?? '00:00',
            'color' => $validated['color'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('klinik_logos', 'public');
        }

        $klinik = Klinik::create($data);

        return response()->json([
            'success' => true,
            'data' => $klinik,
        ]);
    }

    public function update(Request $request, $id)
    {
        $klinik = Klinik::findOrFail($id);
        $validated = $this->validateKlinik($request, $klinik->id);

        $data = [
            'nama' => $validated['nama'],
            'report_cutoff_time' => $validated['report_cutoff_time'] ?? '00:00',
            'color' => $validated['color'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $this->deleteLogoFile($klinik->logo);
            $data['logo'] = $request->file('logo')->store('klinik_logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogoFile($klinik->logo);
            $data['logo'] = null;
        }

        $klinik->update($data);

        return response()->json([
            'success' => true,
            'data' => $klinik,
        ]);
    }

    public function destroy($id)
    {
        $klinik = Klinik::findOrFail($id);

        if ($klinik->visitation()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik tidak dapat dihapus karena sudah memiliki data kunjungan.',
            ], 422);
        }

        try {
            $logo = $klinik->logo;
            $klinik->delete();
            $this->deleteLogoFile($logo);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik tidak dapat dihapus karena masih digunakan oleh data lain.',
            ], 422);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    private function validateKlinik(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255|unique:erm_klinik,nama' . ($ignoreId ? ',' . $ignoreId : ''),
            'report_cutoff_time' => 'nullable|date_format:H:i',
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);
    }

    private function deleteLogoFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
