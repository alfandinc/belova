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
        'min_stok',
        'max_stok',
        'batch',
        'expiration_date',
        'rak',
        'lokasi'
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'stok' => 'decimal:2',
        'min_stok' => 'decimal:2',
        'max_stok' => 'decimal:2'
    ];

    // Relasi ke Obat
    public function obat()
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

    // Scope untuk mencari stok dibawah minimum
    public function scopeBelowMinimum($query)
    {
        return $query->whereRaw('stok < min_stok');
    }

    // Scope untuk mencari stok diatas maksimum
    public function scopeAboveMaximum($query)
    {
        return $query->whereRaw('stok > max_stok');
    }

    // Method untuk menambah stok
    public function addStock($amount)
    {
        $this->increment('stok', $amount);
    }

    // Method untuk mengurangi stok
    public function reduceStock($amount)
    {
        if ($this->stok >= $amount) {
            $this->decrement('stok', $amount);
            return true;
        }
        return false;
    }
}
