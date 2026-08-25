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
            ->groupBy(DB::raw('COALESCE(NULLIF(fj.no_jurnal, ""), CONCAT("ROW-", fj.id))'))
            ->selectRaw('MIN(fj.id) as representative_id')
            ->selectRaw('COALESCE(NULLIF(fj.no_jurnal, ""), CONCAT("ROW-", fj.id)) as journal_key')
            ->selectRaw('MAX(fj.no_jurnal) as no_jurnal')
            ->selectRaw('MAX(fj.tanggal) as tanggal')
            ->selectRaw('MAX(fj.ref_id) as ref_id')
            ->selectRaw('MAX(fj.keterangan) as keterangan')
            ->selectRaw('SUM(COALESCE(fj.debet, 0)) as total_debet')
            ->selectRaw('SUM(COALESCE(fj.kredit, 0)) as total_kredit')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM(CASE WHEN fj.pos IS NULL THEN 1 ELSE 0 END) as draft_line_count')
            ->selectRaw('MAX(u.name) as user_name')
            ->selectRaw("GROUP_CONCAT(DISTINCT CONCAT(COALESCE(fa.kode_akun, '-'), ' - ', COALESCE(fa.nama_akun, '-')) ORDER BY fa.kode_akun SEPARATOR '||') as akun_summary");

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
            ->addColumn('tanggal_display', function ($jurnal) {
                return $jurnal->tanggal ? date('d M Y', strtotime($jurnal->tanggal)) : '-';
            })
            ->addColumn('nomor_display', function ($jurnal) {
                $label = $jurnal->no_jurnal ?: ('Draft #' . $jurnal->representative_id);

                return '<div class="font-weight-bold">' . e($label) . '</div>'
                    . '<div class="text-muted small">Ref: ' . e($jurnal->ref_id ?: '-') . '</div>';
            })
            ->addColumn('akun_display', function ($jurnal) {
                if (!$jurnal->akun_summary) {
                    return '<span class="text-muted">-</span>';
                }

                $lines = array_filter(explode('||', (string) $jurnal->akun_summary));
                $html = collect($lines)->map(function ($line) {
                    return '<div class="text-muted small">' . e($line) . '</div>';
                })->implode('');

                return '<div class="font-weight-bold">' . e((string) $jurnal->line_count) . ' baris akun</div>' . $html;
            })
            ->addColumn('debet_display', function ($jurnal) {
                return '<div class="text-right text-success font-weight-bold">' . number_format((float) $jurnal->total_debet, 2, ',', '.') . '</div>';
            })
            ->addColumn('kredit_display', function ($jurnal) {
                return '<div class="text-right text-danger font-weight-bold">' . number_format((float) $jurnal->total_kredit, 2, ',', '.') . '</div>';
            })
            ->addColumn('status_display', function ($jurnal) {
                $balance = round((float) $jurnal->total_debet - (float) $jurnal->total_kredit, 2);

                if ((int) $jurnal->draft_line_count > 0) {
                    return '<span class="badge badge-warning">Draft</span>';
                }

                if ($balance === 0.0) {
                    return '<span class="badge badge-success">Balanced</span>';
                }

                return '<span class="badge badge-danger">Unbalanced</span>';
            })
            ->addColumn('balance_display', function ($jurnal) {
                $balance = (float) $jurnal->total_debet - (float) $jurnal->total_kredit;
                $class = abs($balance) < 0.005 ? 'text-success' : 'text-danger';

                return '<div class="text-right font-weight-bold ' . $class . '">' . number_format($balance, 2, ',', '.') . '</div>';
            })
            ->addColumn('user_display', function ($jurnal) {
                return e($jurnal->user_name ?: '-');
            })
            ->addColumn('actions_display', function ($jurnal) {
                return '<div class="d-flex justify-content-center" style="gap:.4rem;">'
                    . '<button type="button" class="btn btn-outline-info btn-sm btn-view-jurnal" data-id="' . e((string) $jurnal->representative_id) . '"><i class="fas fa-eye"></i></button>'
                    . '<button type="button" class="btn btn-outline-primary btn-sm btn-edit-jurnal" data-id="' . e((string) $jurnal->representative_id) . '"><i class="fas fa-pen"></i></button>'
                    . '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-jurnal" data-id="' . e((string) $jurnal->representative_id) . '" data-label="' . e($jurnal->no_jurnal ?: ('Draft #' . $jurnal->representative_id)) . '"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['nomor_display', 'akun_display', 'debet_display', 'kredit_display', 'status_display', 'balance_display', 'actions_display'])
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
