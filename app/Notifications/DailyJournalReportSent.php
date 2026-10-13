<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** A subordinate sent their Daily Journal day summary. Shown by the journal reminder popup. */
class DailyJournalReportSent extends Notification
{
    /**
     * @param array $counts ['total' => int, 'done' => int, 'in_progress' => int, 'todo' => int, 'skipped' => int]
     * @param array $tasks  [['title' => string, 'status' => string], ...]
     */
    public function __construct(
        public int $reporterId,
        public string $reporterName,
        public string $date,
        public array $counts,
        public array $tasks,
    ) {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'daily_journal_report',
            'title' => 'Laporan harian ' . $this->reporterName,
            'message' => $this->counts['done'] . ' dari ' . $this->counts['total'] . ' task selesai'
                . ($this->counts['in_progress'] ? ' · ' . $this->counts['in_progress'] . ' dikerjakan' : '')
                . ($this->counts['todo'] ? ' · ' . $this->counts['todo'] . ' belum mulai' : ''),
            'reporter_id' => $this->reporterId,
            'date' => $this->date,
            'counts' => $this->counts,
            'tasks' => $this->tasks,
            'sender' => $this->reporterName,
        ];
    }
}
