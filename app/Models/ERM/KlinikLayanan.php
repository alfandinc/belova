<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Layanan (jenis kunjungan) a klinik provides. No row = aktif, so only switched-off layanan are really stored;
 * before the migration ran every klinik provides every layanan.
 */
class KlinikLayanan extends Model
{
    protected $table = 'erm_klinik_layanan';

    protected $fillable = ['klinik_id', 'jenis_kunjungan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    /** Every jenis kunjungan, in the order shown on the mapping tab. */
    public const JENIS = [
        Visitation::TYPE_KONSULTASI,
        Visitation::TYPE_SLIMMING,
        Visitation::TYPE_VAKSIN,
        Visitation::TYPE_MCU,
        Visitation::TYPE_LAB,
        Visitation::TYPE_PRODUK,
        Visitation::TYPE_RESELLER,
        Visitation::TYPE_MARKETPLACE,
        Visitation::TYPE_EVENT,
    ];

    public static function tracked(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('erm_klinik_layanan');
    }

    /** klinik_id => [jenis kunjungan switched off] (for the registration modal). */
    public static function nonaktifMap(): array
    {
        if (!self::tracked()) {
            return [];
        }

        return self::where('aktif', false)->get(['klinik_id', 'jenis_kunjungan'])
            ->groupBy('klinik_id')
            ->map(fn ($rows) => $rows->pluck('jenis_kunjungan')->map(fn ($j) => (int) $j)->values()->all())
            ->all();
    }

    public static function tersedia($klinikId, $jenis): bool
    {
        if (!self::tracked() || !$klinikId) {
            return true;
        }

        return !self::where('klinik_id', $klinikId)->where('jenis_kunjungan', (int) $jenis)->where('aktif', false)->exists();
    }

    /** Validation message when the klinik does not provide the jenis kunjungan, or null when it does. */
    public static function errorFor($klinikId, $jenis): ?string
    {
        if (self::tersedia($klinikId, $jenis)) {
            return null;
        }
        $klinik = Klinik::find($klinikId);

        return 'Layanan ' . Visitation::typeLabel($jenis) . ' tidak tersedia di ' . ($klinik->nama ?? 'klinik ini')
            . '. Pilih klinik lain atau aktifkan di Admin > Klinik Setting > tab Layanan.';
    }

    /** Layanan of one klinik for the setting tab: [{jenis, label, aktif}]. */
    public static function forKlinik($klinikId): array
    {
        $off = $klinikId && self::tracked()
            ? self::where('klinik_id', $klinikId)->where('aktif', false)->pluck('jenis_kunjungan')->map(fn ($j) => (int) $j)->all()
            : [];

        return array_map(fn ($jenis) => [
            'jenis' => $jenis,
            'label' => Visitation::typeLabel($jenis),
            'aktif' => !in_array($jenis, $off, true),
        ], self::JENIS);
    }

    /** Save the klinik's layanan from the list of active jenis (others are switched off). */
    public static function simpan(int $klinikId, array $aktifJenis): void
    {
        if (!self::tracked()) {
            return;
        }
        $aktifJenis = array_map('intval', $aktifJenis);
        foreach (self::JENIS as $jenis) {
            self::updateOrCreate(
                ['klinik_id' => $klinikId, 'jenis_kunjungan' => $jenis],
                ['aktif' => in_array($jenis, $aktifJenis, true)]
            );
        }
    }
}
