<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanLibur extends Model
{
    use HasFactory;

    protected $table = 'hrd_pengajuan_libur';

    protected $fillable = [
        'employee_id',
        'jenis_libur',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_hari',
        'alasan',
        'status_manager',
        'notes_manager',
        'tanggal_persetujuan_manager',
        'status_hrd',
        'notes_hrd',
        'tanggal_persetujuan_hrd'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_persetujuan_manager' => 'datetime',
        'tanggal_persetujuan_hrd' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    /**
     * Calculate the total days between start and end dates when saving
     */
    protected static function boot()
    {
        parent::boot();
        
        // Force correct day calculation on both create and update
        static::saving(function ($model) {
            // Always recalculate total_hari for consistency
            $start = \Carbon\Carbon::parse($model->tanggal_mulai)->startOfDay();
            $end = \Carbon\Carbon::parse($model->tanggal_selesai)->startOfDay();
            
            // Calculate days by counting dates between start and end (inclusive)
            // Manual calculation to ensure accuracy:
            // 1. Get all days between the two dates
            // 2. Count them + 1 to include the start date
            $startTimestamp = $start->getTimestamp();
            $endTimestamp = $end->getTimestamp();
            $totalHari = (int)round(($endTimestamp - $startTimestamp) / 86400) + 1;
            
            // Force positive value (absolute) to prevent negative days
            $totalHari = abs($totalHari);
            
            // Safety check - ensure at least 1 day
            if ($totalHari < 1) {
                $totalHari = 1;
            }
            
            $model->total_hari = $totalHari;
        });
    }
}
