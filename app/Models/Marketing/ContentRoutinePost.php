<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** The upload (or skip) of a routine content on one day. */
class ContentRoutinePost extends Model
{
    protected $table = 'marketing_content_routine_posts';

    public const POSTED = 'posted';
    public const SKIPPED = 'skipped';

    // jam_jadwal: the routine's upload time on that day ("tepat waktu" stays when the time changes later)
    protected $fillable = ['routine_id', 'tanggal', 'status', 'jam_jadwal', 'link_publikasi', 'notes', 'user_id', 'done_at'];

    protected $casts = [
        'tanggal' => 'date',
        'link_publikasi' => 'array',
        'done_at' => 'datetime',
    ];

    public function routine()
    {
        return $this->belongsTo(ContentRoutine::class, 'routine_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
