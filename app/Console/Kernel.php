<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\UpdateJatahCuti;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		UpdateJatahCuti::class,
	];

	/**
	 * Define the application's command schedule.
	 */
	protected function schedule(Schedule $schedule)
	{
		// Run daily at 00:05 to catch new year and anniversary updates
		$schedule->command('hrd:update-jatah-cuti')->dailyAt('00:05');
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
