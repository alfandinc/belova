<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fin_jurnal extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'bcl_fin_jurnal';
    protected $primaryKey = 'id';

    // Relation to User who created the journal entry
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    public function receipt()
    {
        return $this->hasMany(expense_receipt::class, 'trans_id', 'doc_id');
    }
}
