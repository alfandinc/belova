<?php

namespace App\Models\ERM;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasienMerchandise extends Model
{
    use HasFactory;

    protected $table = 'erm_pasien_merchandises';

    protected $fillable = [
        'pasien_id', 'merchandise_id', 'quantity', 'stock_qty', 'notes', 'given_by_user_id', 'given_at',
        'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class, 'merchandise_id');
    }

    public function givenBy()
    {
        return $this->belongsTo(User::class, 'given_by_user_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
