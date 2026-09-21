<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Slimming extends Model
{
    protected $table = 'erm_slimming';

    protected $fillable = [
        'visitation_id',
        'pasien_id',
        'dokter_id',
        'usia',
        'tb',
        'bb',
        'base_weight',
        'base_fat',
        'base_visceral_fat',
        'base_kcal',
        'base_bmi',
        'base_body_age',
        'lingkar_perut',
        'lingkar_lengan_kanan',
        'lingkar_lengan_kiri',
        'lingkar_paha_kanan',
        'lingkar_paha_kiri',
        'subcutaneous_whole_body',
        'subcutaneous_trunk',
        'subcutaneous_arms',
        'subcutaneous_legs',
        'skeletal_whole_body',
        'skeletal_trunk',
        'skeletal_arms',
        'skeletal_legs',
    ];

    protected $casts = [
        'usia' => 'integer',
        'tb' => 'float',
        'bb' => 'float',
        'base_weight' => 'float',
        'base_fat' => 'float',
        'base_visceral_fat' => 'float',
        'base_kcal' => 'float',
        'base_bmi' => 'float',
        'base_body_age' => 'integer',
        'lingkar_perut' => 'float',
        'lingkar_lengan_kanan' => 'float',
        'lingkar_lengan_kiri' => 'float',
        'lingkar_paha_kanan' => 'float',
        'lingkar_paha_kiri' => 'float',
        'subcutaneous_whole_body' => 'float',
        'subcutaneous_trunk' => 'float',
        'subcutaneous_arms' => 'float',
        'subcutaneous_legs' => 'float',
        'skeletal_whole_body' => 'float',
        'skeletal_trunk' => 'float',
        'skeletal_arms' => 'float',
        'skeletal_legs' => 'float',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }
}