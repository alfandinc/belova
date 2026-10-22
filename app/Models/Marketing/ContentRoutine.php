<?php

namespace App\Models\Marketing;

use App\Models\Marketing\Concerns\HasStatusLogs;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * "Konten rutin": made once, uploaded on fixed days at a fixed time (e.g. "We Are Open" every day 09:00).
 * Each scheduled day shows on the Content Plan board; the upload of that day is a ContentRoutinePost.
 */
class ContentRoutine extends Model
{
    use HasStatusLogs;

    protected $table = 'marketing_content_routines';

    public const DAY_LABELS = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];

    /** Label of each action in the "Riwayat" timeline. */
    public const ACTION_LABELS = [
        'created' => 'Membuat konten rutin',
        'updated' => 'Mengubah konten rutin',
        'posted' => 'Upload',
        'skipped' => 'Melewati',
        'undo' => 'Membatalkan catatan',
        'paused' => 'Menonaktifkan',
        'resumed' => 'Mengaktifkan lagi',
    ];

    protected $fillable = [
        'judul',
        'brand',
        'platform',
        'jenis_konten',
        'jam',
        'days',
        'start_date',
        'end_date',
        'assigned_to',
        'asset_link',
        'caption',
        'catatan',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'brand' => 'array',
        'platform' => 'array',
        'jenis_konten' => 'array',
        'days' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function posts()
    {
        return $this->hasMany(ContentRoutinePost::class, 'routine_id');
    }

    /** "HH:MM" of the upload. */
    public function jamLabel(): string
    {
        return substr((string) ($this->jam ?: '09:00'), 0, 5);
    }

    /** Whether the routine is scheduled on this day (ignores is_active, see the schedule). */
    public function scheduledOn(Carbon $day): bool
    {
        if ($day->lt($this->start_date->copy()->startOfDay())) {
            return false;
        }
        if ($this->end_date && $day->gt($this->end_date->copy()->endOfDay())) {
            return false;
        }

        return in_array($day->dayOfWeekIso, array_map('intval', (array) $this->days), true);
    }

    /** Upload moment of a day. */
    public function slotOn(Carbon $day): Carbon
    {
        return Carbon::parse($day->toDateString() . ' ' . $this->jamLabel());
    }

    /** "Setiap hari" / "Sen, Rab, Jum". */
    public function daysLabel(): string
    {
        $days = array_map('intval', (array) $this->days);
        sort($days);
        if (count($days) === 7) {
            return 'Setiap hari';
        }
        if ($days === [1, 2, 3, 4, 5]) {
            return 'Senin–Jumat';
        }
        if ($days === [1, 2, 3, 4, 5, 6]) {
            return 'Senin–Sabtu';
        }

        return implode(', ', array_map(fn ($d) => self::DAY_LABELS[$d] ?? $d, $days));
    }
}
