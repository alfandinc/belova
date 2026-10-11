<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaan extends Model
{
    use HasFactory;

    protected $table = 'erm_permintaan';

    protected $fillable = [
        'no_permintaan',
        'request_date',
        'approved_by',
        'approved_date',
        'status',
        'created_by',
        'notes',
        'rejected_by',
        'rejected_at',
        'rejected_reason',
    ];

    protected $casts = [
        'approved_date' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    public function items()
    {
        return $this->hasMany(PermintaanItem::class, 'permintaan_id');
    }

    public function fakturBelis()
    {
        return $this->hasMany(FakturBeli::class, 'permintaan_id');
    }
}
