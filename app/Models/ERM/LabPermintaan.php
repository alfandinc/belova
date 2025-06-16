<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class LabPermintaan extends Model
{
    protected $table = 'erm_lab_permintaan';
    protected $fillable = ['visitation_id', 'lab_test_id', 'status', 'hasil', 'dokter_id'];
    
    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
    
    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }
    
    public function dokter()
    {
        return $this->belongsTo(Dokter::class);
    }

    public function billings()
    {
        return $this->morphMany(Billing::class, 'billable');
    }
    
}
