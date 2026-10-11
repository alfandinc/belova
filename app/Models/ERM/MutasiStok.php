<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    use HasFactory;

    protected $table = 'erm_mutasi_stok';

    protected $fillable = [
        'nomor_mutasi',
        'gudang_id',
        'jenis_mutasi',
        'tanggal_mutasi',
        'tanggal_input',
        'status',
        'user_id',
        'cancelled_by',
        'cancelled_at',
        'revised_from_id',
        'total_nilai',
        'butuh_approval',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejected_reason',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_DONE = 'done';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $casts = [
        'tanggal_mutasi' => 'datetime',
        'tanggal_input' => 'datetime',
        'cancelled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'total_nilai' => 'decimal:2',
        'butuh_approval' => 'boolean',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    public function revisedFrom()
    {
        return $this->belongsTo(self::class, 'revised_from_id');
    }

    public function revisions()
    {
        return $this->hasMany(self::class, 'revised_from_id');
    }

    public function items()
    {
        return $this->hasMany(MutasiStokItem::class, 'mutasi_stok_id');
    }
}