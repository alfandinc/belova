<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class LabPaket extends Model
{
    protected $table = 'erm_lab_paket';

    protected $fillable = ['nama', 'deskripsi', 'harga_paket'];

    public function labTests()
    {
        return $this->belongsToMany(LabTest::class, 'erm_lab_paket_detail', 'lab_paket_id', 'lab_test_id')
            ->withTimestamps();
    }

    public function billings()
    {
        return $this->morphMany(Billing::class, 'billable');
    }

    public function labPermintaans()
    {
        return $this->hasMany(LabPermintaan::class, 'lab_paket_id');
    }
}