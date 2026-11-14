<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Laravel 11 reads schedules here (app/Console/Kernel.php is not used).
// Needs `php artisan schedule:run` every minute (Windows Task Scheduler); the karyawan list page also
// carries out due ones when opened, so nothing is missed without it.
Schedule::command('hrd:proses-nonaktif')->dailyAt('00:10');
