<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObatHibah extends Model
{
    use HasFactory;

    protected $table = 'erm_obat_hibah';

    protected $fillable = [
        'nomor_hibah',
        'received_date',
        'sumber',
        'notes',
        'bukti',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    public function items()
    {
        return $this->hasMany(ObatHibahItem::class, 'obat_hibah_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public static function generateNomorHibah(): string
    {
        $prefix = 'HIB-' . now()->format('Ymd') . '-';
        $lastNumber = static::query()
            ->where('nomor_hibah', 'like', $prefix . '%')
            ->latest('id')
            ->value('nomor_hibah');

        $sequence = 1;
        if ($lastNumber) {
            $sequence = ((int) substr($lastNumber, -4)) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}