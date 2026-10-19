<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceRevenueTarget;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Revenue;
use App\Support\Dashboard\RevenueTargets;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Monthly revenue target per clinic. Edited from Laporan Omset ("Atur Target" modal, Admin / Finance);
 * the old Target Revenue page URL redirects there and opens the modal.
 */
class FinanceRevenueTargetController extends Controller
{
    /** Old page URL: Laporan Omset with the target modal open on the same month */
    public function legacy(Request $request): RedirectResponse
    {
        return redirect()->route('finance.omset.index', [
            'target' => $this->resolvePeriod($request->query('period'))->format('Y-m'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->payload($this->resolvePeriod($request->query('period'))));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'targets' => ['required', 'array'],
            'targets.*.amount' => ['nullable', 'numeric', 'min:0'],
            'targets.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $period = $this->resolvePeriod($validated['period']);
        $clinicIds = Clinics::all()->keys()->map(fn ($id) => (int) $id);

        DB::transaction(function () use ($validated, $clinicIds, $period) {
            foreach ($validated['targets'] as $clinicId => $input) {
                if (! $clinicIds->contains((int) $clinicId)) {
                    continue;
                }

                $amount = trim((string) ($input['amount'] ?? ''));
                $notes = trim((string) ($input['notes'] ?? ''));
                $key = [
                    'klinik_id' => (int) $clinicId,
                    'periode_bulan' => (int) $period->month,
                    'periode_tahun' => (int) $period->year,
                ];

                // empty amount = no target for that clinic this month
                if ($amount === '') {
                    FinanceRevenueTarget::query()->where($key)->delete();

                    continue;
                }

                FinanceRevenueTarget::query()->updateOrCreate($key, [
                    'target_amount' => round((float) $amount, 2),
                    'notes' => $notes !== '' ? $notes : null,
                ]);
            }
        });

        return response()->json($this->payload($period) + [
            'message' => 'Target ' . $period->translatedFormat('F Y') . ' berhasil disimpan.',
        ]);
    }

    private function resolvePeriod(?string $periodValue): Carbon
    {
        if (is_string($periodValue) && preg_match('/^\d{4}-\d{2}$/', $periodValue) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d', $periodValue . '-01')->startOfMonth();
            } catch (\Throwable $exception) {
            }
        }

        return now()->startOfMonth();
    }

    private function payload(Carbon $period): array
    {
        $targets = FinanceRevenueTarget::query()
            ->where('periode_tahun', (int) $period->year)
            ->where('periode_bulan', (int) $period->month)
            ->get()
            ->keyBy('klinik_id');

        // omset (net of returs) of the whole month: klinik_id => total, same as Laporan Omset
        $omset = Revenue::byKlinik($period->copy()->startOfMonth(), $period->copy()->endOfMonth());

        $clinics = Clinics::all()->values()->map(function ($klinik) use ($targets, $omset) {
            $target = $targets->get($klinik->id);
            $amount = $target ? (float) $target->target_amount : null;
            $actual = (float) ($omset[$klinik->id] ?? 0);

            return [
                'id' => (int) $klinik->id,
                'nama' => $klinik->nama,
                'color' => $klinik->color,
                'logo' => $klinik->logo,
                'target_amount' => $target ? (string) round($amount) : '',
                'target_notes' => $target->notes ?? '',
                'updated_at' => $target?->updated_at?->translatedFormat('d M Y H:i'),
                'actual' => $actual,
                'pct' => $amount > 0 ? $actual / $amount * 100 : null,
            ];
        });

        return [
            'period' => $period->format('Y-m'),
            'period_label' => $period->translatedFormat('F Y'),
            // days left in that month from today (0 = month over), for "perlu Rp …/hari"
            'days_left' => RevenueTargets::daysLeft($period, $period),
            'clinics' => $clinics,
        ];
    }
}
