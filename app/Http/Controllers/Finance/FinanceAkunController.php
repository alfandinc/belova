<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceAkun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $journalBalanceSubquery = DB::table('finance_jurnal')
            ->selectRaw('akun_id, COALESCE(SUM(debet), 0) as total_debet, COALESCE(SUM(kredit), 0) as total_kredit')
            ->whereNull('deleted_at')
            ->groupBy('akun_id');

        $query = FinanceAkun::query()
            ->with('parent:id,kode_akun,nama_akun')
            ->leftJoinSub($journalBalanceSubquery, 'journal_balances', function ($join) {
                $join->on('journal_balances.akun_id', '=', 'finance_akun.id');
            })
            ->selectRaw('finance_akun.*, COALESCE(journal_balances.total_debet, 0) as total_debet, COALESCE(journal_balances.total_kredit, 0) as total_kredit');

        $type = trim((string) $request->input('tipe_akun', ''));
        if ($type !== '') {
            $query->where('tipe_akun', $type);
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '') {
            $query->where('is_active', $status === 'active');
        }

        $search = $request->get('search');
        $searchValue = is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '';

        if ($searchValue !== '') {
            $query->where(function ($subQuery) use ($searchValue) {
                $subQuery->where('kode_akun', 'like', "%{$searchValue}%")
                    ->orWhere('nama_akun', 'like', "%{$searchValue}%")
                    ->orWhere('tipe_akun', 'like', "%{$searchValue}%")
                    ->orWhereHas('parent', function ($parentQuery) use ($searchValue) {
                        $parentQuery->where('kode_akun', 'like', "%{$searchValue}%")
                            ->orWhere('nama_akun', 'like', "%{$searchValue}%");
                    });
            });
        }

        $accounts = $query
            ->orderBy('kode_akun')
            ->get();

        $accountsById = $accounts->keyBy('id');
        $childrenByParent = $accounts->groupBy('parent_id');
        $rollup = [];

        $calculateRollup = function ($accountId) use (&$calculateRollup, &$rollup, $accountsById, $childrenByParent) {
            if (isset($rollup[$accountId])) {
                return $rollup[$accountId];
            }

            $account = $accountsById->get($accountId);
            if (!$account) {
                return ['debet' => 0.0, 'kredit' => 0.0];
            }

            $debet = (float) ($account->total_debet ?? 0);
            $kredit = (float) ($account->total_kredit ?? 0);

            foreach ($childrenByParent->get($accountId, collect()) as $child) {
                $childRollup = $calculateRollup($child->id);
                $debet += (float) $childRollup['debet'];
                $kredit += (float) $childRollup['kredit'];
            }

            $rollup[$accountId] = [
                'debet' => $debet,
                'kredit' => $kredit,
            ];

            return $rollup[$accountId];
        };

        $accounts->transform(function (FinanceAkun $account) use ($calculateRollup) {
            $totals = $calculateRollup($account->id);
            $account->rolled_total_debet = (float) $totals['debet'];
            $account->rolled_total_kredit = (float) $totals['kredit'];

            return $account;
        });

        return DataTables::of($accounts)
            ->addColumn('kode_display', function (FinanceAkun $akun) {
                $class = (int) $akun->level === 0 ? 'font-weight-bold text-dark' : 'text-dark';
                $indentLevel = max(0, (int) $akun->level);
                $indentPx = $indentLevel * 26;

                return '<div style="padding-left:' . $indentPx . 'px;" class="' . $class . '">' . e($akun->kode_akun) . '</div>';
            })
            ->addColumn('nama_display', function (FinanceAkun $akun) {
                $indentLevel = max(0, (int) $akun->level);
                $indentPx = $indentLevel * 26;
                $nameClass = $indentLevel === 0 ? 'font-weight-bold text-dark' : 'text-dark';

                return '<div style="padding-left:' . $indentPx . 'px;" class="' . $nameClass . '">' . e($akun->nama_akun) . '</div>';
            })
            ->addColumn('saldo_display', function (FinanceAkun $akun) {
                $tipeAkun = strtolower((string) ($akun->tipe_akun ?? ''));
                $normalDebitTypes = ['asset', 'expense', 'beban'];
                $usesDebitNormal = in_array($tipeAkun, $normalDebitTypes, true);
                $saldo = $usesDebitNormal
                    ? ((float) $akun->rolled_total_debet - (float) $akun->rolled_total_kredit)
                    : ((float) $akun->rolled_total_kredit - (float) $akun->rolled_total_debet);
                $weightClass = (int) $akun->level === 0 ? 'font-weight-bold' : 'font-weight-normal';
                $colorClass = $saldo < 0 ? 'text-danger' : 'text-dark';

                return '<div class="text-right ' . $weightClass . ' ' . $colorClass . '">Rp ' . number_format($saldo, 2, ',', '.') . '</div>';
            })
            ->addColumn('actions_display', function (FinanceAkun $akun) {
                return '<div class="d-flex justify-content-center" style="gap:.4rem;">'
                    . '<button type="button" class="btn btn-outline-primary btn-sm btn-edit-akun" data-id="' . e((string) $akun->id) . '"><i class="fas fa-pen"></i></button>'
                    . '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-akun" data-id="' . e((string) $akun->id) . '" data-label="' . e($akun->kode_akun . ' - ' . $akun->nama_akun) . '"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['kode_display', 'nama_display', 'saldo_display', 'actions_display'])
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
