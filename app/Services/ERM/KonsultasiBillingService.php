<?php

namespace App\Services\ERM;

use App\Models\ERM\Konsultasi;
use App\Models\ERM\RadiologiPermintaan;
use App\Models\Finance\Billing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Billing of the jenis konsultasi the dokter picks (Asesmen / CPPT). A konsultasi may include radiologi (paket,
 * e.g. "Konsultasi PD + USG"): picking it creates those radiologi permintaan with their part of the harga, the
 * konsultasi billing being the rest, so the pasien pays the paket harga and the USG is recorded in Radiologi.
 */
class KonsultasiBillingService
{
    /** @return string[] notes for the dokter (radiologi of the old paket that could not be removed) */
    public function apply(string $visitationId, Konsultasi $jasa): array
    {
        $tests = Konsultasi::hasRadiologiPaket() ? $jasa->radiologiTests()->get() : collect();
        $jumlah = max(0, (float) $jasa->harga - (float) $tests->sum(fn ($t) => $t->pivot->harga));

        $consultationBillings = Billing::where('visitation_id', $visitationId)
            ->where('billable_type', Konsultasi::class)
            ->whereHasMorph('billable', [Konsultasi::class], function ($query) {
                $query->whereRaw('LOWER(nama) LIKE ?', ['%konsultasi%']);
            })
            ->orderBy('id')
            ->get();

        $billing = $consultationBillings->first();
        $values = [
            'billable_id' => $jasa->id,
            'billable_type' => Konsultasi::class,
            'jumlah' => $jumlah,
            'keterangan' => 'Tindakan: ' . $jasa->nama,
        ];

        if ($billing) {
            $billing->update($values);
            if ($consultationBillings->count() > 1) {
                Billing::whereIn('id', $consultationBillings->slice(1)->pluck('id'))->delete();
            }
        } else {
            Billing::create(['visitation_id' => $visitationId] + $values);
        }

        return Konsultasi::hasRadiologiPaket() ? $this->syncRadiologi($visitationId, $jasa, $tests) : [];
    }

    /**
     * Radiologi of the visit follow the paket: those of the old paket not in the new one are removed while still
     * "Diminta" (already processed ones stay, as a normal permintaan with their billing); the paket tests are
     * created, or taken over when the dokter had already requested them, at their paket harga.
     */
    private function syncRadiologi(string $visitationId, Konsultasi $jasa, Collection $tests): array
    {
        $existing = RadiologiPermintaan::with('radiologiTest:id,nama')->where('visitation_id', $visitationId)->get()
            ->keyBy('radiologi_test_id');
        $wanted = $tests->keyBy('id');
        $notes = [];
        $added = [];

        foreach ($existing->whereNotNull('konsultasi_id') as $testId => $permintaan) {
            if ($wanted->has($testId)) {
                continue;
            }
            if ($permintaan->status === 'requested') {
                $permintaan->billings()->delete();
                $permintaan->delete();
                $notes[] = 'Permintaan ' . optional($permintaan->radiologiTest)->nama . ' dari paket konsultasi sebelumnya dihapus; minta lagi di E-Radiologi bila tetap diperlukan.';
            } else {
                $permintaan->update(['konsultasi_id' => null]);
                $notes[] = optional($permintaan->radiologiTest)->nama . ' sudah diproses radiologi, jadi tetap ditagih.';
            }
        }

        foreach ($wanted as $testId => $test) {
            $billingValues = [
                'visitation_id' => $visitationId,
                'jumlah' => $test->pivot->harga,
                'keterangan' => 'Radiologi: ' . $test->nama . ' (paket ' . $jasa->nama . ')',
            ];
            $permintaan = $existing->get($testId);

            if ($permintaan) {
                $permintaan->update(['konsultasi_id' => $jasa->id]);
                $billing = $permintaan->billings()->first();
                $billing ? $billing->update($billingValues) : $permintaan->billings()->save(new Billing($billingValues));
                continue;
            }

            $permintaan = RadiologiPermintaan::create([
                'visitation_id' => $visitationId,
                'radiologi_test_id' => $testId,
                'konsultasi_id' => $jasa->id,
                'status' => 'requested',
                'dokter_id' => Auth::id(),
            ]);
            $permintaan->billings()->save(new Billing($billingValues));
            $added[] = $test->nama;
        }

        if ($added) {
            // Callers save inside a transaction: notify only once it is committed
            DB::afterCommit(fn () => $this->notifyRadiologi($visitationId, $added));
        }

        return $notes;
    }

    /** Same notification as a permintaan from E-Radiologi. Never fails the save. */
    private function notifyRadiologi(string $visitationId, array $testNames): void
    {
        try {
            $notif = \App\Support\ErmStaffNotifications::class;
            $notif::send(new \App\Notifications\RadiologiPermintaanNotification(
                $notif::permintaanMessage($visitationId, $testNames), optional(Auth::user())->name, $visitationId
            ), ['Lab']);
        } catch (\Throwable $e) {
            Log::error('Radiologi paket konsultasi notification failed: ' . $e->getMessage(), ['visitation_id' => $visitationId]);
        }
    }
}
