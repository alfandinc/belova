<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Area\Village;
use App\Models\HRD\Employee;
use App\Models\ERM\KelasPasien;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Pasien extends Model
{
    use HasFactory;

    public const IDENTITY_DOCUMENT_KTP = 'ktp';
    public const IDENTITY_DOCUMENT_SIM = 'sim';
    public const IDENTITY_DOCUMENT_PASPOR = 'paspor';
    public const IDENTITY_DOCUMENT_KIA = 'kia';
    public const REFERRAL_TYPE_WALK_IN = 'walk_in';
    public const REFERRAL_TYPE_PASIEN = 'pasien';
    public const REFERRAL_TYPE_SOCIAL_MEDIA = 'social_media';
    public const REFERRAL_TYPE_WEBSITE = 'website';
    public const REFERRAL_TYPE_EMPLOYEE = 'employee';
    public const REFERRAL_TYPE_DOKTER = 'dokter';
    public const REFERRAL_TYPE_EVENT = 'event';
    public const REFERRAL_TYPE_MARKETPLACE = 'marketplace';
    public const REFERRAL_TYPE_PARTNERSHIP = 'partnership';
    public const REFERRAL_TYPE_GOOGLE_MAPS = 'google_maps';
    public const MARKETPLACE_REFERRAL_DETAILS = ['shopee', 'tiktokshop', 'tokopedia', 'lazada'];
    public const SOCIAL_MEDIA_REFERRAL_DETAILS = ['instagram', 'tiktok', 'facebook', 'threads', 'twitter', 'whatsapp'];

    protected $keyType = 'string';
    public $incrementing = false;
    protected $appends = [
        'nik',
        'identity_label',
        'identity_display',
    ];

    protected $table = 'erm_pasiens';
    protected $fillable = [
        'id',
        'identity_document',
        'identity_number',
        'referral_type',
        'referral_detail',
        'referralable_type',
        'referralable_id',
        'nama',
        'tanggal_lahir',
        'gender',
        'agama',
        'marital_status',
        'pendidikan',
        'pekerjaan',
        'gol_darah',
        'notes',
        'alamat',
        'village_id',
        'no_hp',
        'no_hp2',
        'email',
        'instagram',
        'status_pasien',
        'status_akses',
        'status_review',
        'user_id',
        'employee_id',
    ];

    public function getNikAttribute()
    {
        return $this->attributes['identity_number'] ?? null;
    }

    public function setNikAttribute($value)
    {
        $this->attributes['identity_number'] = $value;
    }

    public function getIdentityDocumentAttribute($value)
    {
        return $value ?: self::IDENTITY_DOCUMENT_KTP;
    }

    public function getIdentityLabelAttribute()
    {
        return match ($this->identity_document) {
            self::IDENTITY_DOCUMENT_KTP => 'NIK',
            self::IDENTITY_DOCUMENT_SIM => 'Nomor SIM',
            self::IDENTITY_DOCUMENT_PASPOR => 'Nomor Paspor',
            self::IDENTITY_DOCUMENT_KIA => 'Nomor KIA',
            default => 'Identitas',
        };
    }

    public function getIdentityDisplayAttribute()
    {
        $identityNumber = $this->attributes['identity_number'] ?? null;

        if (empty($identityNumber)) {
            return '-';
        }

        return $this->identity_label . ': ' . $identityNumber;
    }

    public static function marketplaceReferralOptions(): array
    {
        return self::MARKETPLACE_REFERRAL_DETAILS;
    }

    public static function socialMediaReferralOptions(): array
    {
        return self::SOCIAL_MEDIA_REFERRAL_DETAILS;
    }

    public static function buildReferralAttributes(
        ?string $referralType,
        ?string $referralPasienId = null,
        ?string $referralDetail = null,
        ?string $referralableType = null,
        $referralableId = null
    ): array {
        $referralType = $referralType !== null && trim($referralType) !== '' ? trim($referralType) : null;
        $referralPasienId = $referralPasienId !== null && trim($referralPasienId) !== '' ? trim($referralPasienId) : null;
        $referralDetail = $referralDetail !== null && trim($referralDetail) !== '' ? trim($referralDetail) : null;
        $referralableType = $referralableType !== null && trim($referralableType) !== '' ? trim($referralableType) : null;
        $referralableId = $referralableId !== null && trim((string) $referralableId) !== '' ? trim((string) $referralableId) : null;

        if ($referralType === self::REFERRAL_TYPE_PASIEN) {
            return [
                'referral_detail' => $referralPasienId ?? $referralDetail,
                'referralable_type' => $referralPasienId ? (new self())->getMorphClass() : null,
                'referralable_id' => $referralPasienId,
            ];
        }

        if ($referralType === self::REFERRAL_TYPE_EMPLOYEE) {
            return [
                'referral_detail' => $referralableId ?? $referralDetail,
                'referralable_type' => $referralableId ? (new Employee())->getMorphClass() : null,
                'referralable_id' => $referralableId,
            ];
        }

        if ($referralType === self::REFERRAL_TYPE_DOKTER) {
            return [
                'referral_detail' => $referralableId ?? $referralDetail,
                'referralable_type' => $referralableId ? (new Dokter())->getMorphClass() : null,
                'referralable_id' => $referralableId,
            ];
        }

        if ($referralType === self::REFERRAL_TYPE_EVENT) {
            return [
                'referral_detail' => $referralDetail,
                'referralable_type' => $referralableId ? (new MarketingEvent())->getMorphClass() : null,
                'referralable_id' => $referralableId,
            ];
        }

        return [
            'referral_detail' => $referralDetail,
            'referralable_type' => $referralableType,
            'referralable_id' => $referralableId,
        ];
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function referralable(): MorphTo
    {
        return $this->morphTo();
    }

    public function suratIstirahats()
    {
        return $this->hasMany(SuratIstirahat::class);
    }

    public function suratMondoks()
    {
        return $this->hasMany(SuratMondok::class);
    }
    
    public function visitations()
    {
        return $this->hasMany(Visitation::class, 'pasien_id');
    }

    public function slimmingRecords()
    {
        return $this->hasMany(Slimming::class, 'pasien_id');
    }

    /**
     * Pasien merchandise receipts (pivot records)
     */
    public function pasienMerchandises()
    {
        return $this->hasMany(PasienMerchandise::class, 'pasien_id');
    }

    /**
     * Convenience relation to get merchandises through pivot
     */
    public function merchandises()
    {
        return $this->belongsToMany(Merchandise::class, 'erm_pasien_merchandises', 'pasien_id', 'merchandise_id')
                    ->withPivot(['id', 'quantity', 'notes', 'given_by_user_id', 'given_at'])
                    ->withTimestamps();
    }
}
