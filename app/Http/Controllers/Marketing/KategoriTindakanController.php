<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\KategoriTindakan;

class KategoriTindakanController extends Controller
{
    public function index()
    {
        $kategoris = KategoriTindakan::orderBy('nama')->get();
        return view('marketing.kategori_tindakan.index', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|unique:erm_kategori_tindakan,nama',
        ]);
        $kategori = KategoriTindakan::create($validated);
        return response()->json(['success' => true, 'data' => $kategori]);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        $query = KategoriTindakan::query();
        if ($q) {
            $query->where('nama', 'like', "%{$q}%");
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama']);
        return response()->json(['results' => $results->map(function($item){ return ['id'=>$item->id, 'nama'=>$item->nama, 'text'=>$item->nama]; })]);
    }

    public function destroy($id)
    {
        $k = KategoriTindakan::findOrFail($id);
        $k->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Import kategori tindakan from uploaded CSV.
     * Expected CSV: single column with category name (header optional)
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv');
        $path = $file->getRealPath();

        $created = 0;
        $skipped = 0;
        $errors = [];
        $created_items = [];
        $skipped_items = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $rowIndex = 0;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $rowIndex++;
                if (!isset($data[0]) || trim($data[0]) === '') {
                    continue;
                }
                $raw = trim($data[0]);
                if ($rowIndex === 1) {
                    $lower = strtolower($raw);
                    if (in_array($lower, ['kategori tindakan', 'kategori', 'nama', 'nama kategori'])) {
                        // header row: skip
                        continue;
                    }
                }
                $nama = $raw;
                try {
                    $exists = KategoriTindakan::where('nama', $nama)->first();
                    if ($exists) {
                        $skipped++;
                        $skipped_items[] = ['row' => $rowIndex, 'nama' => $nama, 'reason' => 'Exists'];
                        continue;
                    }
                    $k = KategoriTindakan::create(['nama' => $nama]);
                    $created++;
                    $created_items[] = ['row' => $rowIndex, 'id' => $k->id, 'nama' => $k->nama];
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowIndex}: " . $e->getMessage();
                    $skipped++;
                    $skipped_items[] = ['row' => $rowIndex, 'nama' => $nama, 'reason' => $e->getMessage()];
                }
            }
            fclose($handle);
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'created_items' => $created_items,
            'skipped_items' => $skipped_items,
        ]);
    }
}
