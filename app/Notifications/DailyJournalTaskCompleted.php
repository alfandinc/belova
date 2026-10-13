<?php

namespace App\Notifications;

use App\Models\DailyJournalTask;
use Illuminate\Notifications\Notification;

/** A subordinate finished a Daily Journal task. Shown as a toast to their manager / the task giver. */
class DailyJournalTaskCompleted extends Notification
{
    public function __construct(public DailyJournalTask $task, public string $doerName)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'daily_journal_completed',
            'title' => $this->doerName . ' menyelesaikan task',
            'message' => $this->task->title,
            'task_id' => $this->task->id,
            'date' => $this->task->task_date?->toDateString(),
            'sender' => $this->doerName,
        ];
    }
}
