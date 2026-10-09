<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Sent to an employee when HRD changes their schedule. $days = [[date, sebelum, sesudah], ...]
 * (readable states from ScheduleLog::stateOf). Shown on the main menu schedule card.
 */
class JadwalBerubahNotification extends Notification
{
    public function __construct(public array $days)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        usort($this->days, fn($a, $b) => strcmp($a[0], $b[0]));

        return [
            'type' => 'jadwal_berubah',
            'title' => 'Jadwal Anda diubah',
            'message' => implode('; ', array_map(
                fn($d) => Carbon::parse($d[0])->locale('id')->isoFormat('ddd D MMM') . ': ' . ($d[1] ?? 'Kosong') . ' → ' . ($d[2] ?? 'Kosong'),
                $this->days
            )),
            'days' => $this->days,
            'sender' => Auth::user()->name ?? 'HRD',
        ];
    }
}
