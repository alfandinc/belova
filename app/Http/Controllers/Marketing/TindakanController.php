<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Tindakan;
use App\Models\ERM\PaketTindakan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class TindakanController extends Controller
{
    /**
     * Get before/after gallery for tindakan.
     */
    public function galeriBeforeAfter($id)
    {
        $informConsents = \App\Models\ERM\InformConsent::where('tindakan_id', $id)
            ->whereNotNull('before_image_path')
            ->where('before_image_path', '!=', '')
            ->whereNotNull('after_image_path')
            ->where('after_image_path', '!=', '')
            ->with(['visitation.pasien', 'visitation.dokter'])
            ->get();

        $result = [];
        foreach ($informConsents as $ic) {
            // If path does not start with 'storage/', prepend it
            $beforePath = $ic->before_image_path;
            $afterPath = $ic->after_image_path;
            if ($beforePath && !preg_match('/^storage\//', $beforePath)) {
                $beforePath = 'storage/' . ltrim($beforePath, '/');
            }
            if ($afterPath && !preg_match('/^storage\//', $afterPath)) {
                $afterPath = 'storage/' . ltrim($afterPath, '/');
            }
            $tanggalVisit = $ic->visitation && $ic->visitation->tanggal_visitation
                ? \Carbon\Carbon::parse($ic->visitation->tanggal_visitation)->translatedFormat('j F Y')
                : 'N/A';
            $result[] = [
                'nama_tindakan' => $ic->tindakan ? $ic->tindakan->nama : 'Tindakan',
                'pasien_nama' => $ic->visitation && $ic->visitation->pasien ? $ic->visitation->pasien->nama : 'N/A',
                'tanggal_visit' => $tanggalVisit,
                'dokter_nama' => $ic->visitation && $ic->visitation->dokter ? $ic->visitation->dokter->user ? $ic->visitation->dokter->user->name : ($ic->visitation->dokter->nama ?? 'N/A') : 'N/A',
                'before_image' => asset($beforePath),
                'after_image' => asset($afterPath),
                'allow_post' => (bool) $ic->allow_post,
            ];
        }
        return response()->json($result);
    }
    /**
     * Display a listing of tindakan.
     */
    public function index()
    {
        return view('marketing.tindakan.index');
    }

    /**
     * Get tindakan data for DataTables.
     */
    public function getTindakanData(Request $request)
    {
        $spesialisId = $request->input('spesialis_id');
        $status = $request->input('status');

        $query = Tindakan::with('spesialis')->withCount(['sop', 'kodeTindakans']);
        if ($spesialisId) {
            $query->where('spesialis_id', $spesialisId);
        }
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return DataTables::of($query)
            ->addColumn('spesialis_nama', function ($row) {
                return $row->spesialis ? $row->spesialis->nama : 'N/A';
            })
            ->editColumn('nama', function ($row) {
                $nama = $row->nama;
                if (isset($row->kode_tindakans_count) && $row->kode_tindakans_count == 0) {
                    $nama = '<i class="fas fa-exclamation-triangle text-danger blink-icon mr-2" title="No Kode Tindakan assigned"></i>' . $nama;
                }
                return $nama;
            })
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-primary btn-sm edit-tindakan" data-id="'.$row->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-danger btn-sm delete-tindakan" data-id="'.$row->id.'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="button" class="btn btn-info btn-sm galeri-before-after" data-id="'.$row->id.'">
                        <i class="fas fa-images"></i> Galeri Before After
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm toggle-active-tindakan" data-id="'.$row->id.'" data-active="'.($row->is_active ? '1' : '0').'">
                        '.($row->is_active ? '<i class="fas fa-eye"></i> Active' : '<i class="fas fa-eye-slash"></i> Inactive').'
                    </button>
                ';
            })
            ->addColumn('status', function($row) {
                if ($row->is_active) {
                    return '<span class="badge badge-success">Active</span>'; 
                }
                return '<span class="badge badge-secondary">Inactive</span>';
            })
            ->rawColumns(['nama', 'action', 'status'])
            ->make(true);
    }

        /**
     * AJAX search for SOPs (for Select2)
     */
    public function searchSop(Request $request)
    {
        $q = $request->input('q');
        $sopQuery = \App\Models\ERM\Sop::query();
        if ($q) {
            $sopQuery->where('nama_sop', 'like', "%".$q."%");
        }
        $sops = $sopQuery->orderBy('nama_sop')->limit(20)->get(['id', 'nama_sop']);
        $results = $sops->map(function($sop) {
            return [
                'id' => $sop->id,
                'nama_sop' => $sop->nama_sop
            ];
        });
        return response()->json($results);
    }

    /**
     * Get all SOPs and selected SOPs for a tindakan (for modal select2)
     */
    public function getSopTindakan($id)
    {
        $tindakan = Tindakan::with(['sop' => function($q) { $q->orderBy('urutan'); }])->findOrFail($id);
        $orderedSops = $tindakan->sop;
        $allSop = \App\Models\ERM\Sop::all(['id', 'nama_sop']);
        $selectedSopIds = $orderedSops->pluck('id')->toArray();
        return response()->json([
            'all_sop' => $allSop,
            'selected_sop_ids' => $selectedSopIds
        ]);
    }

    /**
     * Update SOPs for a tindakan (from modal select2)
     */
    public function updateSopTindakan(Request $request, $id)
    {
        $tindakan = Tindakan::findOrFail($id);
        $sopIds = $request->input('sop_ids', []);
        $currentSops = $tindakan->sop;
        $toDelete = $currentSops->whereNotIn('id', $sopIds);
        $notDeleted = [];
        foreach ($toDelete as $sop) {
            $isReferenced = DB::table('erm_spk_details')->where('sop_id', $sop->id)->exists();
            if ($isReferenced) {
                $notDeleted[] = $sop->nama_sop;
                // Do not delete or update, and keep it assigned to tindakan (do nothing)
                if (!in_array($sop->id, $sopIds)) {
                    $sopIds[] = $sop->id;
                }
                continue;
            }
            // Only delete if not referenced
            $sop->delete();
        }
        // Do NOT update tindakan_id to null for referenced SOPs, only for non-referenced ones
        // So, no further detach/update here
        // Add new SOPs (clone from master SOP if not already present)
        $existingIds = $currentSops->pluck('id')->toArray();
        foreach ($sopIds as $idx => $sopId) {
            if (!in_array($sopId, $existingIds)) {
                $sop = \App\Models\ERM\Sop::find($sopId);
                if ($sop) {
                    $tindakan->sop()->create([
                        'nama_sop' => $sop->nama_sop,
                        'deskripsi' => $sop->deskripsi,
                        'urutan' => $idx + 1
                    ]);
                }
            }
        }
        // Update urutan for all assigned SOPs (including existing)
        $assignedSops = $tindakan->sop()->whereIn('id', $sopIds)->get();
        foreach ($sopIds as $idx => $sopId) {
            $sop = $assignedSops->where('id', $sopId)->first();
            if ($sop) {
                $sop->urutan = $idx + 1;
                $sop->save();
            }
        }
        $msg = 'SOP tindakan updated successfully.';
        if (count($notDeleted)) {
            $msg .= ' Some SOPs were not deleted because they are in use: ' . implode(', ', $notDeleted);
        }
        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    /**
     * Show the form for creating a new tindakan.
     */
    public function create()
    {
        return view('marketing.tindakan.form');
    }

    /**
     * Store a newly created tindakan or update an existing one.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:erm_tindakan,id',
            'nama' => ['required', 'string', 'max:255', 
                Rule::unique('erm_tindakan')->ignore($request->id)],
            'deskripsi' => 'nullable|string',
            'harga' => 'required|numeric|min:0',
            'harga_diskon' => 'nullable|numeric|min:0',
            'diskon_active' => 'nullable|boolean',
            'spesialis_id' => 'required|exists:erm_spesialisasis,id',
            'obat_ids' => 'array',
            'obat_ids.*' => 'exists:erm_obat,id',
            'kode_tindakan_ids' => 'array',
            'kode_tindakan_ids.*' => 'exists:erm_kode_tindakan,id',
            'is_active' => 'nullable|boolean',
            'harga_3_kali' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            $tindakan = Tindakan::updateOrCreate(
                ['id' => $request->id],
                [
                    'nama' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga' => $request->harga,
                    'harga_diskon' => $request->harga_diskon,
                    'diskon_active' => $request->diskon_active ?? 0,
                    'spesialis_id' => $request->spesialis_id,
                        'is_active' => $request->input('is_active', 1),
                        'harga_3_kali' => $request->input('harga_3_kali', null),
                ]
            );

            // Handle SOPs (from text input, as names)
            $sopNames = $request->input('sop_names');
            $sopNamesArr = [];
            if ($sopNames) {
                $sopNamesArr = is_array($sopNames) ? $sopNames : explode(',', $sopNames);
                $sopNamesArr = array_filter(array_map('trim', $sopNamesArr)); // Remove empty strings
            }
            
            // Get current SOPs for tindakan
            $currentSops = $tindakan->sop()->get();
            
            // Find SOPs to delete (current SOPs not in the new list)
            $toDelete = $currentSops->filter(function($sop) use ($sopNamesArr) {
                return !in_array($sop->nama_sop, $sopNamesArr);
            });
            
            // Only delete SOPs not referenced in erm_spk_details
            foreach ($toDelete as $sop) {
                $isReferenced = DB::table('erm_spk_details')->where('sop_id', $sop->id)->exists();
                if (!$isReferenced) {
                    $sop->delete();
                }
            }
            
            // Add or update SOPs
            foreach ($sopNamesArr as $idx => $sopName) {
                $sopName = trim($sopName);
                if ($sopName === '') continue;
                $existing = $currentSops->firstWhere('nama_sop', $sopName);
                if ($existing) {
                    // Update order if needed
                    if ($existing->urutan != $idx + 1) {
                        $existing->urutan = $idx + 1;
                        $existing->save();
                    }
                } else {
                    $tindakan->sop()->create([
                        'nama_sop' => $sopName,
                        'deskripsi' => null,
                        'urutan' => $idx + 1
                    ]);
                }
            }

            // Sync bundled obat
            $obatIds = $request->input('obat_ids', []);
            $tindakan->obats()->sync($obatIds);

            // Sync kode tindakan
            $kodeTindakanIds = $request->input('kode_tindakan_ids', []);
            $tindakan->kodeTindakans()->sync($kodeTindakanIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tindakan has been ' . ($request->id ? 'updated' : 'created') . ' successfully!',
                'data' => $tindakan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set all tindakan as inactive.
     */
    public function makeAllInactive(Request $request)
    {
        try {
            Tindakan::query()->update(['is_active' => false]);
            return response()->json(['success' => true, 'message' => 'All tindakan set to inactive']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Set all tindakan as active.
     */
    public function makeAllActive(Request $request)
    {
        try {
            Tindakan::query()->update(['is_active' => true]);
            return response()->json(['success' => true, 'message' => 'All tindakan set to active']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle active status for a single tindakan.
     */
    public function toggleActive($id)
    {
        try {
            $t = Tindakan::findOrFail($id);
            $t->is_active = !$t->is_active;
            $t->save();
            return response()->json(['success' => true, 'is_active' => (bool)$t->is_active]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get tindakan data by ID.
     */
    public function getTindakan($id)
    {
        $tindakan = Tindakan::with(['spesialis', 'sop' => function($q) { $q->orderBy('urutan'); }, 'obats', 'kodeTindakans'])->findOrFail($id);
        $result = $tindakan->toArray();
        $result['sop'] = $tindakan->sop->map(function($sop) {
            return [
                'nama_sop' => $sop->nama_sop,
                'urutan' => $sop->urutan
            ];
        })->toArray();
        $result['obat_ids'] = $tindakan->obats->pluck('id')->toArray();
        // Also return basic obat objects (id & nama) to allow frontend to populate Select2 labels
        $result['obats'] = $tindakan->obats->map(function($obat) {
            return [
                'id' => $obat->id,
                'nama' => $obat->nama
            ];
        })->toArray();
        $result['kode_tindakan_ids'] = $tindakan->kodeTindakans->pluck('id')->toArray();
        // Add kode_tindakans array for Select2 population
        $result['kode_tindakans'] = $tindakan->kodeTindakans->map(function($kt) {
            // Get connected obats for this kode tindakan
            $obats = $kt->obats->map(function($obat) {
                return [
                    'id' => $obat->id,
                    'nama' => $obat->nama,
                    'kode' => $obat->kode ?? '',
                    'qty' => $obat->pivot->qty ?? null,
                    'dosis' => $obat->pivot->dosis ?? null,
                    'satuan_dosis' => $obat->pivot->satuan_dosis ?? null
                ];
            })->toArray();
            return [
                'id' => $kt->id,
                'text' => $kt->nama ? ($kt->kode ? $kt->kode.' - '.$kt->nama : $kt->nama) : ($kt->kode ?? ''),
                'obats' => $obats
            ];
        })->toArray();
        return response()->json($result);
    }

    /**
     * Import tindakan from CSV.
     * Expected columns: name/nama, harga normal, harga diskon, harga 3x, specialist (name or id)
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

        DB::beginTransaction();
        try {
            if (($handle = fopen($path, 'r')) !== false) {
                $rowIndex = 0;
                $headers = null;
                while (($data = fgetcsv($handle, 0, ',')) !== false) {
                    $rowIndex++;
                    // skip empty rows
                    if (!isset($data[0]) || trim($data[0]) === '') {
                        continue;
                    }

                    // detect header row
                    if ($rowIndex === 1) {
                        $lowerCols = array_map(function($c){ return strtolower(trim($c)); }, $data);
                        // if first row contains known header keywords, treat as header
                        $known = ['nama', 'name', 'harga normal', 'harga_normal', 'harga', 'harga diskon', 'harga_diskon', 'harga 3x', 'harga_3_kali', 'spesialis', 'specialist', 'spesialisasi', 'is_active', 'active', 'aktif'];
                        $intersect = array_intersect($lowerCols, $known);
                        if (count($intersect) >= 2) {
                            $headers = $lowerCols;
                            continue;
                        }
                    }

                    // Map columns
                    if ($headers) {
                        $cols = $headers;
                        $get = function($names) use ($cols, $data) {
                            foreach ($names as $n) {
                                $idx = array_search($n, $cols);
                                if ($idx !== false && isset($data[$idx])) return trim($data[$idx]);
                            }
                            return null;
                        };
                        $nama = $get(['nama','name','nama tindakan','nama_tindakan']);
                        $harga = $get(['harga normal','harga_normal','harga']);
                        $harga_diskon = $get(['harga diskon','harga_diskon','diskon']);
                        $harga_3_kali = $get(['harga 3x','harga_3_kali','harga3x','harga_3x']);
                        $spesialisRaw = $get(['spesialis','specialist','spesialisasi','spesialis_id']);
                        $isActiveRaw = $get(['is_active','active','aktif']);
                    } else {
                        // expected order: nama, harga, harga_diskon, harga_3_kali, spesialis
                        $nama = isset($data[0]) ? trim($data[0]) : null;
                        $harga = isset($data[1]) ? trim($data[1]) : null;
                        $harga_diskon = isset($data[2]) ? trim($data[2]) : null;
                        $harga_3_kali = isset($data[3]) ? trim($data[3]) : null;
                        $spesialisRaw = isset($data[4]) ? trim($data[4]) : null;
                        $isActiveRaw = isset($data[5]) ? trim($data[5]) : null;
                    }

                    if (!$nama) { $skipped++; continue; }

                    // We'll attempt to create even if the same name exists.
                    // If DB enforces uniqueness, we'll append a suffix until it succeeds.
                    // parse number helper: remove currency symbols and thousand separators
                    $parseNumber = function($str) {
                        if ($str === null || $str === '') return null;
                        $s = preg_replace('/[^0-9,\.\-]/', '', $str);
                        // If contains comma and dot, assume dot thousand sep, comma decimal (e.g. 1.234,56)
                        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
                            $s = str_replace('.', '', $s);
                            $s = str_replace(',', '.', $s);
                        } else {
                            // remove thousand dots
                            $s = str_replace('.', '', $s);
                            $s = str_replace(',', '.', $s);
                        }
                        return is_numeric($s) ? floatval($s) : null;
                    };

                    // parse boolean helper: accept 1/0, true/false, yes/no, aktif/nonaktif
                    $parseBool = function($v) {
                        if ($v === null || $v === '') return null;
                        $s = strtolower(trim($v));
                        if (in_array($s, ['1', 'true', 'yes', 'y', 'aktif', 'active'])) return 1;
                        if (in_array($s, ['0', 'false', 'no', 'n', 'nonaktif', 'inactive'])) return 0;
                        return null;
                    };

                    $hargaVal = $parseNumber($harga);
                    $hargaDiskonVal = $parseNumber($harga_diskon);
                    $harga3xVal = $parseNumber($harga_3_kali);

                    // resolve specialist to id
                    $spesialis_id = null;
                    if ($spesialisRaw) {
                        $spesialisRawTrim = trim($spesialisRaw);
                        if (ctype_digit($spesialisRawTrim)) {
                            $sp = \App\Models\ERM\Spesialisasi::find(intval($spesialisRawTrim));
                            if ($sp) $spesialis_id = $sp->id;
                        }
                        if (!$spesialis_id) {
                            $sp = \App\Models\ERM\Spesialisasi::whereRaw('LOWER(nama) = ?', [strtolower($spesialisRawTrim)])->first();
                            if ($sp) $spesialis_id = $sp->id;
                        }
                    }

                    // parse is_active value
                    $isActiveVal = $parseBool($isActiveRaw);

                    if (!$spesialis_id) {
                        $errors[] = "Row {$rowIndex}: Specialist not found (" . ($spesialisRaw ?? '') . ")";
                        $skipped++;
                        continue;
                    }

                    $baseName = $nama;
                    $createdThis = false;

                    // If a tindakan with the same name exists, rename the old one to append " (menu lama)"
                    $existing = Tindakan::where('nama', $baseName)->first();
                    if ($existing) {
                        // Ensure the new name for the old item is unique
                        $candidateBase = $existing->nama . ' (menu lama)';
                        $candidate = $candidateBase;
                        $idx = 1;
                        while (Tindakan::where('nama', $candidate)->exists()) {
                            $idx++;
                            $candidate = $candidateBase . ' ' . $idx;
                            if ($idx > 100) break;
                        }
                        try {
                            $existing->nama = $candidate;
                            $existing->save();
                        } catch (\Exception $e) {
                            $errors[] = "Row {$rowIndex}: Failed to rename existing tindakan: " . $e->getMessage();
                            $skipped++;
                            continue;
                        }
                    }

                    // Try to create the new tindakan with the original name
                    try {
                        Tindakan::create([
                            'nama' => $baseName,
                            'deskripsi' => null,
                            'harga' => $hargaVal ?? 0,
                            'harga_diskon' => $hargaDiskonVal,
                                'diskon_active' => ($hargaDiskonVal !== null) ? 1 : 0,
                            'spesialis_id' => $spesialis_id,
                                'is_active' => ($isActiveVal !== null) ? $isActiveVal : 1,
                            'harga_3_kali' => $harga3xVal,
                        ]);
                        $created++;
                        $createdThis = true;
                    } catch (\Exception $e) {
                        // If creation fails due to duplicate (race), fall back to copy-suffix strategy
                        $msg = $e->getMessage();
                        if (stripos($msg, 'duplicate') !== false || stripos($msg, 'unique') !== false || stripos($msg, 'Integrity constraint') !== false) {
                            $attempt = 0;
                            while ($attempt < 10 && !$createdThis) {
                                $attemptName = $attempt === 0 ? $baseName . ' - copy' : ($baseName . ' - copy ' . ($attempt + 1));
                                try {
                                    Tindakan::create([
                                        'nama' => $attemptName,
                                        'deskripsi' => null,
                                        'harga' => $hargaVal ?? 0,
                                        'harga_diskon' => $hargaDiskonVal,
                                            'diskon_active' => ($hargaDiskonVal !== null) ? 1 : 0,
                                        'spesialis_id' => $spesialis_id,
                                            'is_active' => ($isActiveVal !== null) ? $isActiveVal : 1,
                                        'harga_3_kali' => $harga3xVal,
                                    ]);
                                    $created++;
                                    $createdThis = true;
                                } catch (\Exception $e2) {
                                    $attempt++;
                                    continue;
                                }
                            }
                            if (!$createdThis) {
                                $errors[] = "Row {$rowIndex}: Could not create unique name after multiple attempts for base name '{$baseName}'";
                                $skipped++;
                            }
                        } else {
                            $errors[] = "Row {$rowIndex}: " . $e->getMessage();
                            $skipped++;
                        }
                    }
                }
                fclose($handle);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'created' => $created, 'skipped' => $skipped, 'errors' => $errors]);
    }

    /**
     * Import Tindakan <-> KodeTindakan relations from CSV.
     * Expected CSV: two columns - left: tindakan name, right: kode tindakan (kode or nama)
     */
    public function importRelationsCsv(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv');
        $path = $file->getRealPath();

        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            if (($handle = fopen($path, 'r')) !== false) {
                $rowIndex = 0;
                while (($data = fgetcsv($handle, 0, ',')) !== false) {
                    $rowIndex++;
                    // skip empty rows
                    if (!isset($data[0]) || trim($data[0]) === '') {
                        continue;
                    }

                    // Accept two-column CSV: tindakan_name, kode_tindakan_identifier
                    $tindakanName = trim($data[0]);
                    $kodeIdentifier = isset($data[1]) ? trim($data[1]) : null;

                    if (!$tindakanName || !$kodeIdentifier) {
                        $errors[] = "Row {$rowIndex}: Missing tindakan name or kode tindakan";
                        $skipped++;
                        continue;
                    }

                    // find tindakan by exact name
                    $tindakan = Tindakan::where('nama', $tindakanName)->first();
                    if (!$tindakan) {
                        $errors[] = "Row {$rowIndex}: Tindakan not found: '{$tindakanName}'";
                        $skipped++;
                        continue;
                    }
                    // ensure tindakan is active
                    if (isset($tindakan->is_active) && !$tindakan->is_active) {
                        $errors[] = "Row {$rowIndex}: Tindakan is inactive, skipping: '{$tindakanName}'";
                        $skipped++;
                        continue;
                    }

                    // Try to find KodeTindakan by kode first, then by nama
                    $kode = \App\Models\ERM\KodeTindakan::where('kode', $kodeIdentifier)->first();
                    if (!$kode) {
                        $kode = \App\Models\ERM\KodeTindakan::where('nama', $kodeIdentifier)->first();
                    }
                    if (!$kode) {
                        $errors[] = "Row {$rowIndex}: KodeTindakan not found: '{$kodeIdentifier}'";
                        $skipped++;
                        continue;
                    }
                    // ensure kode tindakan is active
                    if (isset($kode->is_active) && !$kode->is_active) {
                        $errors[] = "Row {$rowIndex}: KodeTindakan is inactive, skipping: '{$kodeIdentifier}'";
                        $skipped++;
                        continue;
                    }

                    // Attach relation without detaching existing ones
                    try {
                        $tindakan->kodeTindakans()->syncWithoutDetaching([$kode->id]);
                        $created++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowIndex}: Failed to attach relation: " . $e->getMessage();
                        $skipped++;
                    }
                }
                fclose($handle);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import relations failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'created' => $created, 'skipped' => $skipped, 'errors' => $errors]);
    }

    /**
     * Return tindakan created in a date range for preview (AJAX)
     */
    public function getByDate(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);
        $from = \Carbon\Carbon::parse($request->input('date_from'))->startOfDay();
        $to = \Carbon\Carbon::parse($request->input('date_to'))->endOfDay();

        $items = Tindakan::whereBetween('created_at', [$from, $to])->orderBy('created_at')->get(['id','nama','created_at','is_active']);
        $rows = $items->map(function($t){
            return [
                'id' => $t->id,
                'nama' => $t->nama,
                'created_at' => $t->created_at ? $t->created_at->toDateTimeString() : null,
                'is_active' => (bool)$t->is_active,
            ];
        });
        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Bulk set `is_active` by ids or date range
     */
    public function bulkSetActive(Request $request)
    {
        $request->validate([
            'set_active' => 'required|boolean',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $set = $request->input('set_active') ? 1 : 0;
        DB::beginTransaction();
        try {
            $query = Tindakan::query();
            if ($request->filled('ids')) {
                $ids = $request->input('ids');
                $count = $query->whereIn('id', $ids)->update(['is_active' => $set]);
            } elseif ($request->filled('date_from') && $request->filled('date_to')) {
                $from = \Carbon\Carbon::parse($request->input('date_from'))->startOfDay();
                $to = \Carbon\Carbon::parse($request->input('date_to'))->endOfDay();
                $count = $query->whereBetween('created_at', [$from, $to])->update(['is_active' => $set]);
            } else {
                return response()->json(['success' => false, 'message' => 'Either ids or date range required'], 400);
            }
            DB::commit();
            return response()->json(['success' => true, 'updated' => $count]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a tindakan.
     */
    public function destroy($id)
    {
        try {
            $tindakan = Tindakan::findOrFail($id);
            
            // Check if tindakan is used in any paket
            $isUsed = $tindakan->paketTindakan()->exists();
            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete tindakan because it is included in one or more paket tindakan.'
                ], 400);
            }
            
            // Check if tindakan has any inform consents
            if ($tindakan->informConsent()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete tindakan because it has associated inform consents.'
                ], 400);
            }
            
            $tindakan->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Tindakan deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of paket tindakan.
     */
    public function indexPaket()
    {
        return view('marketing.tindakan.paket-index');
    }

    /**
     * Get paket tindakan data for DataTables.
     */
    public function getPaketData(Request $request)
    {
        $paketTindakan = PaketTindakan::with('tindakan');

        return DataTables::of($paketTindakan)
            ->addColumn('tindakan_list', function ($row) {
                $tindakanNames = $row->tindakan->pluck('nama')->toArray();
                return implode(', ', $tindakanNames);
            })
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-primary btn-sm edit-paket" data-id="'.$row->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-danger btn-sm delete-paket" data-id="'.$row->id.'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new paket tindakan.
     */
    public function createPaket()
    {
        $tindakan = Tindakan::all();
        return view('marketing.tindakan.paket-form', compact('tindakan'));
    }

    /**
     * Store a newly created paket tindakan or update an existing one.
     */
    public function storePaket(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:erm_paket_tindakan,id',
            'nama' => ['required', 'string', 'max:255', 
                Rule::unique('erm_paket_tindakan')->ignore($request->id)],
            'deskripsi' => 'nullable|string',
            'harga_paket' => 'required|numeric|min:0',
            'tindakan_ids' => 'required|array|min:1',
            'tindakan_ids.*' => 'exists:erm_tindakan,id',
        ]);

        try {
            DB::beginTransaction();
            
            $paketTindakan = PaketTindakan::updateOrCreate(
                ['id' => $request->id],
                [
                    'nama' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga_paket' => $request->harga_paket,
                ]
            );

            // Sync the tindakan relationships
            $paketTindakan->tindakan()->sync($request->tindakan_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paket Tindakan has been ' . ($request->id ? 'updated' : 'created') . ' successfully!',
                'data' => $paketTindakan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paket tindakan data by ID.
     */
    public function getPaket($id)
    {
        $paketTindakan = PaketTindakan::with('tindakan')->findOrFail($id);
        $tindakanIds = $paketTindakan->tindakan->pluck('id')->toArray();
        
        $response = [
            'paket' => $paketTindakan,
            'tindakan_ids' => $tindakanIds
        ];
        
        return response()->json($response);
    }

    /**
     * Delete a paket tindakan.
     */
    public function destroyPaket($id)
    {
        try {
            $paketTindakan = PaketTindakan::findOrFail($id);
            
            // Check if paket has any billings
            if ($paketTindakan->billing()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete paket tindakan because it has associated billings.'
                ], 400);
            }
            
            // Delete the relationship with tindakan
            $paketTindakan->tindakan()->detach();
            
            // Delete the paket
            $paketTindakan->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Paket Tindakan deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of specialists for dropdown.
     */
    public function getSpesialisasiList()
    {
        $spesialisasi = \App\Models\ERM\Spesialisasi::select('id', 'nama')->get();
        return response()->json($spesialisasi);
    }

    /**
     * Get list of tindakan for dropdown.
     */
    public function getTindakanList()
    {
        $tindakan = Tindakan::with('spesialis')->orderBy('nama')->get();
        return response()->json($tindakan);
    }

    public function searchTindakan(Request $request)
{
    $search = $request->input('q');
    $spesialisasiId = $request->input('spesialisasi_id');
    
    $query = \App\Models\ERM\Tindakan::with('spesialis')
        ->when($spesialisasiId, function($q) use ($spesialisasiId) {
            $q->where('spesialis_id', $spesialisasiId);
        })
        ->when($search, function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%");
        })
        ->orderBy('nama')
        ->limit(20)
        ->get();

    $results = [];
    foreach ($query as $tindakan) {
        $results[] = [
            'id' => $tindakan->id,
            'text' => $tindakan->nama, // Only display the tindakan name
            'harga' => $tindakan->harga, // <-- add harga to result
        ];
    }
    return response()->json(['results' => $results]);
}
}
