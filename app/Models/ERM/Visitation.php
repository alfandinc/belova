<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Visitation extends Model
{

    public const TYPE_KONSULTASI = 1;
    public const TYPE_PRODUK = 2;
    public const TYPE_LAB = 3;
    public const TYPE_EVENT = 4;
    public const TYPE_MARKETPLACE = 5;
    // Same flow as Beli Produk (buy, straight to farmasi), for resellers
    public const TYPE_RESELLER = 6;
    // Service visits with their own ERM page (shortcut on Rawat Jalan)
    public const TYPE_SLIMMING = 7;
    public const TYPE_VAKSIN = 8;
    public const TYPE_MCU = 9;

    // Visits examined by perawat + dokter (asesmen, CPPT). Other types are transactions (produk, lab, ...)
    public const CLINICAL_TYPES = [self::TYPE_KONSULTASI, self::TYPE_SLIMMING, self::TYPE_VAKSIN, self::TYPE_MCU];

    public const STATUS_DIBATALKAN = 7;

    // Badge colour per type (Rawat Jalan list + filter chips)
    public const TYPE_COLORS = [
        self::TYPE_KONSULTASI => '#1d63ed',
        self::TYPE_PRODUK => '#d39e00',
        self::TYPE_LAB => '#0aa2c0',
        self::TYPE_EVENT => '#e83e8c',
        self::TYPE_MARKETPLACE => '#343a40',
        self::TYPE_RESELLER => '#fd7e14',
        self::TYPE_SLIMMING => '#20a374',
        self::TYPE_VAKSIN => '#6f42c1',
        self::TYPE_MCU => '#c0392b',
    ];

    /**
     * Rawat Jalan jenis filter chips: key => [label, types]. '' = every type.
     *
     * @return array<string, array{label: string, types: int[]}>
     */
    public static function rawatJalanFilters(): array
    {
        return [
            '' => ['label' => 'Semua', 'types' => []],
            'klinis' => ['label' => 'Klinis', 'types' => self::CLINICAL_TYPES],
            '1' => ['label' => 'Konsultasi', 'types' => [self::TYPE_KONSULTASI]],
            '7' => ['label' => 'Slimming', 'types' => [self::TYPE_SLIMMING]],
            '8' => ['label' => 'Vaksin', 'types' => [self::TYPE_VAKSIN]],
            '9' => ['label' => 'MCU', 'types' => [self::TYPE_MCU]],
            'produk' => ['label' => 'Produk & Reseller', 'types' => [self::TYPE_PRODUK, self::TYPE_RESELLER]],
            '5' => ['label' => 'Marketplace', 'types' => [self::TYPE_MARKETPLACE]],
            '3' => ['label' => 'Lab', 'types' => [self::TYPE_LAB]],
            '4' => ['label' => 'Event', 'types' => [self::TYPE_EVENT]],
        ];
    }

    public static function isClinicalType($type): bool
    {
        return in_array((int) $type, self::CLINICAL_TYPES, true);
    }

    /**
     * Progress (0 belum datang, 1 menunggu dokter, 2 selesai) only moves forward; a cancelled visit stays cancelled.
     */
    public static function advanceStatus($current, int $target): int
    {
        $current = (int) $current;

        return $current === self::STATUS_DIBATALKAN ? $current : max($current, $target);
    }

    
    protected $table = 'erm_visitations';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
        'pasien_id',
        'metode_bayar_id',
        'dokter_id',
        'spesialisasi_id', // chosen at pendaftaran; null on old visits
        'user_id',
        'klinik_id',
        'status_kunjungan',
        'status_dokumen',
        'jenis_kunjungan',
        'tanggal_visitation',
        'waktu_kunjungan', // add this line
        'no_antrian',
        'masuk_ruangan_at', // perawat sent the patient into the dokter's room
        // Transaction referral: why the patient came for THIS visit.
        // The patient's source referral lives on erm_pasiens.
        'referral_type',
        'referral_detail',
        'referralable_type',
        'referralable_id',
    ];

    protected $casts = [
        'masuk_ruangan_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Visitation $visitation) {
            if (!empty($visitation->referral_type) || empty($visitation->pasien_id)) {
                return;
            }

            $visitation->forceFill(self::defaultReferralFor((string) $visitation->pasien_id));
        });

        // Spesialisasi must be one the dokter holds in this klinik; default to the first one
        static::saving(function (Visitation $visitation) {
            if (!self::tracksSpesialisasi()) {
                unset($visitation->spesialisasi_id);
                return;
            }

            if (empty($visitation->dokter_id)) {
                $visitation->spesialisasi_id = null;
                return;
            }

            if (!$visitation->isDirty(['dokter_id', 'klinik_id', 'spesialisasi_id']) && $visitation->exists) {
                return;
            }

            $options = Dokter::find($visitation->dokter_id)?->spesialisasiIdsForKlinik($visitation->klinik_id) ?? [];
            if (!in_array((int) $visitation->spesialisasi_id, $options, true)) {
                $visitation->spesialisasi_id = $options[0] ?? null;
            }
        });

        // A visit booked from a CRM follow up turns that follow up's booking into Sukses
        static::saved(function (Visitation $visitation) {
            if ($visitation->referral_type === Pasien::REFERRAL_TYPE_FOLLOW_UP
                && $visitation->referralable_id
                && ($visitation->wasRecentlyCreated || $visitation->wasChanged('referralable_id'))) {
                \App\Models\Marketing\FollowUp::whereKey($visitation->referralable_id)->update(['status_booking' => 'Sukses']);
            }
        });
    }

    /**
     * Default referral for a new visit: copy the patient's last visit referral,
     * or the patient's source referral when this is their first visit.
     * A follow up referral belongs to the one visit it booked, so it is never copied.
     */
    public static function defaultReferralFor(string $pasienId): array
    {
        $previous = self::query()
            ->where('pasien_id', $pasienId)
            ->whereNotNull('referral_type')
            ->where('referral_type', '!=', Pasien::REFERRAL_TYPE_FOLLOW_UP)
            ->orderByDesc('tanggal_visitation')
            ->orderByDesc('waktu_kunjungan')
            ->orderByDesc('created_at')
            ->first(['referral_type', 'referral_detail', 'referralable_type', 'referralable_id']);

        $source = $previous ?? Pasien::find($pasienId, ['referral_type', 'referral_detail', 'referralable_type', 'referralable_id']);
        if (($source->referral_type ?? null) === Pasien::REFERRAL_TYPE_FOLLOW_UP) {
            $source = null;
        }

        return [
            'referral_type' => $source->referral_type ?? Pasien::REFERRAL_TYPE_WALK_IN,
            'referral_detail' => $source->referral_detail ?? null,
            'referralable_type' => $source->referralable_type ?? null,
            'referralable_id' => $source->referralable_id ?? null,
        ];
    }

    /**
     * Whether this is the patient's earliest visit (whose referral must match the patient source).
     */
    public function isFirstVisitOfPasien(): bool
    {
        $firstId = self::query()
            ->where('pasien_id', $this->pasien_id)
            ->orderBy('tanggal_visitation')
            ->orderBy('waktu_kunjungan')
            ->orderBy('created_at')
            ->value('id');

        return (string) $firstId === (string) $this->id;
    }

    public function referralable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function typeLabel($type): string
    {
        return match ((int) $type) {
            self::TYPE_KONSULTASI => 'Konsultasi Dokter',
            self::TYPE_PRODUK => 'Beli Produk',
            self::TYPE_LAB => 'Laboratorium',
            self::TYPE_EVENT => 'Event',
            self::TYPE_MARKETPLACE => 'Marketplace',
            self::TYPE_RESELLER => 'Reseller',
            self::TYPE_SLIMMING => 'Slimming',
            self::TYPE_VAKSIN => 'Vaksin',
            self::TYPE_MCU => 'MCU',
            default => (string) $type,
        };
    }

    /**
     * Steps still missing before a service page (Slimming / Vaksin / MCU) may be opened, for every role:
     * the perawat's asesmen first, and for Vaksin also the screening vaksin. Empty = ready.
     *
     * @return string[]
     */
    public static function serviceBlockers($type, ?string $statusDokumen, bool $hasScreeningVaksin): array
    {
        if (!in_array((int) $type, [self::TYPE_SLIMMING, self::TYPE_VAKSIN, self::TYPE_MCU], true)) {
            return [];
        }

        return self::perawatStepsMissing($type, $statusDokumen, $hasScreeningVaksin);
    }

    /**
     * Perawat steps a clinical visit still needs before the patient goes to the dokter
     * (also gates the "Masuk" button): asesmen perawat, and for Vaksin the screening vaksin.
     *
     * @return string[]
     */
    public static function perawatStepsMissing($type, ?string $statusDokumen, bool $hasScreeningVaksin): array
    {
        return array_values(array_filter([
            empty($statusDokumen) ? 'asesmen perawat' : null,
            (int) $type === self::TYPE_VAKSIN && !$hasScreeningVaksin ? 'screening vaksin' : null,
        ]));
    }

    /** masuk_ruangan_at exists once its migration ran (prod schema may lag behind the code). */
    public static function tracksMasukRuangan(): bool
    {
        static $exists = null;

        return $exists ??= \Illuminate\Support\Facades\Schema::hasColumn('erm_visitations', 'masuk_ruangan_at');
    }

    /** spesialisasi_id exists once its migration ran (prod schema may lag behind the code). */
    public static function tracksSpesialisasi(): bool
    {
        static $exists = null;

        return $exists ??= \Illuminate\Support\Facades\Schema::hasColumn('erm_visitations', 'spesialisasi_id');
    }

    /**
     * SQL for a visit's spesialisasi id in list queries joining erm_dokters as $dokterAlias:
     * the one chosen at pendaftaran, else the dokter's default.
     */
    public static function spesialisasiIdSql(string $dokterAlias): \Illuminate\Database\Query\Expression
    {
        return \Illuminate\Support\Facades\DB::raw(self::tracksSpesialisasi()
            ? "COALESCE(erm_visitations.spesialisasi_id, {$dokterAlias}.spesialisasi_id)"
            : "{$dokterAlias}.spesialisasi_id");
    }

    /** serviceBlockers() for this visit, reading the screening from the database. */
    public function pendingServiceSteps(): array
    {
        $hasScreeningVaksin = (int) $this->jenis_kunjungan === self::TYPE_VAKSIN
            && ScreeningVaksin::where('visitation_id', $this->id)->exists();

        return self::serviceBlockers($this->jenis_kunjungan, $this->status_dokumen, $hasScreeningVaksin);
    }

    /**
     * ERM page of a service visit type (Slimming / Vaksin / MCU), or null when the type has none.
     *
     * @return array{label: string, icon: string, url: string}|null
     */
    public static function servicePage($type, string $visitationId): ?array
    {
        return match ((int) $type) {
            self::TYPE_SLIMMING => ['label' => 'Slimming', 'icon' => 'fas fa-weight', 'url' => route('erm.slimming.create', $visitationId)],
            self::TYPE_VAKSIN => ['label' => 'Vaksin', 'icon' => 'fas fa-syringe', 'url' => route('erm.vaksin.create', $visitationId)],
            self::TYPE_MCU => ['label' => 'MCU', 'icon' => 'fas fa-heartbeat', 'url' => route('erm.mcu.create', $visitationId)],
            default => null,
        };
    }

        public function riwayatTindakanObats()
    {
        return $this->belongsToMany(
            Obat::class,
            'erm_riwayat_tindakan_obat',
            'riwayat_tindakan_id',
            'obat_id'
        )->withPivot('kode_tindakan_id', 'qty', 'dosis', 'satuan_dosis')->withTimestamps();
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function metodeBayar()
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }
    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function spesialisasi()
    {
        return $this->belongsTo(Spesialisasi::class, 'spesialisasi_id');
    }

    /**
     * Spesialisasi of this visit: the one chosen at pendaftaran, or for old visits
     * the dokter's spesialisasi in this visit's klinik.
     */
    public function resolvedSpesialisasi(): ?Spesialisasi
    {
        if ($this->spesialisasi_id) {
            return $this->spesialisasi;
        }

        return $this->dokter?->spesialisasiForKlinik($this->klinik_id);
    }

    /**
     * Spesialisasi name for list tables (no extra queries with `spesialisasi` and `dokter.spesialisasi` loaded):
     * the one chosen at pendaftaran, else the dokter's default. Same as spesialisasiIdSql().
     */
    public function listSpesialisasiNama(): ?string
    {
        return ($this->spesialisasi_id ? $this->spesialisasi?->nama : null)
            ?? $this->dokter?->spesialisasi?->nama;
    }

    public function resolvedSpesialisasiId(): ?int
    {
        if ($this->spesialisasi_id) {
            return (int) $this->spesialisasi_id;
        }

        return $this->dokter?->spesialisasiIdForKlinik($this->klinik_id);
    }
    public function asesmenPerawat()
    {
        return $this->hasOne(AsesmenPerawat::class);
    }

    public function asesmenDalam()
    {
        return $this->hasOne(AsesmenDalam::class);
    }
    public function asesmenTht()
    {
        return $this->hasOne('App\\Models\\ERM\\AsesmenTht');
    }
    public function asesmenEstetika()
    {
        return $this->hasOne(AsesmenEstetika::class);
    }
    public function asesmenSaraf()
    {
        return $this->hasOne(AsesmenSaraf::class);
    }
    public function asesmenAnak()
    {
        return $this->hasOne(AsesmenAnak::class);
    }
    public function asesmenGigi()
    {
        return $this->hasOne(AsesmenGigi::class);
    }

    public function asesmenPenunjang()
    {
        return $this->hasOne(AsesmenPenunjang::class);
    }

    public function asesmenUmum()
    {
        return $this->hasOne(AsesmenUmum::class);
    }
    public function resepDokter()
    {
        return $this->hasMany(ResepDokter::class);
    }
    public function resepFarmasi()
    {
        return $this->hasMany(ResepFarmasi::class);
    }

    public function klinik()
    {
        return $this->belongsTo(Klinik::class, 'klinik_id');
    }
    public function cppt()
    {
        return $this->hasOne(Cppt::class, 'visitation_id');
    }

    public function labPermintaan()
    {
        return $this->hasMany(LabPermintaan::class, 'visitation_id');
    }

    public function riwayatTindakan()
    {
        return $this->hasMany(RiwayatTindakan::class, 'visitation_id');
    }

    public function invoice()
    {
        return $this->hasOne(\App\Models\Finance\Invoice::class, 'visitation_id');
    }

    public function billings()
    {
        return $this->hasMany(\App\Models\Finance\Billing::class, 'visitation_id');
    }
    
    public function suratDiagnosa()
    {
        return $this->hasOne(SuratDiagnosa::class, 'visitation_id');
    }

    public function screeningBatuk()
    {
        return $this->hasOne(ScreeningBatuk::class, 'visitation_id');
    }

    public function screeningVaksin()
    {
        return $this->hasOne(ScreeningVaksin::class, 'visitation_id');
    }

    public function suratIstirahats()
    {
        return $this->hasMany(SuratIstirahat::class, 'pasien_id', 'pasien_id');
    }

    public function suratMondoks()
    {
        return $this->hasMany(SuratMondok::class, 'pasien_id', 'pasien_id');
    }

    public function slimmingRecords()
    {
        return $this->hasMany(Slimming::class, 'visitation_id');
    }
}
