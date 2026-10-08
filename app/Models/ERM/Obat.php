<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ERM\Supplier;
use App\Models\Satusehat\ObatKfa;

class Obat extends Model
{
    use HasFactory;

    /**
     * Mendapatkan total stok obat di gudang tertentu
     * @param int $gudangId
     * @return float
     */
    public function getStokByGudang($gudangId)
    {
        return $this->stokGudang()->where('gudang_id', $gudangId)->sum('stok');
    }
    

    protected $table = 'erm_obat';

    /**
     * Single source of truth for kategori values (form, filter, import, stok opname).
     */
    public const KATEGORI_LIST = ['Obat', 'Produk', 'Racikan', 'Bhp', 'Bhp Alat', 'Lainnya'];

    /**
     * HPP is WITHOUT PPN, HNA is WITH PPN: both are set from the faktur price on approval
     * (hpp = harga, hna = harga * (1 + PPN_PERCENT / 100)).
     */
    public const PPN_PERCENT = 11;

    public static function hnaFromHpp(float $hpp): float
    {
        return round($hpp * (1 + self::PPN_PERCENT / 100), 2);
    }

    /**
     * kode_obat = PREFIX-00001, numbered per kategori. Generated automatically, never typed in.
     * The code is fixed once given, even if the kategori changes later.
     */
    public const KODE_PREFIX = [
        'Obat' => 'OBT', 'Produk' => 'PRD', 'Racikan' => 'RCK',
        'Bhp' => 'BHP', 'Bhp Alat' => 'BHA', 'Lainnya' => 'LNN',
    ];

    /**
     * Next free code for a prefix. Call inside a transaction: the range lock on the unique
     * kode_obat index makes concurrent creates wait instead of picking the same number.
     */
    public static function nextKode(string $prefix): string
    {
        $max = DB::table('erm_obat')
            ->where('kode_obat', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING(kode_obat, ?) AS UNSIGNED)) as max_no', [strlen($prefix) + 2])
            ->value('max_no');

        return sprintf('%s-%05d', $prefix, ((int) $max) + 1);
    }

    /**
     * Names of the given obat that have no satuan stok yet. Purchasing (Master Pembelian, permintaan,
     * faktur) counts qty, isi per box and harga per satuan in this unit, so it must be set first.
     */
    public static function namesWithoutSatuanStok(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        return self::withInactive()->whereIn('id', $ids)
            ->where(fn ($q) => $q->whereNull('satuan_stok')->orWhere('satuan_stok', ''))
            ->orderBy('nama')
            ->pluck('nama')
            ->all();
    }

    /** 422 message for purchasing forms when some obat have no satuan stok, null when all are set. */
    public static function satuanStokRequiredMessage(array $ids): ?string
    {
        $names = self::namesWithoutSatuanStok($ids);
        if (empty($names)) {
            return null;
        }
        $list = implode(', ', array_slice($names, 0, 5)) . (count($names) > 5 ? ' dan ' . (count($names) - 5) . ' lainnya' : '');

        return "Satuan stok belum diisi untuk: {$list}. Isi dulu di Master Obat, karena qty, isi per box dan harga per satuan dihitung dalam satuan ini.";
    }

    /**
     * Satuan stok/jual: the unit stock, HPP and harga jual are counted in (1 stok = 1 of these).
     */
    public const SATUAN_STOK_LIST = [
        'Tablet', 'Kapsul', 'Kaplet', 'Botol', 'Tube', 'Pot', 'Ampul', 'Vial', 'Sachet', 'Strip',
        'Pcs', 'Pack', 'Box', 'Softbag', 'Pen', 'Pump', 'g', 'IU',
    ];

    /**
     * Unit for `dosis` (kekuatan per 1 satuan stok). Measurement units, or a stok unit when the
     * dose is counted per piece (e.g. "1 Tablet") — racikan math divides by `dosis`.
     */
    public const SATUAN_DOSIS_LIST = ['mg', 'mcg', 'g', 'mL', 'IU', '%'];

    /**
     * Legacy spellings => canonical value, used by the satuan clean-up migration and the CSV import.
     */
    public const SATUAN_ALIASES = [
        'mg' => 'mg', 'mcg' => 'mcg', 'g' => 'g', 'gr' => 'g', 'gram' => 'g', 'ml' => 'mL', 'iu' => 'IU', '%' => '%',
        'tablet' => 'Tablet', 'tab' => 'Tablet', 'kapsul' => 'Kapsul', 'kaplet' => 'Kaplet', 'botol' => 'Botol',
        'plaboth' => 'Botol', 'tube' => 'Tube', 'pot' => 'Pot', 'ampul' => 'Ampul', 'vial' => 'Vial',
        'sachet' => 'Sachet', 'strip' => 'Strip', 'pcs' => 'Pcs', 'pack' => 'Pack', 'box' => 'Box',
        'softbag' => 'Softbag', 'pen' => 'Pen', 'pump' => 'Pump',
    ];

    public static function satuanDosisOptions(): array
    {
        return array_values(array_unique(array_merge(self::SATUAN_DOSIS_LIST, self::SATUAN_STOK_LIST)));
    }

    public static function normalizeSatuan(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return self::SATUAN_ALIASES[strtolower($value)] ?? $value;
    }

    /**
     * Unit to show next to stock quantities; falls back to the old satuan until satuan_stok is filled.
     */
    public function getSatuanStokLabelAttribute(): ?string
    {
        return $this->satuan_stok ?: $this->satuan;
    }

    /**
     * Tables whose rows mean this obat has been used. Deleting an obat cascades into
     * several of these (resep, stok gudang, bundles), so delete is refused while any exist.
     */
    public const USAGE_TABLES = [
        'erm_resepdokter', 'erm_resepfarmasi', 'erm_obat_stok_gudang', 'erm_kartu_stok',
        'erm_fakturbeli_items', 'erm_fakturretur_items', 'erm_master_faktur',
        'erm_mutasi_gudang', 'erm_mutasi_gudang_items', 'erm_mutasi_stok_items',
        'erm_obat_hibah_items', 'erm_stok_opname_items', 'erm_permintaan_items',
        'erm_riwayat_tindakan_obat', 'erm_obat_expired_tindak_lanjut',
        'erm_tindakan_obat', 'erm_kode_tindakan_obat', 'erm_lab_test_obat',
        'erm_paket_racikan_detail', 'marketing_penawaran_items',
    ];

    public function isUsed(): bool
    {
        foreach (self::USAGE_TABLES as $table) {
            if (DB::table($table)->where('obat_id', $this->id)->exists()) {
                return true;
            }
        }

        return DB::table('finance_invoice_items')
            ->where('billable_type', self::class)
            ->where('billable_id', $this->id)
            ->exists();
    }

    // Relasi ke stok per gudang
    public function stokGudang()
    {
        return $this->hasMany(ObatStokGudang::class, 'obat_id');
    }

    /**
     * Principal (manufacturer / brand owner), entered in Master Obat. Master Pembelian, permintaan and
     * faktur beli take it from here; faktur and permintaan items keep their own copy as history.
     */
    public function principal()
    {
        return $this->belongsTo(Principal::class, 'principal_id');
    }

    public function masterFakturs()
    {
        return $this->hasMany(MasterFaktur::class, 'obat_id');
    }

    // Mendapatkan total stok dari semua gudang
    public function getTotalStokAttribute()
    {
        return $this->stokGudang()->sum('stok');
    }

    protected $fillable = [
        'nama',
        'kode_obat',
        'kode_obat_lama',
        'satuan',
        'satuan_stok',
        'dosis',
        'harga_net',
        'hna',
        'harga_fornas',
        'harga_nonfornas',
        'kategori',
        'metode_bayar_id',
        'status_aktif',
        'hpp',
        'is_generik',
        'is_favorite',
        'principal_id',
    ];
    
    protected $casts = [
        'is_generik' => 'boolean',
        'is_favorite' => 'boolean',
    ];
    /**
     * The "booted" method of the model.
     * This ensures that only active medications are shown by default
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('status_aktif', 1);
        });

        // Give a kode as soon as the obat has a kategori (on create, or when kategori is first set)
        static::saving(function (Obat $obat) {
            // HNA is never entered: whenever HPP or HNA is written, HNA becomes HPP + PPN
            if (($obat->isDirty('hpp') || $obat->isDirty('hna')) && (float) $obat->hpp > 0) {
                $obat->hna = self::hnaFromHpp((float) $obat->hpp);
            }

            $prefix = self::KODE_PREFIX[$obat->kategori] ?? null;
            if ($prefix && trim((string) $obat->kode_obat) === '') {
                $obat->kode_obat = self::nextKode($prefix);
            }
        });
    }

    /**
     * Scope to include inactive medications when needed
     */
    public function scopeWithInactive($query)
    {
        return $query->withoutGlobalScope('active');
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function zatAktifs()
    {
        return $this->belongsToMany(ZatAktif::class, 'erm_kandungan_obat', 'obat_id', 'zataktif_id');
    }

    public function metodeBayar()
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }

    /**
     * KFA mapping (one-to-one)
     */
    public function kfa()
    {
        return $this->hasOne(ObatKfa::class, 'obat_id');
    }

        /**
         * The tindakan that this obat is bundled with.
         */
        public function tindakans()
        {
            return $this->belongsToMany(Tindakan::class, 'erm_tindakan_obat', 'obat_id', 'tindakan_id');
        }

    /**
     * @deprecated HPP calculation now handled directly in StokService
     * This method assumes harga_beli is stored per batch, but it's not in current design
     * HPP is calculated as weighted average in StokService when adding stock with price
     */
    public function recalculateHPP()
    {
        Log::warning('recalculateHPP() is deprecated. HPP calculation now handled in StokService.');
        
        // Legacy method - no longer functional with current database design
        // HPP calculation is now done in StokService->tambahStok() when hargaBeli is provided
        return false;
    }
}
