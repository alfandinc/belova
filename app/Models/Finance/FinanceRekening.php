<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceRekening extends Model
{
    use HasFactory;     

    protected $table = 'finance_rekening';

    protected $fillable = ['bank', 'no_rekening', 'atas_nama'];
}
