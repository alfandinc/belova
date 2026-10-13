<?php

namespace App\Notifications;

use App\Models\DailyJournalTask;
use Illuminate\Notifications\Notification;

/** A manager gave the user a Daily Journal task. Shown by the journal reminder popup. */
class DailyJournalTaskAssigned extends Notification
{
    public function __construct(public DailyJournalTask $task, public string $senderName, public ?string $repeatLabel = null)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'daily_journal_assigned',
            'title' => 'Task baru dari ' . $this->senderName,
            'message' => $this->task->title
                . ' · ' . $this->task->task_date?->locale('id')->isoFormat('ddd D MMM')
                . ($this->task->deadline_date ? ' · deadline ' . $this->task->deadline_date->locale('id')->isoFormat('D MMM') : '')
                . ($this->repeatLabel ? ' · ' . $this->repeatLabel : ''),
            'task_id' => $this->task->id,
            'date' => $this->task->task_date?->toDateString(),
            'sender' => $this->senderName,
        ];
    }
}
