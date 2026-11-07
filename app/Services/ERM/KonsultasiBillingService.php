<?php

namespace App\Services\ERM;

use App\Models\ERM\Konsultasi;
use App\Models\ERM\RadiologiPermintaan;
use App\Models\ERM\RadiologiTest;
use App\Models\ERM\Visitation;
use App\Models\Finance\Billing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Billing of the jenis konsultasi the dokter picks (Asesmen / CPPT), with the radiologi picked in the same popup,
 * so the USG is recorded in Radiologi (permintaan + its own billing) while the dokter clicks once:
 * - tambahan: tests the dokter ticks under the konsultasi (e.g. + USG Cepat), billed at their harga tambahan;
 *   one done by the dokter himself (dikerjakan_dokter, USG penyakit dalam) is selesai at once, Lab not notified;
 * - paket: a konsultasi that includes radiologi (old "+ USG" items): its part of the harga is billed on the
 *   permintaan, the konsultasi billing being the rest.
 */
class KonsultasiBillingService
{
    /**
     * @param int[]|null $tambahanIds radiologi tests ticked in the popup; null = not sent, leave the tambahan as is
     * @return string[] notes for the dokter (radiologi removed, or already processed so kept)
     */
    public function apply(string $visitationId, Konsultasi $jasa, ?array $tambahanIds = null): array
    {
        // Payment processed: the bill stands. The dokter's document is still saved, so only tell when this differs.
        if (\App\Models\Finance\Invoice::isLockedForVisitation($visitationId)) {
            return $this->differsFromBill($visitationId, $jasa, $tambahanIds)
                ? ['Pembayaran kunjungan ini sudah diproses, jadi jenis konsultasi dan radiologi tambahan di tagihan tidak berubah. Buat kunjungan baru untuk layanan tambahan.']
                : [];
        }

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

        if (!Konsultasi::hasRadiologiPaket()) {
            return [];
        }

        $notes = [];
        $added = [];
        $this->syncPaket($visitationId, $jasa, $tests, array_map('intval', $tambahanIds ?? []), $notes, $added);
        if ($tambahanIds !== null) {
            $this->syncTambahan($visitationId, $jasa, $tambahanIds, $notes, $added);
        }

        if ($added) {
            // Callers save inside a transaction: notify only once it is committed
            DB::afterCommit(fn () => $this->notifyRadiologi($visitationId, $added));
        }

        return $notes;
    }

    private function differsFromBill(string $visitationId, Konsultasi $jasa, ?array $tambahanIds): bool
    {
        $billed = Billing::where('visitation_id', $visitationId)
            ->where('billable_type', Konsultasi::class)
            ->whereHasMorph('billable', [Konsultasi::class], fn ($query) => $query->whereRaw('LOWER(nama) LIKE ?', ['%konsultasi%']))
            ->orderBy('id')
            ->value('billable_id');
        if ((int) $billed !== (int) $jasa->id) {
            return true;
        }

        if ($tambahanIds === null || !Konsultasi::hasRadiologiPaket()) {
            return false;
        }

        $current = RadiologiPermintaan::where('visitation_id', $visitationId)
            ->where('sumber_konsultasi', RadiologiPermintaan::SUMBER_TAMBAHAN)
            ->pluck('radiologi_test_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $wanted = collect($tambahanIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return $current !== $wanted;
    }

    /** Tambahan the popup offers to this visit, and the ones already ticked: [[{id, nama, harga}], [ids]] */
    public static function tambahanUntuk(Visitation $visitation): array
    {
        $options = RadiologiTest::tambahanUntuk($visitation->resolvedSpesialisasiId())
            ->map(fn ($t) => ['id' => (string) $t->id, 'nama' => $t->nama, 'harga' => $t->hargaTambahan()])->values();
        $terpilih = $options->isEmpty() ? collect() : RadiologiPermintaan::where('visitation_id', $visitation->id)
            ->where('sumber_konsultasi', RadiologiPermintaan::SUMBER_TAMBAHAN)
            ->pluck('radiologi_test_id')->map(fn ($id) => (string) $id)->values();

        return [$options->all(), $terpilih->all()];
    }

    /**
     * Radiologi of a paket konsultasi: those of the old paket not in the new one are removed while still "Diminta"
     * (already processed ones stay, as a normal permintaan with their billing); the paket tests are created, or
     * taken over when already requested, at their paket harga.
     */
    private function syncPaket(string $visitationId, Konsultasi $jasa, Collection $tests, array $tambahanIds, array &$notes, array &$added): void
    {
        $existing = $this->existing($visitationId);
        $wanted = $tests->keyBy('id');

        foreach ($existing->where('sumber_konsultasi', RadiologiPermintaan::SUMBER_PAKET) as $testId => $permintaan) {
            if ($wanted->has($testId)) {
                continue;
            }
            if (in_array((int) $testId, $tambahanIds, true) && $permintaan->radiologiTest) {
                // Old paket item -> konsultasi + the same test ticked as tambahan: keep the permintaan, at the test harga
                $this->simpan($visitationId, $jasa, $permintaan, $permintaan->radiologiTest, $permintaan->radiologiTest->hargaTambahan(),
                    RadiologiPermintaan::SUMBER_TAMBAHAN, '', $added);
            } else {
                $this->lepas($permintaan, 'paket konsultasi sebelumnya', $notes);
            }
        }

        foreach ($wanted as $testId => $test) {
            $this->simpan($visitationId, $jasa, $existing->get($testId), $test, (float) $test->pivot->harga,
                RadiologiPermintaan::SUMBER_PAKET, ' (paket ' . $jasa->nama . ')', $added);
        }
    }

    /**
     * Tambahan ticked in the popup: unticked ones are removed while still "Diminta"; ticked ones are created at the
     * test harga, unless the test is already requested for this visit (paket, E-Radiologi): no double billing.
     */
    private function syncTambahan(string $visitationId, Konsultasi $jasa, array $tambahanIds, array &$notes, array &$added): void
    {
        $existing = $this->existing($visitationId);
        $wanted = RadiologiTest::whereIn('id', array_map('intval', $tambahanIds))->get()->keyBy('id');

        foreach ($existing->where('sumber_konsultasi', RadiologiPermintaan::SUMBER_TAMBAHAN) as $testId => $permintaan) {
            if (!$wanted->has($testId)) {
                $this->lepas($permintaan, 'tambahan konsultasi', $notes);
            } elseif ((int) $permintaan->konsultasi_id !== (int) $jasa->id) {
                $permintaan->update(['konsultasi_id' => $jasa->id]);
            }
        }

        foreach ($wanted as $testId => $test) {
            $permintaan = $existing->get($testId);
            if (!$permintaan) {
                $this->simpan($visitationId, $jasa, null, $test, $test->hargaTambahan(), RadiologiPermintaan::SUMBER_TAMBAHAN, '', $added);
            } elseif (!$permintaan->sumber_konsultasi) {
                // Already requested in E-Radiologi: now ticked here, it follows the popup at the harga tambahan (one
                // billing, no double); once paid the invoice stays as it is
                if ($this->dibayar($permintaan)) {
                    $permintaan->update(['konsultasi_id' => $jasa->id, 'sumber_konsultasi' => RadiologiPermintaan::SUMBER_TAMBAHAN]);
                } else {
                    $this->simpan($visitationId, $jasa, $permintaan, $test, $test->hargaTambahan(), RadiologiPermintaan::SUMBER_TAMBAHAN, '', $added);
                }
            }
        }
    }

    private function existing(string $visitationId): Collection
    {
        return RadiologiPermintaan::with('radiologiTest')->where('visitation_id', $visitationId)->get()
            ->keyBy('radiologi_test_id');
    }

    /**
     * No longer chosen: removed while still "Diminta" (or done by the dokter himself: unticked = not done), else kept
     * as a normal permintaan (Lab already works on it). Never removed once paid.
     */
    private function lepas(RadiologiPermintaan $permintaan, string $asal, array &$notes): void
    {
        $nama = optional($permintaan->radiologiTest)->nama;
        if ($this->dibayar($permintaan)) {
            // Removing it would leave a paid invoice without its permintaan: keep it, the kasir handles a refund
            $permintaan->update(['konsultasi_id' => null, 'sumber_konsultasi' => null]);
            $notes[] = "{$nama} sudah dibayar di invoice, jadi tidak dihapus. Hubungi kasir bila perlu dibatalkan.";
        } elseif ($permintaan->status === 'requested' || optional($permintaan->radiologiTest)->dikerjakan_dokter) {
            $permintaan->billings()->delete();
            $permintaan->delete();
            $notes[] = "Permintaan {$nama} dari {$asal} dihapus; minta lagi bila tetap diperlukan.";
        } else {
            $permintaan->update(['konsultasi_id' => null, 'sumber_konsultasi' => null]);
            $notes[] = "{$nama} sudah diproses radiologi, jadi tetap ditagih.";
        }
    }

    /** In a paid / partly paid invoice */
    private function dibayar(RadiologiPermintaan $permintaan): bool
    {
        return DB::table('finance_invoice_items as ii')->join('finance_invoices as i', 'i.id', '=', 'ii.invoice_id')
            ->where('ii.billable_type', RadiologiPermintaan::class)->where('ii.billable_id', $permintaan->id)
            ->whereIn('i.status', ['paid', 'partial'])->exists();
    }

    /** Creates the permintaan with its billing, or takes over the existing one at this harga */
    private function simpan(string $visitationId, Konsultasi $jasa, ?RadiologiPermintaan $permintaan, RadiologiTest $test,
        float $harga, string $sumber, string $suffix, array &$added): void
    {
        $billingValues = [
            'visitation_id' => $visitationId,
            'jumlah' => $harga,
            'keterangan' => 'Radiologi: ' . $test->nama . $suffix,
        ];

        if ($permintaan) {
            $permintaan->update(['konsultasi_id' => $jasa->id, 'sumber_konsultasi' => $sumber]);
            $billing = $permintaan->billings()->first();
            $billing ? $billing->update($billingValues) : $permintaan->billings()->save(new Billing($billingValues));

            return;
        }

        $permintaan = RadiologiPermintaan::create([
            'visitation_id' => $visitationId,
            'radiologi_test_id' => $test->id,
            'konsultasi_id' => $jasa->id,
            'sumber_konsultasi' => $sumber,
            // Done by the dokter during the konsultasi: selesai, nothing for Lab to do
            'status' => $test->dikerjakan_dokter ? 'completed' : 'requested',
            'dokter_id' => Auth::id(),
        ]);
        $permintaan->billings()->save(new Billing($billingValues));
        if (!$test->dikerjakan_dokter) {
            $added[] = $test->nama;
        }
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
            Log::error('Radiologi konsultasi notification failed: ' . $e->getMessage(), ['visitation_id' => $visitationId]);
        }
    }
}
