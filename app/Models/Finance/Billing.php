<?php

namespace App\Models\Finance;

use App\Models\ERM\Visitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'finance_billing';
    protected $fillable = ['visitation_id', 'billable_id', 'billable_type', 'jumlah', 'keterangan'];

    public function billable()
    {
        return $this->morphTo();
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
