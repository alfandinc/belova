<?php

namespace App\Models\ERM;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** One saved change of a visit's odontogram (append only). */
class OdontogramRiwayat extends Model
{
    protected $table = 'erm_odontogram_riwayat';

    public const UPDATED_AT = null;

    protected $fillable = ['visitation_id', 'pasien_id', 'user_id', 'sebelum', 'sesudah', 'perubahan'];

    protected $casts = [
        'sebelum' => 'array',
        'sesudah' => 'array',
        'perubahan' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
