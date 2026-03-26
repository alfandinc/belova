<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyJournalTask extends Model
{
    use HasFactory;

    public const STATUSES = [
        'todo',
        'in_progress',
        'done',
        'skipped',
    ];

    public const THEMES = [
        'rose',
        'lavender',
        'mint',
        'sky',
        'peach',
    ];

    protected $fillable = [
        'user_id',
        'from_user_id',
        'task_date',
        'deadline_date',
        'title',
        'note',
        'scheduled_time',
        'status',
        'reported',
        'color_theme',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'task_date' => 'date',
            'deadline_date' => 'date',
            'reported' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}