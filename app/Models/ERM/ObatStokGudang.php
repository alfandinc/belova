<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObatStokGudang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erm_obat_stok_gudang';
    
    protected $fillable = [
        'obat_id',
        'gudang_id',
        'stok',
        'batch',
        'expiration_date',
        'rak',
        'lokasi'
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'stok' => 'decimal:2',
    ];

    // Relasi ke Obat (termasuk yang tidak aktif)
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id')->withInactive();
    }

    // Relasi ke Obat (hanya yang aktif)
    public function obatAktif()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    // Relasi ke Gudang
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    // Scope untuk mencari stok berdasarkan obat dan gudang
    public function scopeByObatAndGudang($query, $obatId, $gudangId)
    {
        return $query->where('obat_id', $obatId)
                    ->where('gudang_id', $gudangId);
    }

    // Scope untuk mencari stok berdasarkan batch
    public function scopeByBatch($query, $batch)
    {
        return $query->where('batch', $batch);
    }

    // Scope untuk mencari stok yang akan kadaluarsa dalam x hari
    public function scopeWillExpire($query, $days = 30)
    {
        return $query->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '<=', now()->addDays($days))
                    ->whereDate('expiration_date', '>=', now());
    }

    // Scope untuk mencari stok yang sudah kadaluarsa
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '<', now());
    }

    // Stok migrasi dari sistem lama: batch "MIGRATE-*" dengan ED sementara (bukan ED asli).
    // ED-nya belum diketahui, jadi batch ini tidak pernah dianggap expired sampai dilengkapi di layar Stok MIGRATE.
    public const MIGRASI_BATCH_PREFIX = 'MIGRATE';

    public function isMigrasi(): bool
    {
        return $this->batch !== null && str_starts_with($this->batch, self::MIGRASI_BATCH_PREFIX);
    }

    // Batch yang boleh keluar ke pasien atau dimutasi: belum lewat ED.
    // Batch tanpa ED dan batch migrasi (ED belum diketahui) dianggap layak.
    // Dipakai bersama oleh billing, mutasi gudang, mutasi stok, dan tampilan ketersediaan stok.
    public function scopeLayakPakai($query)
    {
        $table = $this->getTable();

        return $query->where(function ($q) use ($table) {
            $q->whereNull($table . '.expiration_date')
                ->orWhereDate($table . '.expiration_date', '>=', now()->toDateString())
                ->orWhere($table . '.batch', 'like', self::MIGRASI_BATCH_PREFIX . '%');
        });
    }

    // Kebalikan layakPakai sebagai ekspresi SQL (1 = batch sudah lewat ED); untuk SUM / ORDER BY
    public static function sqlSudahEd(): string
    {
        $table = (new static)->getTable();

        return "CASE WHEN {$table}.expiration_date IS NOT NULL"
            . " AND {$table}.expiration_date < '" . now()->toDateString() . "'"
            . " AND ({$table}.batch IS NULL OR {$table}.batch NOT LIKE '" . self::MIGRASI_BATCH_PREFIX . "%')"
            . " THEN 1 ELSE 0 END";
    }

    // Hanya batch dengan ED asli (bukan migrasi); dipakai untuk daftar akan expired / karantina
    public function scopeEdTerverifikasi($query)
    {
        $table = $this->getTable();

        return $query->whereNotNull($table . '.expiration_date')
            ->where(function ($q) use ($table) {
                $q->whereNull($table . '.batch')
                    ->orWhere($table . '.batch', 'not like', self::MIGRASI_BATCH_PREFIX . '%');
            });
    }

    // Urutan pengambilan stok: batch migrasi dulu (stok fisik tertua), lalu FEFO (ED terdekat dulu),
    // batch tanpa ED paling akhir
    public function scopeUrutFefo($query)
    {
        $table = $this->getTable();

        return $query->orderByRaw(
                "CASE WHEN {$table}.batch LIKE '" . self::MIGRASI_BATCH_PREFIX . "%' THEN 0"
                . " WHEN {$table}.expiration_date IS NULL THEN 2 ELSE 1 END"
            )
            ->orderBy($table . '.expiration_date')
            ->orderBy($table . '.batch');
    }

    public function isExpired(): bool
    {
        return !$this->isMigrasi()
            && $this->expiration_date !== null
            && $this->expiration_date->lt(now()->startOfDay());
    }

    // Method untuk menambah stok
    public function addStock($amount)
    {
        $this->stok = ((float)$this->stok) + (float)$amount;
        $this->save();
    }

    // Method untuk mengurangi stok
    public function reduceStock($amount)
    {
        if ((float)$this->stok >= (float)$amount) {
            $this->stok = ((float)$this->stok) - (float)$amount;
            $this->save();
            return true;
        }
        return false;
    }
}
