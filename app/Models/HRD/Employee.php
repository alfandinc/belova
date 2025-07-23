<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'hrd_employee';

    protected $fillable = [
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'nik',
        'no_induk',
        'no_darurat', // Emergency contact number
        'alamat',
        'village_id',
        'position',       // Changed from 'posisi' to match migration
        'division_id',       // Changed from 'divisi' to match migration
        'pendidikan',
        'no_hp',
        'tanggal_masuk',
        'status',
        'kontrak_berakhir',
        'masa_pensiun',
        'doc_cv',
        'doc_ktp',
        'doc_kontrak',
        'doc_pendukung',
        'user_id',
        'photo' 
    ];

    protected $casts = [
    'tanggal_lahir' => 'date',
    'tanggal_masuk' => 'date',
    'kontrak_berakhir' => 'date',
    'masa_pensiun' => 'date',
];

    public function position()
    {
        // Make sure this points to the correct foreign key
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function division()
    {
        // Make sure this points to the correct foreign key
        return $this->belongsTo(Division::class, 'division_id');
    }
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
        // OR if you determine managers by a flag
        // return $this->hasOne(Employee::class)->where('is_manager', true);
    }

    public function village()
    {
        return $this->belongsTo(\App\Models\Area\Village::class, 'village_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    
    public function contracts()
    {
        return $this->hasMany(EmployeeContract::class, 'employee_id');
    }
    
    public function activeContract()
    {
        return $this->hasOne(EmployeeContract::class, 'employee_id')
                    ->where('status', 'active')
                    ->orderBy('end_date', 'desc');
    }
    
    public function lastContract()
    {
        return $this->hasOne(EmployeeContract::class, 'employee_id')
                    ->latest('end_date');
    }

    public function isManager()
    {
        // Make case-insensitive for safety
        return $this->user && $this->user->hasRole(['manager', 'Manager']);
    }

    // Add these new relationships
    public function evaluationsAsEvaluator()
    {
        return $this->hasMany(PerformanceEvaluation::class, 'evaluator_id');
    }

    public function evaluationsAsEvaluatee()
    {
        return $this->hasMany(PerformanceEvaluation::class, 'evaluatee_id');
    }

    // Get pending evaluations for this employee to complete
    public function getPendingEvaluationsAttribute()
    {
        return $this->evaluationsAsEvaluator()
            ->where('status', 'pending')
            ->with(['evaluatee', 'period'])
            ->get();
    }

    // Get average score for a given period
    public function getScoreForPeriod($periodId)
    {
        $evaluations = $this->evaluationsAsEvaluatee()
            ->where('period_id', $periodId)
            ->where('status', 'completed')
            ->with('scores.question') // Added question relation to check question_type
            ->get();

        if ($evaluations->isEmpty()) {
            return null;
        }

        $allScores = collect();
        foreach ($evaluations as $evaluation) {
            $allScores = $allScores->concat($evaluation->scores);
        }
        
        // Filter scores to only include score-type questions (not text questions)
        $scoreTypeScores = $allScores->filter(function ($score) {
            return $score->question && $score->question->question_type === 'score';
        });
        
        // Return 0 if no score-type questions, otherwise calculate average
        if ($scoreTypeScores->isEmpty()) {
            return 0;
        }
        
        return round($scoreTypeScores->avg('score'), 2);
    }

    public function jatahLibur()
    {
        return $this->hasOne(JatahLibur::class, 'employee_id');
    }

    public function pengajuanLibur()
    {
        return $this->hasMany(PengajuanLibur::class, 'employee_id');
    }

    /**
     * Ensure the employee has a jatah libur record
     * If not, it creates a new one with default values
     *
     * @param int $defaultCutiTahunan Default value for annual leave
     * @param int $defaultGantiLibur Default value for replacement leave
     * @return JatahLibur
     */
    public function ensureJatahLibur($defaultCutiTahunan = 0, $defaultGantiLibur = 0)
    {
        if (!$this->jatahLibur) {
            return JatahLibur::create([
                'employee_id' => $this->id,
                'jatah_cuti_tahunan' => $defaultCutiTahunan,
                'jatah_ganti_libur' => $defaultGantiLibur
            ]);
        }
        
        return $this->jatahLibur;
    }
}
