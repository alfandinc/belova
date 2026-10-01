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

    
    protected $table = 'erm_visitations';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
        'pasien_id',
        'metode_bayar_id',
        'dokter_id',
        'user_id',
        'klinik_id',
        'status_kunjungan',
        'status_dokumen',
        'jenis_kunjungan',
        'tanggal_visitation',
        'waktu_kunjungan', // add this line
        'no_antrian',
        // Transaction referral: why the patient came for THIS visit.
        // The patient's source referral lives on erm_pasiens.
        'referral_type',
        'referral_detail',
        'referralable_type',
        'referralable_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Visitation $visitation) {
            if (!empty($visitation->referral_type) || empty($visitation->pasien_id)) {
                return;
            }

            $visitation->forceFill(self::defaultReferralFor((string) $visitation->pasien_id));
        });
    }

    /**
     * Default referral for a new visit: copy the patient's last visit referral,
     * or the patient's source referral when this is their first visit.
     */
    public static function defaultReferralFor(string $pasienId): array
    {
        $previous = self::query()
            ->where('pasien_id', $pasienId)
            ->whereNotNull('referral_type')
            ->orderByDesc('tanggal_visitation')
            ->orderByDesc('waktu_kunjungan')
            ->orderByDesc('created_at')
            ->first(['referral_type', 'referral_detail', 'referralable_type', 'referralable_id']);

        $source = $previous ?? Pasien::find($pasienId, ['referral_type', 'referral_detail', 'referralable_type', 'referralable_id']);

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
            default => (string) $type,
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

    /**
     * The dokter's spesialisasi for this visit's klinik (a dokter can practise differently per klinik).
     */
    public function resolvedSpesialisasi(): ?Spesialisasi
    {
        return $this->dokter?->spesialisasiForKlinik($this->klinik_id);
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

    public function waMessages()
    {
        return $this->hasMany(\App\Models\WaMessage::class, 'visitation_id');
    }

    public function waScheduledMessages()
    {
        return $this->hasMany(\App\Models\WaScheduledMessage::class, 'visitation_id');
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
