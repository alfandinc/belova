<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MutasiGudang extends Model
{
    use HasFactory;
    protected $table = 'erm_mutasi_gudang';
    protected $fillable = [
        'nomor_mutasi',
        'gudang_asal_id',
        'gudang_tujuan_id',
        'obat_id',
        'jumlah',
        'keterangan',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejected_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Relasi ke Obat
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    // Relasi ke Gudang Asal
    public function gudangAsal()
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }

    // Relasi ke Gudang Tujuan
    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    // Relasi ke User yang request
    public function requestedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    // Relasi ke User yang approve
    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    // Jika sekarang mendukung banyak items per mutasi
    public function items()
    {
        return $this->hasMany(MutasiGudangItem::class, 'mutasi_id');
    }
// ...existing code...
}