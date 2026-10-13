<?php

namespace App\Support\DailyJournal;

use App\Models\DailyJournalRecurrence;
use App\Models\DailyJournalTask;
use Carbon\Carbon;

/**
 * Creates today's task for each matching recurrence, lazily (when the user opens the journal or
 * a page with the reminder). Only today: missed days are not back-filled, so a long leave does not
 * leave a pile of stale copies.
 */
class RecurrenceGenerator
{
    public function generateFor(int $userId, ?Carbon $today = null): int
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $created = 0;

        $recurrences = DailyJournalRecurrence::query()
            ->where('user_id', $userId)
            ->whereDate('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->where(fn ($q) => $q->whereNull('last_generated_date')->orWhereDate('last_generated_date', '<', $today))
            ->get();

        foreach ($recurrences as $recurrence) {
            if ($recurrence->matches($today)) {
                $exists = DailyJournalTask::query()
                    ->where('recurrence_id', $recurrence->id)
                    ->whereDate('task_date', $today)
                    ->exists();

                if (!$exists) {
                    $this->createTask($recurrence, $today);
                    $created++;
                }
            }

            $recurrence->update(['last_generated_date' => $today->toDateString()]);
        }

        return $created;
    }

    public function createTask(DailyJournalRecurrence $recurrence, Carbon $date): DailyJournalTask
    {
        return DailyJournalTask::create([
            'user_id' => $recurrence->user_id,
            'from_user_id' => $recurrence->from_user_id,
            'recurrence_id' => $recurrence->id,
            'task_date' => $date->toDateString(),
            'title' => $recurrence->title,
            'note' => $recurrence->note,
            'scheduled_time' => $recurrence->scheduled_time,
            'status' => 'todo',
        ]);
    }
}
