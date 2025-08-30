<?php
namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrOmsetBulanan extends Model
{
    use HasFactory;
    protected $table = 'pr_omset_bulanan';
    protected $fillable = [
        'bulan',
        'insentif_omset_id',
        'nominal',
    ];

    public function insentifOmset()
    {
        return $this->belongsTo(PrInsentifOmset::class, 'insentif_omset_id');
    }
}
