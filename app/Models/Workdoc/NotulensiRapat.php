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
        'memo',
        'created_by',
    ];

        public function todos()
    {
        return $this->hasMany(NotulensiRapatTodo::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

}
