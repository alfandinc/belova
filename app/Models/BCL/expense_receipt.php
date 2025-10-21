<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class expense_receipt extends Model
{
    use HasFactory;
    // correct table name (was misspelled: bcl_xpense_receipt)
    protected $table = 'bcl_expense_receipt';
    protected $guarded = ['id'];

    public function jurnal()
    {
        // expense_receipt.trans_id references fin_jurnal.doc_id
        return $this->belongsTo(Fin_jurnal::class, 'trans_id', 'doc_id');
    }
}
