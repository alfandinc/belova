<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HRD\Employee;
use App\Models\HRD\JatahLibur;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateJatahCuti extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrd:update-jatah-cuti';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update jatah cuti tahunan to 12 for eligible employees based on tenure and anniversaries.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Running UpdateJatahCuti on {$today->toDateString()}");

        try {
            $employees = Employee::all();
            $updated = 0;
            $created = 0;

            foreach ($employees as $employee) {
                // Ensure tanggal_masuk is present
                if (!$employee->tanggal_masuk) {
                    continue;
                }

                $tanggalMasuk = Carbon::parse($employee->tanggal_masuk)->startOfDay();
                $tenureInYears = $tanggalMasuk->diffInYears($today);

                // Determine if eligible today
                $isNewYear = $today->isSameDay(Carbon::create($today->year, 1, 1));

                // Anniversary: employee reaches 1 year today or multiple of years
                $isAnniversary = false;
                // If employee's anniversary date (month/day) equals today
                if ($tanggalMasuk->month === $today->month && $tanggalMasuk->day === $today->day) {
                    // Only consider if diffInYears >= 1
                    if ($tanggalMasuk->diffInYears($today) >= 1) {
                        $isAnniversary = true;
                    }
                }

                // Eligibility rules:
                // - If employee has tenure >= 1 year and it's New Year, set to 12.
                // - If employee has tenure < 1 year but today is exactly their 1-year anniversary, set to 12.
                $shouldSetTo12 = false;

                if ($tenureInYears >= 1 && $isNewYear) {
                    $shouldSetTo12 = true;
                }

                if ($tenureInYears < 1 && $isAnniversary) {
                    $shouldSetTo12 = true;
                }

                if (!$shouldSetTo12) {
                    continue;
                }

                // Update or create jatah libur
                $jatah = JatahLibur::firstOrCreate(
                    ['employee_id' => $employee->id],
                    ['jatah_cuti_tahunan' => 12, 'jatah_ganti_libur' => 0]
                );

                // If existing and not 12, update
                if ($jatah->jatah_cuti_tahunan != 12) {
                    $jatah->jatah_cuti_tahunan = 12;
                    $jatah->save();
                    $updated++;
                    Log::info("Set jatah_cuti_tahunan=12 for employee_id={$employee->id}");
                } else {
                    // if newly created, count
                    if ($jatah->wasRecentlyCreated) {
                        $created++;
                        Log::info("Created jatah_libur with jatah_cuti_tahunan=12 for employee_id={$employee->id}");
                    }
                }
            }

            $this->info("Update complete. Updated: {$updated}, Created: {$created}");
            return 0;
        } catch (\Exception $e) {
            Log::error('Error in UpdateJatahCuti: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
