<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinanceDanaApprover extends Model
{
    use HasFactory;

    protected $table = 'finance_dana_approver';

    protected $fillable = [
        'user_id',
        'jabatan',
        'aktif',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
