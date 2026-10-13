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
        'is_active',
        'nonaktif_tanggal',
        'nonaktif_keterangan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Dokter for new data (pickers). Not a global scope: kunjungan, resep, slip gaji, ... of a
     * dokter nonaktif must still load their dokter.
     */
    public function scopeActive($query)
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }

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

        // Default spesialisasi first (same order as the filter), then the ones only set per klinik
        $names = $dokters->map(fn ($dokter) => optional($dokter->spesialisasi)->nama);
        if (self::tracksKlinikSpesialisasi()) {
            $names = $names->merge(Spesialisasi::whereIn('id', \Illuminate\Support\Facades\DB::table('erm_dokter_klinik_spesialisasis')->select('spesialisasi_id'))
                ->orderBy('id')
                ->pluck('nama'));
        }

        $map = [];
        foreach ($names as $name) {
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
            ->withTimestamps();
    }

    /** erm_dokter_klinik_spesialisasis exists once its migration ran (prod schema may lag behind the code). */
    public static function tracksKlinikSpesialisasi(): bool
    {
        static $exists = null;

        return $exists ??= \Illuminate\Support\Facades\Schema::hasTable('erm_dokter_klinik_spesialisasis');
    }

    /**
     * Spesialisasi the dokter practises as, per klinik (pivot klinik_id). A klinik can have several.
     */
    public function klinikSpesialisasis(): BelongsToMany
    {
        return $this->belongsToMany(Spesialisasi::class, 'erm_dokter_klinik_spesialisasis', 'dokter_id', 'spesialisasi_id')
            ->withPivot('klinik_id')
            ->withTimestamps();
    }

    /**
     * Spesialisasi ids the dokter can be registered as in the given klinik:
     * the ones set for that klinik, or the dokter's default spesialisasi when none are set.
     *
     * @return int[]
     */
    public function spesialisasiIdsForKlinik($klinikId): array
    {
        $ids = [];
        if ($klinikId && self::tracksKlinikSpesialisasi()) {
            $ids = $this->relationLoaded('klinikSpesialisasis')
                ? $this->klinikSpesialisasis->filter(fn ($s) => (int) $s->pivot->klinik_id === (int) $klinikId)->pluck('id')->sort()->all()
                : $this->klinikSpesialisasis()->wherePivot('klinik_id', $klinikId)->orderBy('erm_spesialisasis.id')->pluck('erm_spesialisasis.id')->all();
        }

        if (empty($ids) && $this->spesialisasi_id) {
            $ids = [$this->spesialisasi_id];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Default spesialisasi in the given klinik (the first one when the dokter holds several there).
     */
    public function spesialisasiIdForKlinik($klinikId): ?int
    {
        return $this->spesialisasiIdsForKlinik($klinikId)[0] ?? null;
    }

    /**
     * Replace the per-klinik spesialisasi list: [klinik_id => [spesialisasi_id, ...]].
     * A klinik without ids keeps no rows (= the dokter's default spesialisasi).
     */
    public function syncKlinikSpesialisasis(array $map): void
    {
        if (!self::tracksKlinikSpesialisasi()) {
            return;
        }

        $this->klinikSpesialisasis()->detach();

        foreach ($map as $klinikId => $spesialisasiIds) {
            $ids = array_unique(array_filter(array_map('intval', (array) $spesialisasiIds)));
            foreach ($ids as $spesialisasiId) {
                $this->klinikSpesialisasis()->attach($spesialisasiId, ['klinik_id' => (int) $klinikId]);
            }
        }
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
