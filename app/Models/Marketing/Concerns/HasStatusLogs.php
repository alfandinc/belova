<?php

namespace App\Models\Marketing\Concerns;

use App\Models\Marketing\StatusLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Workflow history ("Riwayat") for a marketing record: who did what, when, with which note.
 */
trait HasStatusLogs
{
    public function statusLogs()
    {
        return $this->morphMany(StatusLog::class, 'loggable')->orderBy('created_at')->orderBy('id');
    }

    public function logStatus(string $action, ?string $from = null, ?string $to = null, ?string $notes = null, ?int $userId = null): ?StatusLog
    {
        // The table arrives with a migration; until it has run, workflows keep working without history
        if (!static::statusLogTableExists()) {
            return null;
        }

        return $this->statusLogs()->create([
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /** Timeline rows for the front-end, oldest first. */
    public function timeline(array $statusLabels = []): array
    {
        if (!static::statusLogTableExists()) {
            return [];
        }

        return $this->statusLogs()->with('user:id,name')->get()
            ->map(fn (StatusLog $log) => $log->toTimeline($statusLabels))
            ->all();
    }

    protected static function statusLogTableExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('marketing_status_logs');
    }
}
