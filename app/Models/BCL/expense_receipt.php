<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class expense_receipt extends Model
{
    use HasFactory;
    protected $table = 'bcl_xpense_receipt';
    protected $guarded = ['id'];

    public function jurnal()
    {
        return $this->belongsTo(Fin_jurnal::class, 'doc_id', 'trans_id');
    }
}
