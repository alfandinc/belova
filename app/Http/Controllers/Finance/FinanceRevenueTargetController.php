<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\Finance\FinanceRevenueTarget;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceRevenueTargetController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $period = $this->resolvePeriod($request->query('period'));
        $payload = $this->buildIndexPayload($period);

        if ($request->expectsJson()) {
            return response()->json($this->serializePayload($payload));
        }

        return view('finance.revenue_targets.index', $payload);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'clinic_ids' => ['required', 'array'],
            'clinic_ids.*' => ['required', 'integer', 'exists:erm_klinik,id'],
            'targets' => ['nullable', 'array'],
            'targets.*.amount' => ['nullable', 'numeric', 'min:0'],
            'targets.*.notes' => ['nullable', 'string'],
        ]);

        $period = $this->resolvePeriod($validated['period']);
        $clinicIds = collect($validated['clinic_ids'])
            ->map(fn ($clinicId) => (int) $clinicId)
            ->unique()
            ->values();
        $submittedTargets = collect($validated['targets'] ?? []);
        $changedClinicIds = [];

        DB::transaction(function () use ($clinicIds, $submittedTargets, $period, &$changedClinicIds) {
            foreach ($clinicIds as $clinicId) {
                $targetPayload = $submittedTargets->get((string) $clinicId, $submittedTargets->get($clinicId, []));
                $amount = trim((string) ($targetPayload['amount'] ?? ''));
                $notes = trim((string) ($targetPayload['notes'] ?? ''));
                $target = FinanceRevenueTarget::query()
                    ->where('klinik_id', $clinicId)
                    ->where('periode_bulan', (int) $period->month)
                    ->where('periode_tahun', (int) $period->year)
                    ->first();
                $existingAmount = $target ? number_format((float) $target->target_amount, 2, '.', '') : '';
                $incomingAmount = $amount !== '' ? number_format(round((float) $amount, 2), 2, '.', '') : '';
                $existingNotes = trim((string) ($target->notes ?? ''));
                $hasChanged = $existingAmount !== $incomingAmount || $existingNotes !== $notes;

                if ($hasChanged) {
                    $changedClinicIds[] = $clinicId;
                }

                if ($amount === '') {
                    if ($target) {
                        $target->delete();
                    }

                    continue;
                }

                FinanceRevenueTarget::query()->updateOrCreate(
                    [
                        'klinik_id' => $clinicId,
                        'periode_bulan' => (int) $period->month,
                        'periode_tahun' => (int) $period->year,
                    ],
                    [
                        'target_amount' => round((float) $amount, 2),
                        'notes' => $notes !== '' ? $notes : null,
                    ]
                );
            }
        });

        $message = 'Target revenue berhasil disimpan.';

        if ($request->expectsJson()) {
            $payload = $this->buildIndexPayload($period);
            $responsePayload = $this->serializePayload($payload);
            $responsePayload['message'] = $message;
            $responsePayload['saved_clinic_ids'] = array_values(array_unique($changedClinicIds));

            return response()->json($responsePayload, Response::HTTP_OK);
        }

        return redirect()
            ->route('finance.revenue-targets.index', ['period' => $period->format('Y-m')])
            ->with('status', $message);
    }

    private function resolvePeriod(?string $periodValue): Carbon
    {
        if (is_string($periodValue) && preg_match('/^\d{4}-\d{2}$/', $periodValue) === 1) {
            try {
                return Carbon::createFromFormat('Y-m', $periodValue)->startOfMonth();
            } catch (\Throwable $exception) {
            }
        }

        return now()->startOfMonth();
    }

    private function buildIndexPayload(Carbon $period): array
    {
        $periodYear = (int) $period->year;
        $periodMonth = (int) $period->month;
        $periodStart = $period->copy()->startOfMonth();
        $periodEnd = $period->copy()->endOfMonth();

        $targetsByClinic = FinanceRevenueTarget::query()
            ->where('periode_tahun', $periodYear)
            ->where('periode_bulan', $periodMonth)
            ->get()
            ->keyBy('klinik_id');

        $revenueByClinic = DB::table('finance_invoices as fi')
            ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->whereNotNull('fi.payment_date')
            ->whereIn('fi.status', ['paid', 'partial'])
            ->whereBetween('fi.payment_date', [$periodStart, $periodEnd])
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, COALESCE(SUM(fi.total_amount), 0) as revenue_total')
            ->get()
            ->keyBy('klinik_id');

        $clinics = Klinik::query()
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(function (Klinik $clinic) use ($targetsByClinic, $revenueByClinic) {
                $target = $targetsByClinic->get($clinic->id);
                $revenue = $revenueByClinic->get($clinic->id);

                $clinic->target_amount = $target ? (float) $target->target_amount : null;
                $clinic->target_amount_input = $target ? number_format((float) $target->target_amount, 2, '.', '') : '';
                $clinic->target_notes = $target->notes ?? '';
                $clinic->target_updated_at = $target?->updated_at;
                $clinic->actual_revenue = (float) ($revenue->revenue_total ?? 0);
                $clinic->achievement_percentage = ($clinic->target_amount ?? 0) > 0
                    ? ($clinic->actual_revenue / $clinic->target_amount) * 100
                    : null;

                return $clinic;
            });

        return [
            'clinics' => $clinics,
            'period' => $period,
            'periodValue' => $period->format('Y-m'),
            'totalTarget' => (float) $clinics->sum(function (Klinik $clinic) {
                return $clinic->target_amount ?? 0;
            }),
            'totalActualRevenue' => (float) $clinics->sum(function (Klinik $clinic) {
                return $clinic->actual_revenue ?? 0;
            }),
            'configuredClinicCount' => (int) $clinics->filter(function (Klinik $clinic) {
                return ($clinic->target_amount ?? 0) > 0;
            })->count(),
        ];
    }

    private function serializePayload(array $payload): array
    {
        return [
            'period_label' => $payload['period']->translatedFormat('F Y'),
            'period_value' => $payload['periodValue'],
            'total_target' => (float) $payload['totalTarget'],
            'total_actual_revenue' => (float) $payload['totalActualRevenue'],
            'configured_clinic_count' => (int) $payload['configuredClinicCount'],
            'clinic_count' => (int) $payload['clinics']->count(),
            'clinics' => $payload['clinics']->map(function (Klinik $clinic) {
                return [
                    'id' => (int) $clinic->id,
                    'nama' => $clinic->nama,
                    'target_amount_input' => $clinic->target_amount_input,
                    'target_notes' => $clinic->target_notes,
                    'actual_revenue' => (float) ($clinic->actual_revenue ?? 0),
                    'achievement_percentage' => $clinic->achievement_percentage !== null
                        ? (float) $clinic->achievement_percentage
                        : null,
                    'target_updated_at_label' => $clinic->target_updated_at
                        ? $clinic->target_updated_at->translatedFormat('d M Y H:i')
                        : '-',
                ];
            })->values(),
        ];
    }
}
