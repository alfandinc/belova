<?php
namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotulensiRapatTodo extends Model
{
    use HasFactory;

    protected $table = 'notulensi_rapat_todos';

    protected $fillable = [
        'notulensi_rapat_id',
        'task',
        'status',
        'due_date',
    ];

    public function notulensiRapat()
    {
        return $this->belongsTo(NotulensiRapat::class);
    }
        public function approved_by_user()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

}
