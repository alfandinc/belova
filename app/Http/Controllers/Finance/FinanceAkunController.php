<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceAkun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class FinanceAkunController extends Controller
{
    public function index()
    {
        $summary = [
            'total' => FinanceAkun::count(),
            'active' => FinanceAkun::where('is_active', true)->count(),
            'headers' => FinanceAkun::whereNull('parent_id')->count(),
            'detail' => FinanceAkun::whereNotNull('parent_id')->count(),
        ];

        $types = FinanceAkun::query()
            ->whereNotNull('tipe_akun')
            ->where('tipe_akun', '!=', '')
            ->select('tipe_akun')
            ->distinct()
            ->orderBy('tipe_akun')
            ->pluck('tipe_akun');

        $parentOptions = FinanceAkun::query()
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun', 'level']);

        return view('finance.akun.index', compact('summary', 'types', 'parentOptions'));
    }

    public function data(Request $request)
    {
        $query = FinanceAkun::query()
            ->with('parent:id,kode_akun,nama_akun')
            ->withCount('jurnal')
            ->select('finance_akun.*');

        $type = trim((string) $request->input('tipe_akun', ''));
        if ($type !== '') {
            $query->where('tipe_akun', $type);
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '') {
            $query->where('is_active', $status === 'active');
        }

        $level = $request->input('level');
        if ($level !== null && $level !== '') {
            $query->where('level', (int) $level);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = $request->get('search');
                $value = is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '';

                if ($value === '') {
                    return;
                }

                $query->where(function ($subQuery) use ($value) {
                    $subQuery->where('kode_akun', 'like', "%{$value}%")
                        ->orWhere('nama_akun', 'like', "%{$value}%")
                        ->orWhere('tipe_akun', 'like', "%{$value}%")
                        ->orWhere('level', 'like', "%{$value}%")
                        ->orWhereHas('parent', function ($parentQuery) use ($value) {
                            $parentQuery->where('kode_akun', 'like', "%{$value}%")
                                ->orWhere('nama_akun', 'like', "%{$value}%");
                        });
                });
            })
            ->addColumn('kode_nama_display', function (FinanceAkun $akun) {
                return '<div class="font-weight-bold">' . e($akun->kode_akun) . '</div>'
                    . '<div class="text-muted small">' . e($akun->nama_akun) . '</div>';
            })
            ->addColumn('parent_display', function (FinanceAkun $akun) {
                if (!$akun->parent) {
                    return '<span class="text-muted">Akun Induk</span>';
                }

                return '<div>' . e($akun->parent->kode_akun) . '</div>'
                    . '<div class="text-muted small">' . e($akun->parent->nama_akun) . '</div>';
            })
            ->addColumn('type_display', function (FinanceAkun $akun) {
                $label = $akun->tipe_akun ?: '-';
                return '<span class="badge badge-info">' . e($label) . '</span>';
            })
            ->addColumn('level_display', function (FinanceAkun $akun) {
                return '<span class="badge badge-light border">Level ' . e((string) $akun->level) . '</span>';
            })
            ->addColumn('status_display', function (FinanceAkun $akun) {
                if ($akun->is_active) {
                    return '<span class="badge badge-success">Active</span>';
                }

                return '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('usage_display', function (FinanceAkun $akun) {
                return '<div class="text-right font-weight-bold">' . number_format((int) $akun->jurnal_count, 0, ',', '.') . '</div>';
            })
            ->addColumn('actions_display', function (FinanceAkun $akun) {
                return '<div class="d-flex justify-content-center" style="gap:.4rem;">'
                    . '<button type="button" class="btn btn-outline-primary btn-sm btn-edit-akun" data-id="' . e((string) $akun->id) . '"><i class="fas fa-pen"></i></button>'
                    . '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-akun" data-id="' . e((string) $akun->id) . '" data-label="' . e($akun->kode_akun . ' - ' . $akun->nama_akun) . '"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['kode_nama_display', 'parent_display', 'type_display', 'level_display', 'status_display', 'usage_display', 'actions_display'])
            ->make(true);
    }

    public function show(FinanceAkun $akun): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => (int) $akun->id,
                'parent_id' => $akun->parent_id ? (int) $akun->parent_id : null,
                'kode_akun' => $akun->kode_akun,
                'nama_akun' => $akun->nama_akun,
                'tipe_akun' => $akun->tipe_akun,
                'level' => (int) $akun->level,
                'is_active' => (bool) $akun->is_active,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateAkun($request);
        $akun = FinanceAkun::create($this->buildAkunPayload($validated));

        return response()->json([
            'message' => 'Akun berhasil disimpan.',
            'data' => [
                'id' => (int) $akun->id,
                'kode_akun' => $akun->kode_akun,
                'nama_akun' => $akun->nama_akun,
            ],
        ]);
    }

    public function update(Request $request, FinanceAkun $akun): JsonResponse
    {
        $validated = $this->validateAkun($request, $akun);
        $akun->update($this->buildAkunPayload($validated, $akun));

        return response()->json([
            'message' => 'Akun berhasil diperbarui.',
            'data' => [
                'id' => (int) $akun->id,
                'kode_akun' => $akun->kode_akun,
                'nama_akun' => $akun->nama_akun,
            ],
        ]);
    }

    public function destroy(FinanceAkun $akun): JsonResponse
    {
        if ($akun->children()->exists()) {
            throw ValidationException::withMessages([
                'akun' => 'Akun tidak bisa dihapus karena masih memiliki akun turunan.',
            ]);
        }

        if ($akun->jurnal()->exists()) {
            throw ValidationException::withMessages([
                'akun' => 'Akun tidak bisa dihapus karena sudah dipakai pada jurnal.',
            ]);
        }

        $akun->delete();

        return response()->json([
            'message' => 'Akun berhasil dihapus.',
        ]);
    }

    private function validateAkun(Request $request, ?FinanceAkun $akun = null): array
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:finance_akun,id'],
            'kode_akun' => [
                'required',
                'string',
                'max:100',
                Rule::unique('finance_akun', 'kode_akun')->ignore($akun?->id),
            ],
            'nama_akun' => ['required', 'string', 'max:255'],
            'tipe_akun' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($akun && $parentId !== null) {
            $parentId = (int) $parentId;

            if ($parentId === (int) $akun->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent akun tidak boleh sama dengan akun yang sedang diedit.',
                ]);
            }

            if ($this->createsParentCycle($akun, $parentId)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent akun tidak valid karena membentuk siklus hierarki.',
                ]);
            }
        }

        return $validated;
    }

    private function buildAkunPayload(array $validated, ?FinanceAkun $akun = null): array
    {
        $parentId = $validated['parent_id'] ?? null;
        $parent = $parentId ? FinanceAkun::query()->findOrFail($parentId) : null;

        return [
            'parent_id' => $parent?->id,
            'kode_akun' => trim((string) $validated['kode_akun']),
            'nama_akun' => trim((string) $validated['nama_akun']),
            'tipe_akun' => $this->normalizeNullableString($validated['tipe_akun'] ?? null),
            'level' => $parent ? ((int) $parent->level + 1) : 0,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ];
    }

    private function createsParentCycle(FinanceAkun $akun, int $candidateParentId): bool
    {
        $cursor = FinanceAkun::query()->find($candidateParentId);

        while ($cursor) {
            if ((int) $cursor->id === (int) $akun->id) {
                return true;
            }

            if (!$cursor->parent_id) {
                return false;
            }

            $cursor = FinanceAkun::query()->find($cursor->parent_id);
        }

        return false;
    }

    private function normalizeNullableString($value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }
}
