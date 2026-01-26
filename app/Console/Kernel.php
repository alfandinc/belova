<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\UpdateJatahCuti;
use App\Console\Commands\SendScheduledWhatsapp;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		UpdateJatahCuti::class,
		\App\Console\Commands\SimulateKpi::class,
		\App\Console\Commands\BackfillInvoiceStock::class,
		SendScheduledWhatsapp::class,
	];

	/**
	 * Define the application's command schedule.
	 */
	protected function schedule(Schedule $schedule)
	{
		// Run daily at 00:05 to catch new year and anniversary updates
		$schedule->command('hrd:update-jatah-cuti')->dailyAt('00:05');

		// Send scheduled WhatsApp messages every minute
		$schedule->command('wa:send-scheduled')->everyMinute();
	}

	/**
	 * Register the commands for the application.
	 */
	protected function commands()
	{
		$this->load(__DIR__ . '/Commands');

		require base_path('routes/console.php');
	}
}
