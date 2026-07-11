<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Area\Village;
use App\Models\ERM\KelasPasien;

class Pasien extends Model
{
    use HasFactory;

    public const IDENTITY_DOCUMENT_KTP = 'ktp';
    public const IDENTITY_DOCUMENT_SIM = 'sim';
    public const IDENTITY_DOCUMENT_PASPOR = 'paspor';
    public const IDENTITY_DOCUMENT_KIA = 'kia';
    public const REFERRAL_TYPE_SOCIAL_MEDIA = 'social_media';
    public const REFERRAL_TYPE_WEBSITE = 'website';
    public const REFERRAL_TYPE_OTHER_PASIEN = 'other_pasien';
    public const REFERRAL_TYPE_LAINNYA = 'lainnya';

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
        'referral_pasien_id',
        'referral_detail',
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

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function referralPasien()
    {
        return $this->belongsTo(self::class, 'referral_pasien_id');
    }

    public function referredPatients()
    {
        return $this->hasMany(self::class, 'referral_pasien_id');
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
