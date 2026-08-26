<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceAkun;
use App\Models\Finance\FinanceJurnal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class FinanceJurnalController extends Controller
{
    public function index()
    {
        $entryStats = DB::table('finance_jurnal')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(DISTINCT NULLIF(no_jurnal, "")) as grouped_entries')
            ->selectRaw('SUM(CASE WHEN no_jurnal IS NULL OR no_jurnal = "" THEN 1 ELSE 0 END) as ungrouped_entries')
            ->first();

        $summary = [
            'total_rows' => FinanceJurnal::count(),
            'total_entries' => (int) (($entryStats->grouped_entries ?? 0) + ($entryStats->ungrouped_entries ?? 0)),
            'total_debet' => (float) FinanceJurnal::sum('debet'),
            'total_kredit' => (float) FinanceJurnal::sum('kredit'),
            'draft_rows' => FinanceJurnal::whereNull('pos')->count(),
        ];

        $akunOptions = FinanceAkun::query()
            ->where('is_active', true)
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun']);

        return view('finance.jurnal.index', compact('summary', 'akunOptions'));
    }

    public function data(Request $request)
    {
        $query = DB::table('finance_jurnal as fj')
            ->leftJoin('finance_akun as fa', 'fa.id', '=', 'fj.akun_id')
            ->leftJoin('users as u', 'u.id', '=', 'fj.user_id')
            ->whereNull('fj.deleted_at')
            ->select([
                'fj.id',
                'fj.no_jurnal',
                'fj.tanggal',
                'fj.ref_id',
                'fj.keterangan',
                'fj.debet',
                'fj.kredit',
                'fj.pos',
                'u.name as user_name',
                'fa.kode_akun',
                'fa.nama_akun',
            ])
            ->selectRaw('COALESCE(NULLIF(fj.no_jurnal, ""), CONCAT("ROW-", fj.id)) as journal_key');

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('fj.tanggal', [$startDate, $endDate]);
        }

        $akunId = $request->input('akun_id');
        if ($akunId) {
            $query->where('fj.akun_id', $akunId);
        }

        $pos = trim((string) $request->input('pos', ''));
        if ($pos !== '') {
            if ($pos === 'draft') {
                $query->whereNull('fj.pos');
            } else {
                $query->where('fj.pos', $pos);
            }
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = $request->get('search');
                $value = is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '';

                if ($value === '') {
                    return;
                }

                $query->where(function ($subQuery) use ($value) {
                    $subQuery->where('fj.no_jurnal', 'like', "%{$value}%")
                        ->orWhere('fj.keterangan', 'like', "%{$value}%")
                        ->orWhere('fj.ref_id', 'like', "%{$value}%")
                        ->orWhere('fa.kode_akun', 'like', "%{$value}%")
                        ->orWhere('fa.nama_akun', 'like', "%{$value}%")
                        ->orWhere('u.name', 'like', "%{$value}%");
                });
            })
            ->order(function ($query) {
                $query->orderBy('fj.tanggal', 'asc')
                    ->orderByRaw('COALESCE(NULLIF(fj.no_jurnal, ""), CONCAT("ROW-", fj.id)) asc')
                    ->orderBy('fj.id', 'asc');
            })
            ->addColumn('tanggal_display', function ($jurnal) {
                return $jurnal->tanggal ? date('d M Y', strtotime($jurnal->tanggal)) : '-';
            })
            ->addColumn('akun_display', function ($jurnal) {
                $akun = trim(implode(' - ', array_filter([
                    $jurnal->kode_akun,
                    $jurnal->nama_akun,
                ], function ($value) {
                    return $value !== null && $value !== '';
                })));

                return $akun !== ''
                    ? '<div class="font-weight-bold">' . e($akun) . '</div>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('no_jurnal_display', function ($jurnal) {
                return '<div class="font-weight-bold">' . e($jurnal->no_jurnal ?: ('Draft #' . $jurnal->id)) . '</div>';
            })
            ->addColumn('referensi_display', function ($jurnal) {
                return '<div class="text-muted small">' . e($jurnal->ref_id ?: '-') . '</div>';
            })
            ->addColumn('debet_display', function ($jurnal) {
                $value = (float) ($jurnal->debet ?? 0);

                return '<div class="text-right text-success font-weight-bold">' . ($value > 0 ? number_format($value, 2, ',', '.') : '-') . '</div>';
            })
            ->addColumn('kredit_display', function ($jurnal) {
                $value = (float) ($jurnal->kredit ?? 0);

                return '<div class="text-right text-danger font-weight-bold">' . ($value > 0 ? number_format($value, 2, ',', '.') : '-') . '</div>';
            })
            ->addColumn('actions_display', function ($jurnal) {
                return '<div class="d-flex justify-content-center" style="gap:.4rem;">'
                    . '<button type="button" class="btn btn-outline-info btn-sm btn-view-jurnal" data-id="' . e((string) $jurnal->id) . '"><i class="fas fa-eye"></i></button>'
                    . '<button type="button" class="btn btn-outline-primary btn-sm btn-edit-jurnal" data-id="' . e((string) $jurnal->id) . '"><i class="fas fa-pen"></i></button>'
                    . '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-jurnal" data-id="' . e((string) $jurnal->id) . '" data-label="' . e($jurnal->no_jurnal ?: ('Draft #' . $jurnal->id)) . '"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
                ->rawColumns(['akun_display', 'no_jurnal_display', 'referensi_display', 'debet_display', 'kredit_display', 'actions_display'])
            ->make(true);
    }

    public function show(FinanceJurnal $jurnal): JsonResponse
    {
        $rows = $this->resolveJournalRows($jurnal);
        $header = $rows->first();

        return response()->json([
            'data' => [
                'representative_id' => (int) $jurnal->id,
                'no_jurnal' => $header?->no_jurnal,
                'tanggal' => optional($header?->tanggal)->format('Y-m-d'),
                'ref_id' => $header?->ref_id,
                'keterangan' => $header?->keterangan,
                'user_name' => optional($header?->user)->name,
                'lines' => $rows->map(function (FinanceJurnal $row) {
                    return [
                        'id' => (int) $row->id,
                        'akun_id' => (int) $row->akun_id,
                        'akun_label' => optional($row->akun)->kode_akun . ' - ' . optional($row->akun)->nama_akun,
                        'debet' => number_format((float) $row->debet, 2, '.', ''),
                        'kredit' => number_format((float) $row->kredit, 2, '.', ''),
                        'pos' => $row->pos,
                    ];
                })->values(),
                'totals' => [
                    'debet' => (float) $rows->sum('debet'),
                    'kredit' => (float) $rows->sum('kredit'),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateJournalRequest($request);
        $payload = $this->buildJournalPayload($validated);

        DB::transaction(function () use ($payload) {
            foreach ($payload['lines'] as $line) {
                FinanceJurnal::create([
                    'no_jurnal' => $payload['header']['no_jurnal'],
                    'tanggal' => $payload['header']['tanggal'],
                    'akun_id' => $line['akun_id'],
                    'debet' => $line['debet'],
                    'kredit' => $line['kredit'],
                    'keterangan' => $payload['header']['keterangan'],
                    'ref_id' => $payload['header']['ref_id'],
                    'user_id' => $payload['header']['user_id'],
                    'pos' => $line['pos'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Jurnal berhasil disimpan dan balance.',
        ]);
    }

    public function update(Request $request, FinanceJurnal $jurnal): JsonResponse
    {
        $validated = $this->validateJournalRequest($request, $jurnal);
        $payload = $this->buildJournalPayload($validated, $jurnal);

        DB::transaction(function () use ($jurnal, $payload) {
            $this->resolveJournalQuery($jurnal)->delete();

            foreach ($payload['lines'] as $line) {
                FinanceJurnal::create([
                    'no_jurnal' => $payload['header']['no_jurnal'],
                    'tanggal' => $payload['header']['tanggal'],
                    'akun_id' => $line['akun_id'],
                    'debet' => $line['debet'],
                    'kredit' => $line['kredit'],
                    'keterangan' => $payload['header']['keterangan'],
                    'ref_id' => $payload['header']['ref_id'],
                    'user_id' => $payload['header']['user_id'],
                    'pos' => $line['pos'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Jurnal berhasil diperbarui.',
        ]);
    }

    public function destroy(FinanceJurnal $jurnal): JsonResponse
    {
        $this->resolveJournalQuery($jurnal)->delete();

        return response()->json([
            'message' => 'Jurnal berhasil dihapus.',
        ]);
    }

    private function validateJournalRequest(Request $request, ?FinanceJurnal $journal = null): array
    {
        $validated = $request->validate([
            'no_jurnal' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'ref_id' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['required', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.akun_id' => ['required', 'integer', 'exists:finance_akun,id'],
            'lines.*.debet' => ['nullable', 'numeric', 'min:0'],
            'lines.*.kredit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $currentNoJurnal = $journal?->no_jurnal;
        $incomingNoJurnal = trim((string) $validated['no_jurnal']);

        $duplicateQuery = FinanceJurnal::query()
            ->where('no_jurnal', $incomingNoJurnal);

        if ($journal && $currentNoJurnal) {
            $duplicateQuery->where('no_jurnal', '!=', $currentNoJurnal);
        }

        if (!$journal || $incomingNoJurnal !== $currentNoJurnal) {
            if ($duplicateQuery->exists()) {
                throw ValidationException::withMessages([
                    'no_jurnal' => 'Nomor jurnal sudah digunakan.',
                ]);
            }
        }

        return $validated;
    }

    private function buildJournalPayload(array $validated, ?FinanceJurnal $journal = null): array
    {
        $lines = collect($validated['lines'])
            ->map(function ($line, $index) {
                $akunId = (int) ($line['akun_id'] ?? 0);
                $debet = round((float) ($line['debet'] ?? 0), 2);
                $kredit = round((float) ($line['kredit'] ?? 0), 2);

                if ($debet <= 0 && $kredit <= 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'Baris jurnal ke-' . ($index + 1) . ' harus memiliki nilai debit atau kredit.',
                    ]);
                }

                if ($debet > 0 && $kredit > 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'Baris jurnal ke-' . ($index + 1) . ' tidak boleh mengisi debit dan kredit sekaligus.',
                    ]);
                }

                return [
                    'akun_id' => $akunId,
                    'debet' => $debet,
                    'kredit' => $kredit,
                    'pos' => $debet > 0 ? 'D' : 'K',
                ];
            });

        $totalDebet = round((float) $lines->sum('debet'), 2);
        $totalKredit = round((float) $lines->sum('kredit'), 2);

        if ($totalDebet <= 0 || $totalKredit <= 0) {
            throw ValidationException::withMessages([
                'lines' => 'Jurnal harus memiliki minimal satu nilai debit dan satu nilai kredit.',
            ]);
        }

        if (abs($totalDebet - $totalKredit) > 0.009) {
            throw ValidationException::withMessages([
                'lines' => 'Total debit dan total kredit harus balance sebelum jurnal disimpan.',
            ]);
        }

        return [
            'header' => [
                'no_jurnal' => trim((string) $validated['no_jurnal']),
                'tanggal' => $validated['tanggal'],
                'ref_id' => $this->normalizeNullableString($validated['ref_id'] ?? null),
                'keterangan' => trim((string) $validated['keterangan']),
                'user_id' => optional(request()->user())->id,
            ],
            'lines' => $lines->values()->all(),
        ];
    }

    private function resolveJournalRows(FinanceJurnal $jurnal)
    {
        return $this->resolveJournalQuery($jurnal)
            ->with(['akun:id,kode_akun,nama_akun', 'user:id,name'])
            ->orderBy('id')
            ->get();
    }

    private function resolveJournalQuery(FinanceJurnal $jurnal)
    {
        if ($jurnal->no_jurnal) {
            return FinanceJurnal::query()->where('no_jurnal', $jurnal->no_jurnal);
        }

        return FinanceJurnal::query()->whereKey($jurnal->id);
    }

    private function normalizeNullableString($value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }
}
