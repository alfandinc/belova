<?php
namespace App\Models\Akreditasi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;
    protected $table = 'akreditasi_documents';
    protected $fillable = ['ep_id', 'filename', 'filepath'];
    public function ep() {
        return $this->belongsTo(\App\Models\Akreditasi\Ep::class);
    }
}
