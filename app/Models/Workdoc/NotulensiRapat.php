<?php
namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotulensiRapat extends Model
{
    use HasFactory;

    protected $table = 'notulensi_rapat';

    protected $fillable = [
        'title',
        'date',
        'notulen',
    ];
}
