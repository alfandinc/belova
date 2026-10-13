<?php

namespace App\Support;

/**
 * Database notifications that have their own UI. Generic "any unread notification" pollers
 * (ERM / Farmasi / Kasir popups) must skip these, or they mark them read before their UI shows them.
 */
class NotificationTypes
{
    public const OWN_UI = [
        \App\Notifications\JadwalBerubahNotification::class,
        \App\Notifications\DailyJournalTaskAssigned::class,
        \App\Notifications\DailyJournalReportSent::class,
        \App\Notifications\DailyJournalTaskCompleted::class,
    ];
}
