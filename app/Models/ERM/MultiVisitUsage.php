<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class MultiVisitUsage extends Model
{
    protected $table = 'erm_multi_visit_usages';

    protected $fillable = [
        'pasien_id',
        'tindakan_id',
        'first_visitation_id',
        'total',
        'used'
    ];

    /**
     * The visit that bought the package was cancelled or deleted: the package never stood. Worked out from the
     * visit rather than removed, so restoring a cancelled visit brings it back. Older usages without a first
     * visit are left valid.
     */
    public function isVoid(): bool
    {
        if (empty($this->first_visitation_id)) {
            return false;
        }

        $status = Visitation::whereKey($this->first_visitation_id)->value('status_kunjungan');

        return $status === null || (int) $status === Visitation::STATUS_DIBATALKAN;
    }

    /**
     * The package can give a free session at this visit once its purchase is fully paid; a session on the visit
     * that buys it is fine, as that invoice carries the charge.
     */
    public function isPaidFor(?string $visitationId = null): bool
    {
        if (empty($this->first_visitation_id) || (string) $this->first_visitation_id === (string) $visitationId) {
            return true;
        }

        $invoice = \App\Models\Finance\Invoice::where('visitation_id', $this->first_visitation_id)->orderByDesc('id')->first();

        return $invoice && !empty($invoice->payment_method) && $invoice->isLunas();
    }

    public function pasien()
    {
        return $this->belongsTo(\App\Models\Pasien::class, 'pasien_id');
    }

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id');
    }
}
