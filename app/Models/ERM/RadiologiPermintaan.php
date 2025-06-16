<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class RadiologiPermintaan extends Model
{
    protected $table = 'erm_radiologi_permintaan';
    protected $fillable = ['visitation_id', 'radiologi_test_id', 'status', 'hasil', 'dokter_id'];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }

    public function radiologiTest()
    {
        return $this->belongsTo(RadiologiTest::class);
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
