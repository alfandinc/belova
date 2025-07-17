<?php
namespace App\Models\Akreditasi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Standar extends Model
{
    use HasFactory;
    protected $table = 'akreditasi_standars';
    protected $fillable = ['bab_id', 'name'];
    public function bab() {
        return $this->belongsTo(\App\Models\Akreditasi\Bab::class);
    }
    public function eps() {
        return $this->hasMany(\App\Models\Akreditasi\Ep::class);
    }
}
