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
     * Kategori => [badge color, klinik ids it is offered for (null = every klinik),
     * jenis kunjungan it aims at (null = any visit counts, e.g. a birthday greeting)].
     * Klinik ids: 1 Premiere Belova, 2 Belova Skin, 3 Belova Dental.
     * Old follow ups keep their kategori even when it is no longer offered for their klinik.
     */
    public const KATEGORI = [
        'Produk' => ['color' => 'primary', 'kliniks' => [1, 2], 'jenis' => Visitation::TYPE_PRODUK],
        // Perawatan (tindakan) happens inside a konsultasi visit
        'Perawatan' => ['color' => 'pink', 'kliniks' => [2, 3], 'jenis' => Visitation::TYPE_KONSULTASI],
        // Premiere: follow up after a konsultasi that had no perawatan
        'Konsultasi' => ['color' => 'success', 'kliniks' => [1], 'jenis' => Visitation::TYPE_KONSULTASI],
        'Reseller' => ['color' => 'warning', 'kliniks' => [2], 'jenis' => Visitation::TYPE_RESELLER],
        'Slimming' => ['color' => 'purple', 'kliniks' => [2], 'jenis' => Visitation::TYPE_SLIMMING],
        self::KATEGORI_ULANG_TAHUN => ['color' => 'info', 'kliniks' => null, 'jenis' => null],
    ];

    /** Registration modal mode (Daftarkan Kunjungan) per jenis kunjungan a kategori can aim at. */
    private const JENIS_MODE = [
        Visitation::TYPE_KONSULTASI => 'konsultasi',
        Visitation::TYPE_PRODUK => 'produk',
        Visitation::TYPE_RESELLER => 'reseller',
        Visitation::TYPE_SLIMMING => 'slimming',
    ];

    /** Offered for the klinik: listed in KATEGORI and its jenis kunjungan is on in Klinik Setting > Layanan. */
    public static function kategoriOfferedFor(string $kategori, $klinikId): bool
    {
        $kliniks = self::KATEGORI[$kategori]['kliniks'] ?? null;
        if ($kliniks !== null && !in_array((int) $klinikId, $kliniks, true)) {
            return false;
        }
        $jenis = self::KATEGORI[$kategori]['jenis'] ?? null;

        return $jenis === null || \App\Models\ERM\KlinikLayanan::tersedia($klinikId, $jenis);
    }

    /** Jenis kunjungan the kategori aim at; empty = any visit counts (no kategori, or one like Ulang Tahun). */
    public static function jenisFor(array $kategori): array
    {
        $jenis = [];
        foreach ($kategori as $k) {
            $j = self::KATEGORI[$k]['jenis'] ?? null;
            if ($j === null) {
                return [];
            }
            $jenis[] = $j;
        }

        return array_values(array_unique($jenis));
    }

    /** Registration modal mode for the follow up's first kategori that aims at a jenis (konsultasi otherwise). */
    public static function modeFor(array $kategori): string
    {
        foreach ($kategori as $k) {
            $j = self::KATEGORI[$k]['jenis'] ?? null;
            if ($j !== null && isset(self::JENIS_MODE[$j])) {
                return self::JENIS_MODE[$j];
            }
        }

        return 'konsultasi';
    }

    /**
     * Label of the booked visit's jenis when it is not one the kategori aim at ("jenis lain"), else null.
     * Booking stays Sukses either way (any visit at the klinik counts); this only marks it.
     */
    public static function jenisLainLabel(array $kategori, $bookedJenis): ?string
    {
        if ($bookedJenis === null || $bookedJenis === '') {
            return null;
        }
        $jenis = self::jenisFor($kategori);

        return $jenis && !in_array((int) $bookedJenis, $jenis, true) ? Visitation::typeLabel($bookedJenis) : null;
    }

    /**
     * Adds booked_visit_id + booked_jenis: the visit that booked the follow up, the same visit markBookedBy picks:
     * one registered from it (referral "Follow Up"), else the pasien's first visit at its klinik registered within
     * BOOKING_WINDOW_DAYS after it. Not cancelled. Only meaningful for status_booking Sukses.
     */
    public function scopeWithBookedJenis($query)
    {
        return $query->addSelect([
            'booked_visit_id' => self::bookedVisitSub('bv.id'),
            'booked_jenis' => self::bookedVisitSub('bv.jenis_kunjungan'),
        ]);
    }

    /** Adds booked_omset: invoice total of the booked visit (see omsetPerVisit), for the table rows. */
    public function scopeWithBookedOmset($query)
    {
        $sub = \App\Models\Finance\Invoice::query()
            ->from('finance_invoices as bfi')
            ->selectRaw('SUM(GREATEST(COALESCE(bfi.total_amount, 0) - COALESCE(bfi.retur_amount, 0), 0))')
            ->where(fn ($q) => $q->whereNull('bfi.status')->orWhere('bfi.status', '!=', 'canceled'))
            ->where('bfi.visitation_id', '=', self::bookedVisitSub('bv.id')->toBase());

        return $query->addSelect(['booked_omset' => $sub]);
    }

    /**
     * Omset per booked visit: invoice total after approved returs, cancelled invoices left out.
     * @return array visitation_id => rupiah
     */
    public static function omsetPerVisit($visitIds): array
    {
        $visitIds = collect($visitIds)->filter()->unique()->values();
        if ($visitIds->isEmpty()) {
            return [];
        }

        return \App\Models\Finance\Invoice::whereIn('visitation_id', $visitIds)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'canceled'))
            ->selectRaw('visitation_id, SUM(GREATEST(COALESCE(total_amount, 0) - COALESCE(retur_amount, 0), 0)) as omset')
            ->groupBy('visitation_id')
            ->pluck('omset', 'visitation_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /** One column of the booked visit, for scopeWithBookedJenis. */
    private static function bookedVisitSub(string $column)
    {
        $fu = 'marketing_follow_ups';

        return Visitation::query()
            ->from('erm_visitations as bv')
            ->select($column)
            ->whereColumn('bv.pasien_id', "$fu.pasien_id")
            ->where(fn ($q) => $q->whereNull('bv.status_kunjungan')->orWhere('bv.status_kunjungan', '!=', Visitation::STATUS_DIBATALKAN))
            ->where(function ($q) use ($fu) {
                $q->where(fn ($r) => $r->where('bv.referral_type', \App\Models\ERM\Pasien::REFERRAL_TYPE_FOLLOW_UP)
                        ->whereColumn('bv.referralable_id', \Illuminate\Support\Facades\DB::raw("CAST($fu.id AS CHAR)")))
                    ->orWhere(fn ($r) => $r->whereColumn('bv.created_at', '>=', "$fu.created_at")
                        ->whereRaw("bv.created_at <= DATE_ADD($fu.created_at, INTERVAL " . self::BOOKING_WINDOW_DAYS . ' DAY)')
                        ->where(fn ($k) => $k->whereNull("$fu.klinik_id")->orWhereColumn('bv.klinik_id', "$fu.klinik_id")));
            })
            ->orderByRaw("(bv.referral_type = ? AND bv.referralable_id = CAST($fu.id AS CHAR)) DESC", [\App\Models\ERM\Pasien::REFERRAL_TYPE_FOLLOW_UP])
            ->orderBy('bv.created_at')
            ->limit(1);
    }

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
