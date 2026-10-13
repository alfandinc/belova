<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Template for a Daily Journal task that repeats; one task is created per matching day. */
class DailyJournalRecurrence extends Model
{
    public const PATTERNS = ['daily', 'weekdays', 'weekly', 'monthly'];

    protected $fillable = [
        'user_id',
        'from_user_id',
        'title',
        'note',
        'scheduled_time',
        'pattern',
        'weekday',
        'month_day',
        'start_date',
        'end_date',
        'last_generated_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'last_generated_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(DailyJournalTask::class, 'recurrence_id');
    }

    public function matches(Carbon $date): bool
    {
        if ($date->lt($this->start_date) || ($this->end_date && $date->gt($this->end_date))) {
            return false;
        }

        return match ($this->pattern) {
            'daily' => true,
            'weekdays' => $date->isoWeekday() <= 5,
            'weekly' => $date->isoWeekday() === (int) $this->weekday,
            // The 31st falls back to the last day of shorter months
            'monthly' => $date->day === min((int) $this->month_day, $date->daysInMonth),
            default => false,
        };
    }

    public function label(): string
    {
        $days = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return match ($this->pattern) {
            'daily' => 'Setiap hari',
            'weekdays' => 'Setiap hari kerja (Sen–Jum)',
            'weekly' => 'Setiap ' . ($days[(int) $this->weekday] ?? '-'),
            'monthly' => 'Setiap tanggal ' . (int) $this->month_day,
            default => $this->pattern,
        };
    }
}
