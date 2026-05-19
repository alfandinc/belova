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
        'riwayat_tindakan_id',
        'tb',
        'bb',
        'target_weight',
        'weight_control',
        'lingkar_perut',
        'lingkar_lengan_kanan',
        'lingkar_lengan_kiri',
        'muscle_fat_weight',
        'muscle_fat_muscle',
        'muscle_fat_body_fat_mass',
        'obesity_bmi',
        'obesity_analysis',
        'obesity_eval_bmi',
        'obesity_eval',
        'pbf',
        'subcutaneous_fat',
        'subcutaneous_whole_body',
        'subcutaneous_trunk',
        'subcutaneous_arms',
        'subcutaneous_legs',
        'skeletal_muscle',
        'skeletal_whole_body',
        'skeletal_trunk',
        'skeletal_arms',
        'skeletal_legs',
        'research_basal_metabolic_rate',
        'visceral_fat_level',
    ];

    protected $casts = [
        'tb' => 'float',
        'bb' => 'float',
        'target_weight' => 'float',
        'weight_control' => 'float',
        'lingkar_perut' => 'float',
        'lingkar_lengan_kanan' => 'float',
        'lingkar_lengan_kiri' => 'float',
        'muscle_fat_weight' => 'float',
        'muscle_fat_muscle' => 'float',
        'muscle_fat_body_fat_mass' => 'float',
        'obesity_bmi' => 'float',
        'obesity_eval_bmi' => 'float',
        'pbf' => 'float',
        'subcutaneous_whole_body' => 'float',
        'subcutaneous_trunk' => 'float',
        'subcutaneous_arms' => 'float',
        'subcutaneous_legs' => 'float',
        'skeletal_whole_body' => 'float',
        'skeletal_trunk' => 'float',
        'skeletal_arms' => 'float',
        'skeletal_legs' => 'float',
        'research_basal_metabolic_rate' => 'float',
        'visceral_fat_level' => 'float',
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

    public function riwayatTindakan()
    {
        return $this->belongsTo(RiwayatTindakan::class, 'riwayat_tindakan_id');
    }
}