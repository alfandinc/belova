<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Klinik extends Model
{
    protected $table = 'erm_klinik';
    protected $fillable = ['nama', 'report_cutoff_time'];

    public function visitation()
    {
        return $this->hasMany(Visitation::class, 'klinik_id');
    }

    public function dokters(): BelongsToMany
    {
        return $this->belongsToMany(Dokter::class, 'erm_dokter_kliniks', 'klinik_id', 'dokter_id')
            ->withTimestamps();
    }
}
