<?php

namespace App\Models\HRD\Concerns;

use App\Models\HRD\PersetujuanAtasan;

/**
 * Per-superior decisions of a pengajuan. The using model defines const JENIS_PERSETUJUAN.
 */
trait HasPersetujuanAtasan
{
    public function persetujuanAtasan()
    {
        return $this->hasMany(PersetujuanAtasan::class, 'pengajuan_id')
            ->where('jenis', static::JENIS_PERSETUJUAN)
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
