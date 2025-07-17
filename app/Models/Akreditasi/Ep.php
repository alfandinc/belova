<?php
namespace App\Models\Akreditasi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ep extends Model
{
    use HasFactory;
    protected $table = 'akreditasi_eps';
    protected $fillable = ['standar_id', 'name', 'kelengkapan_bukti', 'skor_maksimal'];
    public function standar() {
        return $this->belongsTo(\App\Models\Akreditasi\Standar::class);
    }
    public function documents() {
        return $this->hasMany(\App\Models\Akreditasi\Document::class);
    }
}
