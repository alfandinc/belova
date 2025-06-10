<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HRD\PerformanceEvaluationPeriod;
use Carbon\Carbon;

class PerformanceEvaluationPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create sample evaluation periods
        $periods = [
            [
                'name' => 'Evaluasi Kinerja Semester 1 2025',
                'start_date' => Carbon::create(2025, 1, 1),
                'end_date' => Carbon::create(2025, 6, 30),
                'status' => 'completed',
            ],
            [
                'name' => 'Evaluasi Kinerja Semester 2 2025',
                'start_date' => Carbon::create(2025, 7, 1),
                'end_date' => Carbon::create(2025, 12, 31),
                'status' => 'active',
            ],
            [
                'name' => 'Evaluasi Kinerja Triwulan 1 2025',
                'start_date' => Carbon::create(2025, 1, 1),
                'end_date' => Carbon::create(2025, 3, 31),
                'status' => 'completed',
            ],
            [
                'name' => 'Evaluasi Kinerja Triwulan 2 2025',
                'start_date' => Carbon::create(2025, 4, 1),
                'end_date' => Carbon::create(2025, 6, 30),
                'status' => 'completed',
            ],
        ];

        foreach ($periods as $period) {
            PerformanceEvaluationPeriod::create($period);
        }
    }
}
