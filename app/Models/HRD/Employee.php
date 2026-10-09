<?php

namespace App\Models\HRD;

use App\Models\ERM\Pasien;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    public function attendanceRekap()
    {
        return $this->hasMany(\App\Models\AttendanceRekap::class, 'employee_id');
    }

    public function pengajuanTidakMasuk()
    {
        return $this->hasMany(PengajuanTidakMasuk::class, 'employee_id');
    }

    protected $table = 'hrd_employee';

    protected $fillable = [
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'nik',
        'no_induk',
        'no_darurat', // Emergency contact number
        'alamat',
        'gol_darah', // Blood type
        'village_id',
        // position_id and division_id moved to pivot / derived from Position
        'pendidikan',
        'no_hp',
        'tanggal_masuk',
        'status',
        'kontrak_berakhir',
        'masa_pensiun',
        'doc_cv',
        'doc_ktp',
        'doc_kontrak',
        'doc_pendukung',
        'user_id',
        'photo',
        'email',
        'instagram',
        'perusahaan',
        'finger_id', // New field for fingerprint ID
        'gol_gaji_pokok_id',
        'gol_tunjangan_jabatan_id'
            ,'kategori_pegawai' // Added new field for employee category
        ,'nonaktif_tanggal', 'nonaktif_alasan', 'nonaktif_keterangan',
        'darurat_nama', 'darurat_hubungan',
        'npwp', 'no_bpjs_kesehatan', 'no_bpjs_ketenagakerjaan', 'bank_nama', 'bank_no_rekening', 'bank_atas_nama',
        'status_pernikahan', 'jumlah_tanggungan', 'perusahaan_list',
    ];

    /**
     * All companies of the employee, main company (`perusahaan`) first. Falls back to `perusahaan` for rows
     * without a list.
     */
    public function daftarPerusahaan(): array
    {
        $list = array_values(array_filter((array) ($this->perusahaan_list ?? [])));
        if ($this->perusahaan) {
            $list = array_values(array_unique(array_merge([$this->perusahaan], $list)));
        }

        return $list;
    }

    /** Full name => short label for the list. */
    public const PERUSAHAAN_SINGKAT = [
        'Klinik Utama Premiere Belova' => 'KUPB',
        'Klinik Pratama Belova' => 'KPB',
        'Belova Center Living' => 'BCL',
    ];
    public const PERUSAHAAN = ['Klinik Utama Premiere Belova', 'Klinik Pratama Belova', 'Belova Center Living'];

    /**
     * Jenjang pendidikan terakhir. `pendidikan` stores "jenjang jurusan" as one text, e.g. "S1 Ilmu Komunikasi".
     */
    public const PENDIDIKAN = ['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', 'Profesi'];

    /** Older spellings of a jenjang at the start of `pendidikan`. */
    private const PENDIDIKAN_ALIAS = ['SMA' => 'SMA/SMK', 'SMK' => 'SMA/SMK', 'SLTA' => 'SMA/SMK', 'SMU' => 'SMA/SMK', 'SLTP' => 'SMP', 'DIII' => 'D3', 'D-3' => 'D3', 'DIV' => 'D4', 'D-4' => 'D4', 'S-1' => 'S1', 'S-2' => 'S2'];

    /** "S1 Ilmu Komunikasi" -> ['S1', 'Ilmu Komunikasi']; text without a known jenjang -> ['', text]. */
    public static function splitPendidikan(?string $pendidikan): array
    {
        $pendidikan = trim((string) $pendidikan);
        $candidates = array_combine(self::PENDIDIKAN, self::PENDIDIKAN) + self::PENDIDIKAN_ALIAS;
        uksort($candidates, fn($a, $b) => strlen($b) <=> strlen($a)); // longest first: "SMA/SMK" before "SMA"
        foreach ($candidates as $prefix => $jenjang) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\s|$|[.,\-])/i', $pendidikan)) {
                return [$jenjang, trim(substr($pendidikan, strlen($prefix)), " \t.,-")];
            }
        }

        return ['', $pendidikan];
    }

    public static function joinPendidikan(?string $jenjang, ?string $jurusan): ?string
    {
        $text = trim(trim((string) $jenjang) . ' ' . trim((string) $jurusan));

        return $text === '' ? null : $text;
    }

    public const STATUS_PERNIKAHAN = [
        'belum_menikah' => 'Belum menikah',
        'menikah' => 'Menikah',
        'cerai' => 'Cerai',
    ];

    /** PTKP code for PPh 21: TK (tidak kawin) / K (kawin) + tanggungan (max 3), e.g. K/2. */
    public function ptkp(): ?string
    {
        if (!$this->status_pernikahan) {
            return null;
        }

        return ($this->status_pernikahan === 'menikah' ? 'K' : 'TK') . '/' . min(3, (int) $this->jumlah_tanggungan);
    }

    /** Why an employee is tidak aktif (nonaktif_alasan). */
    public const NONAKTIF_ALASAN = [
        'resign' => 'Resign',
        'phk' => 'PHK / Diberhentikan',
        'kontrak_habis' => 'Kontrak habis (tidak diperpanjang)',
        'pensiun' => 'Pensiun',
        'meninggal' => 'Meninggal dunia',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Data other modules rely on (cuti & slip gaji use tanggal_masuk, divisi / atasan come from the position, ...).
     * The first group is required by the form; all of them are checked by the "data belum lengkap" marker.
     */
    public const DATA_WAJIB = [
        'tanggal_masuk' => 'Tanggal masuk',
        'jenis_kelamin' => 'Jenis kelamin',
        'perusahaan' => 'Perusahaan',
    ];
    public const DATA_PENTING = [
        'nik' => 'NIK',
        'tanggal_lahir' => 'Tanggal lahir',
        'no_hp' => 'No HP',
        'finger_id' => 'Finger ID (absensi)',
        'gol_gaji_pokok_id' => 'Gaji pokok',
    ];

    /** Labels of the missing DATA_WAJIB / DATA_PENTING fields and the position (needs `positions` loaded). */
    public function dataBelumLengkap(): array
    {
        $missing = [];
        foreach (self::DATA_WAJIB + self::DATA_PENTING as $field => $label) {
            if (blank($this->{$field})) {
                $missing[] = $label;
            }
        }
        if ($this->positions->isEmpty()) {
            $missing[] = 'Posisi';
        }

        return $missing;
    }

    /** Query version of dataBelumLengkap(): employees with at least one of those fields missing. */
    public function scopeBelumLengkap(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            foreach (array_keys(self::DATA_WAJIB + self::DATA_PENTING) as $field) {
                $q->orWhereNull($this->qualifyColumn($field))->orWhereRaw('TRIM(' . $this->qualifyColumn($field) . ") = ''");
            }
            $q->orWhereDoesntHave('positions');
        });
    }

    /** "3 Tahun 2 Bulan" since tanggal_masuk (until the nonaktif date for a tidak aktif employee). */
    public function masaKerja(): ?string
    {
        if (!$this->tanggal_masuk) {
            return null;
        }
        $until = $this->status === 'tidak aktif' && $this->nonaktif_tanggal ? $this->nonaktif_tanggal : today();
        if ($until->lt($this->tanggal_masuk)) {
            return null;
        }
        $diff = $this->tanggal_masuk->diff($until);
        $parts = array_filter([$diff->y ? $diff->y . ' Tahun' : null, $diff->m ? $diff->m . ' Bulan' : null]);

        return $parts ? implode(' ', $parts) : $diff->d . ' Hari';
    }
    /**
     * Get the master gaji pokok (salary group) for the employee.
     */
    public function golGajiPokok()
    {
        return $this->belongsTo(PrMasterGajipokok::class, 'gol_gaji_pokok_id');
    }

    /**
     * Get the master tunjangan jabatan (position allowance group) for the employee.
     */
    public function golTunjanganJabatan()
    {
        return $this->belongsTo(PrMasterTunjanganJabatan::class, 'gol_tunjangan_jabatan_id');
    }

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
        'kontrak_berakhir' => 'date',
        'masa_pensiun' => 'date',
        'nonaktif_tanggal' => 'date',
        'perusahaan_list' => 'array',
        'instagram' => 'array', // Cast instagram as array for JSON storage
    ];

    /**
     * Non-aktif employees are hidden everywhere by default; only the karyawan list (which has its own
     * status filter) and relations from history records (pengajuan, slip gaji, absensi, ...) include them.
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->whereRaw('LOWER(' . $builder->getModel()->qualifyColumn('status') . ') <> ?', ['tidak aktif']);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereRaw('LOWER(' . $query->getModel()->qualifyColumn('status') . ') <> ?', ['tidak aktif']);
    }

    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    /** Route-bound employees (e.g. absensi detail of a past month) resolve even when non-aktif. */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::withInactive()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    public function position()
    {
        // Relation: primary position (may return a collection when eager-loaded)
        return $this->positions()->wherePivot('is_primary', 1);
    }

    /**
     * Accessor for `$employee->position` to return a single Position model (primary)
     */
    public function getPositionAttribute()
    {
        $rel = $this->getRelationValue('position');
        if ($rel instanceof \Illuminate\Database\Eloquent\Collection) {
            return $rel->first();
        }
        return $rel;
    }

    /**
     * Accessor for `$employee->division` to keep compatibility.
     * Returns the division of the primary position if available.
     */
    public function getDivisionAttribute()
    {
        $pos = $this->position; // uses accessor above

        if (!$pos) {
            return null;
        }

        if ($pos->relationLoaded('divisions')) {
            return $pos->divisions->first();
        }

        return $pos->divisions()->first();
    }

    public function getPositionIdAttribute()
    {
        if (array_key_exists('position_id', $this->attributes)) {
            return $this->attributes['position_id'];
        }

        return $this->position?->id;
    }

    public function getDivisionIdAttribute()
    {
        if (array_key_exists('division_id', $this->attributes)) {
            return $this->attributes['division_id'];
        }

        return $this->division?->id;
    }

    public function getDivisionIdsAttribute(): array
    {
        $positions = $this->relationLoaded('positions')
            ? $this->getRelation('positions')
            : $this->positions()->with('divisions')->get();

        if (!$positions instanceof Collection) {
            return array_filter([$this->division_id]);
        }

        $divisionIds = $positions
            ->flatMap(function (Position $position) {
                $positionDivisionIds = $position->relationLoaded('divisions')
                    ? $position->divisions->pluck('id')
                    : $position->divisions()->pluck('hrd_division.id');

                if ($positionDivisionIds->isEmpty() && $position->division_id) {
                    return [$position->division_id];
                }

                return $positionDivisionIds->all();
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($divisionIds)) {
            return $divisionIds;
        }

        return array_filter([$this->division_id]);
    }

    /**
     * Many-to-many positions pivot relationship.
     */
    public function positions()
    {
        return $this->belongsToMany(Position::class, 'hrd_employee_position')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * Helper to get the primary position (if any).
     */
    public function primaryPosition()
    {
        return $this->positions()->wherePivot('is_primary', 1)->first();
    }

    public function directSubordinatePositionIds(): array
    {
        $positions = $this->relationLoaded('positions')
            ? $this->getRelation('positions')
            : $this->positions()->with('childPositions')->get();

        if (!$positions instanceof Collection) {
            return [];
        }

        return $positions
            ->flatMap(function (Position $position) {
                $childPositionIds = $position->relationLoaded('childPositions')
                    ? $position->childPositions->pluck('id')
                    : $position->childPositions()->pluck('hrd_position.id');

                return $childPositionIds->all();
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function directSubordinateEmployeeIds(): array
    {
        $childPositionIds = $this->directSubordinatePositionIds();

        if (empty($childPositionIds)) {
            return [];
        }

        return self::query()
            ->where('id', '!=', $this->id)
            ->whereHas('positions', function ($query) use ($childPositionIds) {
                $query->whereIn('hrd_position.id', $childPositionIds);
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    public function hasDirectSubordinates(): bool
    {
        return !empty($this->directSubordinateEmployeeIds());
    }

    public function village()
    {
        return $this->belongsTo(\App\Models\Area\Village::class, 'village_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Pengajuan dana created by this employee
     */
    public function pengajuanDanas()
    {
        return $this->hasMany(\App\Models\Finance\FinancePengajuanDana::class, 'employee_id');
    }

    public function pasiens()
    {
        return $this->hasMany(Pasien::class, 'employee_id');
    }
    
    public function contracts()
    {
        return $this->hasMany(EmployeeContract::class, 'employee_id');
    }
    
    public function activeContract()
    {
        return $this->hasOne(EmployeeContract::class, 'employee_id')
                    ->where('status', 'active')
                    ->orderBy('end_date', 'desc');
    }
    
    public function lastContract()
    {
        return $this->hasOne(EmployeeContract::class, 'employee_id')
                    ->latest('end_date');
    }

    /**
     * An employee whose status is no longer kontrak keeps no running contract: it ends today.
     * Tidak aktif = 'terminated' (Diputus), tetap / freelance = 'expired' (Selesai). Returns whether one was ended.
     */
    public function endActiveContractIfNotKontrak(): bool
    {
        if ($this->status === 'kontrak') {
            return false;
        }
        $contracts = $this->contracts()->where('status', 'active')->get();
        $label = [
            'tetap' => 'diangkat menjadi karyawan tetap',
            'freelance' => 'status diubah menjadi freelance',
            'tidak aktif' => 'tidak aktif' . ($this->nonaktif_alasan ? ' (' . (self::NONAKTIF_ALASAN[$this->nonaktif_alasan] ?? $this->nonaktif_alasan) . ')' : ''),
        ][$this->status] ?? 'status diubah';
        // Tidak aktif: the contract ends on the effective nonaktif date, otherwise today
        $endsOn = $this->status === 'tidak aktif' && $this->nonaktif_tanggal ? $this->nonaktif_tanggal->copy() : today();

        foreach ($contracts as $contract) {
            $contract->update([
                'status' => $this->status === 'tidak aktif' ? 'terminated' : 'expired',
                'end_date' => $contract->end_date->gt($endsOn) ? $endsOn : $contract->end_date,
                'notes' => trim(($contract->notes ? $contract->notes . "\n\n" : '') . 'Berakhir ' . $endsOn->format('d/m/Y') . ': ' . $label . '.'),
            ]);
        }

        return $contracts->isNotEmpty();
    }

    public function isManager()
    {
        // Make case-insensitive for safety
        return $this->user && $this->user->hasRole(['manager', 'Manager']);
    }
    
    public function isCEO()
    {
        // Check if user has CEO role
        return $this->user && $this->user->hasRole(['ceo', 'Ceo', 'CEO']);
    }

    public function jatahLibur()
    {
        return $this->hasOne(JatahLibur::class, 'employee_id');
    }

    public function pengajuanLibur()
    {
        return $this->hasMany(PengajuanLibur::class, 'employee_id');
    }

        /**
         * Relasi ke jadwal shift mingguan karyawan
         */
        public function schedules()
        {
            return $this->hasMany(EmployeeSchedule::class, 'employee_id');
        }

    /**
     * Ensure the employee has a jatah libur record
     * If not, it creates a new one with default values
     *
     * @param int $defaultCutiTahunan Default value for annual leave
     * @param int $defaultGantiLibur Default value for replacement leave
     * @return JatahLibur
     */
    public function ensureJatahLibur($defaultCutiTahunan = 0, $defaultGantiLibur = 0)
    {
        if (!$this->jatahLibur) {
            return JatahLibur::create([
                'employee_id' => $this->id,
                'jatah_cuti_tahunan' => $defaultCutiTahunan,
                'jatah_ganti_libur' => $defaultGantiLibur
            ]);
        }
        
        return $this->jatahLibur;
    }
}
