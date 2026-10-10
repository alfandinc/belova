<?php

namespace App\View\Composers;

use App\Models\ERM\Dokter;
use App\Models\ERM\Klinik;
use App\Models\ERM\MetodeBayar;
use App\Models\HRD\Employee;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Supplies the shared "Daftarkan Kunjungan" modal (erm.rawatjalans.partials.modal-daftar-kunjungan)
 * with its option lists, so every page that includes it (rawat jalan, pasien, billing) behaves the same.
 */
class DaftarKunjunganModalComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();

        if (!isset($data['kliniks'])) {
            $view->with('kliniks', Klinik::select('id', 'nama')->orderBy('nama')->get());
        }

        // Active metode bayar with their Umum / Asuransi group, for the Cara Bayar toggle.
        $view->with('visitMetodeBayar', MetodeBayar::cachedList()->where('is_active', true)->values());

        [$employees, $dokters, $events] = self::referralOptions();

        $view->with([
            'referralEmployees' => $employees,
            'referralDokters' => $dokters,
            'referralEvents' => $events,
        ]);
    }

    /**
     * Cached option lists for referral pickers: [employees, dokters, events].
     */
    public static function referralOptions(): array
    {
        $employees = Cache::remember('erm_referral_employees', 300, function () {
            return Employee::select('id', 'nama', 'no_induk')->orderBy('nama')->get();
        });

        $dokters = Cache::remember('erm_referral_dokters', 300, function () {
            // Only for choosing a new referral: a saved one is shown as text, so dokter nonaktif can go
            return Dokter::active()->with(['user:id,name', 'spesialisasi:id,nama'])->get()->sortBy(function ($dokter) {
                return strtolower((string) ($dokter->user->name ?? ''));
            })->values();
        });

        $events = Cache::remember('erm_referral_events', 300, function () {
            return MarketingEvent::select('id', 'nama_event', 'kode_event')->orderBy('nama_event')->get();
        });

        return [$employees, $dokters, $events];
    }
}
