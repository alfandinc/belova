<?php

namespace App\Models\Finance;

use App\Models\ERM\Visitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Billing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'finance_billing';
    protected $fillable = ['visitation_id', 'billable_id', 'billable_type', 'jumlah', 'qty', 'keterangan', 'diskon' , 'diskon_type'];

    protected static function booted(): void
    {
        // A percentage discount is kept within 0-100 (e.g. 200% used to be stored and then shown as a
        // discount larger than the price). Nominal discounts are not capped here: a group-level nominal
        // discount (racikan / tindakan qty > 1) is intentionally stored on the first row of the group.
        static::saving(function (Billing $billing) {
            if ($billing->diskon_type === '%' && $billing->diskon !== null && $billing->diskon !== '') {
                $billing->diskon = min(100, max(0, (float) $billing->diskon));
            }
        });
    }

    public function billable()
    {
        return $this->morphTo();
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
