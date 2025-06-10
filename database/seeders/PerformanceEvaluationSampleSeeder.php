<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HRD\{
    PerformanceEvaluation,
    PerformanceEvaluationPeriod,
    PerformanceQuestion,
    PerformanceScore,
    Employee,
    Division
};
use Carbon\Carbon;

class PerformanceEvaluationSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // This seeder assumes you already have:
        // 1. Employee data with user associations
        // 2. Division data with manager assigned
        // 3. Periods created using PerformanceEvaluationPeriodSeeder
        // 4. Questions created using PerformanceQuestionSeeder

        // Get a completed period
        $period = PerformanceEvaluationPeriod::where('status', 'completed')->first();

        if (!$period) {
            $this->command->info('No completed periods found. Skipping evaluation samples.');
            return;
        }

        // Get HRD division
        $hrdDivision = Division::where('name', 'like', '%HRD%')->first();
        if (!$hrdDivision) {
            $this->command->info('HRD division not found. Please create it first.');
            return;
        }

        // Get HRD employees
        $hrdEmployees = Employee::whereHas('division', function ($q) {
            $q->where('name', 'like', '%HRD%');
        })->get();

        if ($hrdEmployees->isEmpty()) {
            $this->command->info('No HRD employees found. Please create some first.');
            return;
        }

        // Get managers (employees with manager role)
        $managers = Employee::whereHas('user', function ($q) {
            $q->whereHas('roles', function ($q) {
                $q->where('name', 'manager');
            });
        })->get();

        if ($managers->isEmpty()) {
            $this->command->info('No managers found. Please create some first.');
            return;
        }

        // Get regular employees (non-managers and non-HRD)
        $regularEmployees = Employee::whereDoesntHave('user', function ($q) {
            $q->whereHas('roles', function ($q) {
                $q->where('name', 'manager');
            });
        })->whereHas('division', function ($q) {
            $q->where('name', 'not like', '%HRD%');
        })->get();

        if ($regularEmployees->isEmpty()) {
            $this->command->info('No regular employees found. Please create some first.');
            return;
        }

        $this->command->info('Creating sample evaluations...');

        // 1. HRD to Managers evaluations
        foreach ($hrdEmployees as $hrd) {
            foreach ($managers as $manager) {
                $this->createEvaluation($period, $hrd, $manager, 'hrd_to_manager');
            }
        }

        // 2. Managers to their Employees
        foreach ($managers as $manager) {
            $divisionEmployees = $regularEmployees->where('division', $manager->division)->all();
            foreach ($divisionEmployees as $employee) {
                $this->createEvaluation($period, $manager, $employee, 'manager_to_employee');
            }
        }

        // 3. Employees to their Managers
        foreach ($regularEmployees as $employee) {
            // Find the manager of this employee's division
            $divisionManager = $managers->first(function ($manager) use ($employee) {
                return $manager->division->id === $employee->division->id;
            });

            if ($divisionManager) {
                $this->createEvaluation($period, $employee, $divisionManager, 'employee_to_manager');
            }
        }

        // 4. Managers to HRD
        foreach ($managers as $manager) {
            foreach ($hrdEmployees as $hrd) {
                $this->createEvaluation($period, $manager, $hrd, 'manager_to_hrd');
            }
        }

        $this->command->info('Sample evaluations created successfully!');
    }

    private function createEvaluation($period, $evaluator, $evaluatee, $evaluationType)
    {
        // Create evaluation
        $evaluation = PerformanceEvaluation::create([
            'period_id' => $period->id,
            'evaluator_id' => $evaluator->id,
            'evaluatee_id' => $evaluatee->id,
            'status' => 'completed',
            'completed_at' => Carbon::now()->subDays(rand(1, 30)),
        ]);

        // Get questions for this evaluation type
        $questions = PerformanceQuestion::where('evaluation_type', $evaluationType)
            ->where('is_active', true)
            ->get();

        // Create random scores
        foreach ($questions as $question) {
            PerformanceScore::create([
                'evaluation_id' => $evaluation->id,
                'question_id' => $question->id,
                'score' => rand(3, 5), // Random score between 3 and 5 for positive bias
                'comment' => $this->getRandomComment($question->question_text, $evaluationType),
            ]);
        }
    }

    private function getRandomComment($question, $evaluationType)
    {
        // Only add comments to some questions
        if (rand(0, 1) == 0) {
            return null;
        }

        $positiveComments = [
            'Sangat baik dalam aspek ini',
            'Menunjukkan kemampuan yang luar biasa',
            'Terus pertahankan kinerja yang baik',
            'Melebihi ekspektasi dalam hal ini',
            'Salah satu kekuatan utama',
        ];

        $improvementComments = [
            'Masih perlu pengembangan lebih lanjut',
            'Ada ruang untuk perbaikan',
            'Perlu lebih konsisten',
            'Membutuhkan bimbingan dalam area ini',
            'Dapat ditingkatkan dengan pelatihan tambahan',
        ];

        // Higher chance for positive comments
        $comments = rand(0, 2) == 0 ? $improvementComments : $positiveComments;
        return $comments[array_rand($comments)];
    }
}
