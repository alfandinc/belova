<?php
namespace App\Models\Akreditasi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bab extends Model
{
    use HasFactory;
    protected $table = 'akreditasi_babs';
    protected $fillable = ['name'];
    public function standars() {
        return $this->hasMany(\App\Models\Akreditasi\Standar::class);
    }
}
