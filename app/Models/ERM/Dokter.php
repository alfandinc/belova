<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dokter extends Model
{
    protected $table = 'erm_dokters';

    protected $fillable = [
        'user_id',
        'spesialisasi_id',
        'klinik_id',
        'sip',
        'ttd',
        'due_date_sip',
        'photo',
        'nik',
        'alamat',
        'no_hp',
        'status',
        'str',
        'due_date_str',
    ];

    /**
     * Spesialisasi name => badge class, shared by the Rawat Jalan and Billing tables so a
     * spesialisasi has the same color everywhere. Same cached list/order as the Rawat Jalan filter.
     */
    public static function spesialisasiColorMap(): array
    {
        $palette = ['badge-primary', 'badge-light text-dark', 'badge-success', 'badge-danger', 'badge-warning', 'badge-info', 'badge-dark'];

        $dokters = \Illuminate\Support\Facades\Cache::remember('erm_dokters_list', 300, function () {
            return self::select('id', 'user_id', 'spesialisasi_id')
                ->with(['user:id,name', 'spesialisasi:id,nama'])
                ->get();
        });

        $map = [];
        foreach ($dokters as $dokter) {
            $name = optional($dokter->spesialisasi)->nama;
            if ($name && !isset($map[$name])) {
                $map[$name] = $palette[count($map) % count($palette)];
            }
        }

        return $map;
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function spesialisasi()
    {
        return $this->belongsTo(Spesialisasi::class);
    }
    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }

    public function kliniks(): BelongsToMany
    {
        return $this->belongsToMany(Klinik::class, 'erm_dokter_kliniks', 'dokter_id', 'klinik_id')
            ->withPivot('spesialisasi_id')
            ->withTimestamps();
    }

    /**
     * Spesialisasi the dokter practices as in the given klinik,
     * falling back to the dokter's default spesialisasi.
     */
    public function spesialisasiIdForKlinik($klinikId): ?int
    {
        if ($klinikId) {
            $pivotSpesialisasiId = $this->kliniks()
                ->where('erm_klinik.id', $klinikId)
                ->value('erm_dokter_kliniks.spesialisasi_id');

            if ($pivotSpesialisasiId) {
                return (int) $pivotSpesialisasiId;
            }
        }

        return $this->spesialisasi_id ? (int) $this->spesialisasi_id : null;
    }

    public function spesialisasiForKlinik($klinikId): ?Spesialisasi
    {
        $spesialisasiId = $this->spesialisasiIdForKlinik($klinikId);

        if ($spesialisasiId === null) {
            return null;
        }

        if ((int) $this->spesialisasi_id === $spesialisasiId && $this->relationLoaded('spesialisasi')) {
            return $this->spesialisasi;
        }

        return Spesialisasi::find($spesialisasiId);
    }

    public function mapping()
    {
        return $this->hasOne(\App\Models\Satusehat\DokterMapping::class, 'dokter_id');
    }

    public function slimmingRecords()
    {
        return $this->hasMany(Slimming::class, 'dokter_id');
    }
}
