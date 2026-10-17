<?php

namespace App\Models\Marketing;

use App\Models\ERM\Visitation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $table = 'marketing_follow_ups';

    /** Kategori of the follow up logged when a birthday greeting is sent. */
    public const KATEGORI_ULANG_TAHUN = 'Ulang Tahun';

    /**
     * Kategori => [badge color, klinik ids it is offered for (null = every klinik)].
     * Old follow ups keep their kategori even when it is no longer offered for their klinik.
     */
    public const KATEGORI = [
        'Produk' => ['color' => 'primary', 'kliniks' => null],
        'Perawatan' => ['color' => 'pink', 'kliniks' => null],
        'Reseller' => ['color' => 'warning', 'kliniks' => null],
        'Slimming' => ['color' => 'purple', 'kliniks' => null],
        self::KATEGORI_ULANG_TAHUN => ['color' => 'info', 'kliniks' => null],
    ];

    /** Status respon: "Menunggu" = contacted, no reply yet. */
    public const STATUS_RESPON = ['Direspon', 'Tidak Direspon', 'Menunggu'];

    protected $fillable = [
        'pasien_id',
        'klinik_id',
        'kategori',
        'sales_id',
        'status_respon',
        'bukti_respon',
        'rencana_tindak_lanjut',
        'status_booking',
        'catatan',
    ];

    public function pasien()
    {
        return $this->belongsTo(\App\Models\ERM\Pasien::class, 'pasien_id');
    }

    public function klinik()
    {
        return $this->belongsTo(\App\Models\ERM\Klinik::class, 'klinik_id');
    }

    public function sales()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'sales_id')->withInactive();
    }

    /** Days a follow up stays open: a visit registered within them counts as its booking. */
    public const BOOKING_WINDOW_DAYS = 14;

    /**
     * A new visit is the booking of a follow up: the one it was registered from (referral "Follow Up"),
     * otherwise the pasien's latest still-open follow up of the last BOOKING_WINDOW_DAYS at the same klinik.
     * Booking becomes Sukses; a reply still "Menunggu" becomes Direspon (the pasien answered by booking).
     */
    public static function markBookedBy(Visitation $visitation): void
    {
        if ((int) $visitation->status_kunjungan === Visitation::STATUS_DIBATALKAN) {
            return;
        }

        if ($visitation->referral_type === \App\Models\ERM\Pasien::REFERRAL_TYPE_FOLLOW_UP) {
            $followUp = $visitation->referralable_id ? self::find($visitation->referralable_id) : null;
        } else {
            $followUp = self::where('pasien_id', $visitation->pasien_id)
                ->where('created_at', '>=', now()->subDays(self::BOOKING_WINDOW_DAYS))
                ->where(fn ($q) => $q->whereNull('status_booking')->orWhere('status_booking', 'Menunggu'))
                ->where(fn ($q) => $q->whereNull('klinik_id')->orWhere('klinik_id', $visitation->klinik_id))
                ->latest()
                ->first();
        }
        if (!$followUp) {
            return;
        }

        $followUp->status_booking = 'Sukses';
        if ($followUp->status_respon === 'Menunggu') {
            $followUp->status_respon = 'Direspon';
        }
        $followUp->save();
    }

    /** Klinik of the pasien's latest (not cancelled) visit: the default klinik of a new follow up. */
    public static function lastKlinikId($pasienId): ?int
    {
        $klinikId = Visitation::where('pasien_id', $pasienId)
            ->where(fn ($q) => $q->whereNull('status_kunjungan')->orWhere('status_kunjungan', '!=', Visitation::STATUS_DIBATALKAN))
            ->orderByDesc('tanggal_visitation')
            ->value('klinik_id');

        return $klinikId ? (int) $klinikId : null;
    }
}
