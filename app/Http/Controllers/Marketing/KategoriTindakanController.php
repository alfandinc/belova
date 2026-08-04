<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Icd10;
use App\Models\ERM\KategoriTindakan;
use App\Models\ERM\KodeTindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KategoriTindakanController extends Controller
{
    private const IMPORT_SESSION_KEY = 'marketing_kategori_tindakan_imports';

    public function index()
    {
        $kategoris = KategoriTindakan::with('icd10s:id,code,description')
            ->withCount('kodeTindakans')
            ->orderBy('nama')
            ->get();

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

        return response()->json([
            'results' => $results->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'text' => $item->nama,
                ];
            }),
        ]);
    }

    public function destroy($id)
    {
        $kategori = KategoriTindakan::findOrFail($id);
        $kategori->delete();

        return response()->json(['success' => true]);
    }

    public function importPreview(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $rows = $this->parseImportRows($request->file('csv')->getRealPath());
        $preview = $this->buildImportPreview($rows);
        $token = (string) Str::uuid();

        $imports = session()->get(self::IMPORT_SESSION_KEY, []);
        $imports[$token] = $rows;
        session()->put(self::IMPORT_SESSION_KEY, $imports);

        return response()->json([
            'success' => true,
            'token' => $token,
            'summary' => $preview['summary'],
            'rows' => $preview['rows'],
        ]);
    }

    public function importApply(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $imports = session()->get(self::IMPORT_SESSION_KEY, []);
        $rows = $imports[$validated['token']] ?? null;

        if (!$rows) {
            return response()->json([
                'success' => false,
                'message' => 'Preview import tidak ditemukan atau sudah kedaluwarsa.',
            ], 422);
        }

        $summary = [
            'categories_created' => 0,
            'kode_tindakan_linked' => 0,
            'icd10_created' => 0,
            'icd10_linked' => 0,
            'kode_tindakan_missing' => 0,
            'rows_processed' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $summary['rows_processed']++;

                $kategori = $this->findKategoriByName($row['kategori']);
                if (!$kategori) {
                    $kategori = KategoriTindakan::create(['nama' => $row['kategori']]);
                    $summary['categories_created']++;
                }

                $kodeTindakan = $this->findKodeTindakanByName($row['nama_tindakan']);
                if ($kodeTindakan) {
                    $alreadyLinked = $kodeTindakan->kategoris()
                        ->where('erm_kategori_tindakan.id', $kategori->id)
                        ->exists();

                    $kodeTindakan->kategoris()->syncWithoutDetaching([$kategori->id]);

                    if (!$alreadyLinked) {
                        $summary['kode_tindakan_linked']++;
                    }
                } else {
                    $summary['kode_tindakan_missing']++;
                }

                $icd10 = $this->resolveIcd10ForImport($row);
                if ($icd10) {
                    if (!$icd10->exists) {
                        $icd10->save();
                        $summary['icd10_created']++;
                    }

                    $alreadyIcdLinked = $kategori->icd10s()
                        ->where('erm_icd10.id', $icd10->id)
                        ->exists();

                    $kategori->icd10s()->syncWithoutDetaching([$icd10->id]);

                    if (!$alreadyIcdLinked) {
                        $summary['icd10_linked']++;
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
            ], 500);
        }

        unset($imports[$validated['token']]);
        session()->put(self::IMPORT_SESSION_KEY, $imports);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    private function parseImportRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $rows;
        }

        $rowIndex = 0;
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $rowIndex++;
            $data = array_pad($data, 4, null);

            $namaTindakan = $this->normalizeCell($data[0] ?? null);
            $kategori = $this->normalizeCell($data[1] ?? null);
            $icdCode = $this->normalizeCell($data[2] ?? null);
            $icdDescription = $this->normalizeCell($data[3] ?? null);

            if ($rowIndex === 1 && $this->looksLikeHeaderRow($namaTindakan, $kategori, $icdCode, $icdDescription)) {
                continue;
            }

            if (!$namaTindakan || !$kategori) {
                continue;
            }

            $rows[] = [
                'row' => $rowIndex,
                'nama_tindakan' => $namaTindakan,
                'kategori' => $kategori,
                'icd_code' => $this->normalizeNullableCell($icdCode),
                'icd_description' => $this->normalizeNullableCell($icdDescription),
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function buildImportPreview(array $rows): array
    {
        $previewRows = [];
        $summary = [
            'categories_to_create' => 0,
            'kode_tindakan_to_link' => 0,
            'icd10_to_create' => 0,
            'icd10_to_link' => 0,
            'kode_tindakan_missing' => 0,
            'rows_total' => count($rows),
        ];

        $plannedCategories = [];
        $plannedIcds = [];
        $plannedKodeLinks = [];
        $plannedIcdLinks = [];

        foreach ($rows as $row) {
            $kategoriKey = mb_strtolower($row['kategori']);
            $icdKey = $this->buildIcdKey($row['icd_code'], $row['icd_description']);

            $kategori = $this->findKategoriByName($row['kategori']);
            $kodeTindakan = $this->findKodeTindakanByName($row['nama_tindakan']);
            $icd10 = $this->findIcd10($row['icd_code'], $row['icd_description']);

            $kategoriAction = 'existing';
            if (!$kategori) {
                if (!isset($plannedCategories[$kategoriKey])) {
                    $plannedCategories[$kategoriKey] = true;
                    $summary['categories_to_create']++;
                }
                $kategoriAction = 'create';
            }

            $kodeLinkAction = 'missing';
            if ($kodeTindakan) {
                $kodeLinkKey = $kodeTindakan->id . '|' . $kategoriKey;
                if ($kategori) {
                    $alreadyKodeLinked = $kodeTindakan->kategoris()
                        ->where('erm_kategori_tindakan.id', $kategori->id)
                        ->exists();
                    $kodeLinkAction = $alreadyKodeLinked ? 'exists' : 'attach';
                } else {
                    if (isset($plannedKodeLinks[$kodeLinkKey])) {
                        $kodeLinkAction = 'exists';
                    } else {
                        $plannedKodeLinks[$kodeLinkKey] = true;
                        $kodeLinkAction = 'attach';
                    }
                }

                if ($kodeLinkAction === 'attach') {
                    $summary['kode_tindakan_to_link']++;
                }
            } else {
                $summary['kode_tindakan_missing']++;
            }

            $icdAction = 'none';
            $icdLinkAction = 'none';
            if ($row['icd_code'] || $row['icd_description']) {
                if ($icd10) {
                    $icdAction = 'existing';
                } elseif ($icdKey) {
                    if (!isset($plannedIcds[$icdKey])) {
                        $plannedIcds[$icdKey] = true;
                        $summary['icd10_to_create']++;
                    }
                    $icdAction = 'create';
                }

                if ($icdKey) {
                    $icdLinkKey = $kategoriKey . '|' . $icdKey;
                    if ($kategori && $icd10) {
                        $alreadyIcdLinked = $kategori->icd10s()
                            ->where('erm_icd10.id', $icd10->id)
                            ->exists();
                        $icdLinkAction = $alreadyIcdLinked ? 'exists' : 'attach';
                    } else {
                        if (isset($plannedIcdLinks[$icdLinkKey])) {
                            $icdLinkAction = 'exists';
                        } else {
                            $plannedIcdLinks[$icdLinkKey] = true;
                            $icdLinkAction = 'attach';
                        }
                    }
                }

                if ($icdLinkAction === 'attach') {
                    $summary['icd10_to_link']++;
                }
            }

            $previewRows[] = [
                'row' => $row['row'],
                'nama_tindakan' => $row['nama_tindakan'],
                'kategori' => $row['kategori'],
                'icd_code' => $row['icd_code'],
                'icd_description' => $row['icd_description'],
                'kategori_action' => $kategoriAction,
                'kode_tindakan_action' => $kodeLinkAction,
                'icd_action' => $icdAction,
                'icd_link_action' => $icdLinkAction,
            ];
        }

        return [
            'summary' => $summary,
            'rows' => $previewRows,
        ];
    }

    private function findKategoriByName(string $nama): ?KategoriTindakan
    {
        return KategoriTindakan::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
    }

    private function findKodeTindakanByName(string $nama): ?KodeTindakan
    {
        return KodeTindakan::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
    }

    private function findIcd10(?string $code, ?string $description): ?Icd10
    {
        if ($code) {
            return Icd10::where('code', $code)->first();
        }

        if ($description) {
            return Icd10::whereRaw('LOWER(description) = ?', [mb_strtolower($description)])->first();
        }

        return null;
    }

    private function resolveIcd10ForImport(array $row): ?Icd10
    {
        $existing = $this->findIcd10($row['icd_code'], $row['icd_description']);
        if ($existing) {
            return $existing;
        }

        if (!$row['icd_code']) {
            return null;
        }

        return new Icd10([
            'code' => $row['icd_code'],
            'description' => $row['icd_description'] ?: $row['icd_code'],
            'category' => $row['kategori'],
        ]);
    }

    private function buildIcdKey(?string $code, ?string $description): ?string
    {
        if ($code) {
            return 'code:' . $code;
        }

        if ($description) {
            return 'desc:' . mb_strtolower($description);
        }

        return null;
    }

    private function normalizeCell($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(str_replace("\xEF\xBB\xBF", '', (string) $value));

        return $value === '' ? null : $value;
    }

    private function normalizeNullableCell(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        return $normalized;
    }

    private function looksLikeHeaderRow(?string $namaTindakan, ?string $kategori, ?string $icdCode, ?string $icdDescription): bool
    {
        return mb_strtolower((string) $namaTindakan) === 'nama tindakan'
            && mb_strtolower((string) $kategori) === 'kategori'
            && str_contains(mb_strtolower((string) $icdCode), 'kode diagnosa')
            && str_contains(mb_strtolower((string) $icdDescription), 'diagnosa');
    }
}
