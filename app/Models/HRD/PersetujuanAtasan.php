<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

/**
 * A direct superior's decision on a pengajuan (see the 2026_11_24 migration).
 */
class PersetujuanAtasan extends Model
{
    protected $table = 'hrd_pengajuan_persetujuan_atasan';

    protected $fillable = ['jenis', 'pengajuan_id', 'approver_employee_id', 'status', 'notes'];

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id')->withoutGlobalScope('active');
    }
}
