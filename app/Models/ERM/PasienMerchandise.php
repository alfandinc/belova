<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasienMerchandise extends Model
{
    use HasFactory;

    protected $table = 'erm_pasien_merchandises';

    protected $fillable = [
        'pasien_id', 'merchandise_id', 'quantity', 'notes', 'given_by_user_id', 'given_at'
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class, 'merchandise_id');
    }
}
