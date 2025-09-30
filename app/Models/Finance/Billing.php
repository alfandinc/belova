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

    public function billable()
    {
        return $this->morphTo();
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
