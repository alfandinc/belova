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
        'user_id'
    ];

    protected $dates = [
        'tanggal_lahir',
        'tanggal_masuk',
        'kontrak_berakhir',
        'masa_pensiun',
    ];

    public function position()
    {
        // Make sure this points to the correct foreign key
        return $this->belongsTo(Position::class, 'position', 'id');
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
            ->with('scores')
            ->get();

        if ($evaluations->isEmpty()) {
            return null;
        }

        $allScores = collect();
        foreach ($evaluations as $evaluation) {
            $allScores = $allScores->concat($evaluation->scores);
        }

        return $allScores->avg('score');
    }
}
