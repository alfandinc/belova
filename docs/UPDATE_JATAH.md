This document explains the `hrd:update-jatah-cuti` artisan command.

What it does
- Runs daily and sets `jatah_cuti_tahunan` to 12 for employees who are eligible.
- Eligibility rules implemented:
  - If an employee has tenure >= 1 year and it's New Year's Day, set to 12.
  - If an employee has tenure < 1 year but today is exactly their 1-year anniversary, set to 12.

How to run manually
- From the project root run:

  php artisan hrd:update-jatah-cuti

Scheduling
- The command is registered in `app/Console/Kernel.php` and scheduled daily at 00:05.
- On Windows, use Task Scheduler to run `php artisan schedule:run` every minute as recommended by Laravel scheduling docs.

Notes & testing
- You can test for a specific date by temporarily modifying the command to set `$today = Carbon::create(YYYY, MM, DD);` then run it.
- The command logs updates to the application log via `Log::info`.

Edge cases
- Employees without `tanggal_masuk` are skipped.
- The command uses firstOrCreate so if a `hrd_jatah_libur` record does not exist it will be created with `jatah_cuti_tahunan = 12` and `jatah_ganti_libur = 0`.

If you'd like different rules (e.g., pro-rated days instead of immediate 12 on anniversary), tell me and I can adjust the logic.