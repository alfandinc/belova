<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanTidakMasuk extends Model
{
    use HasFactory;

    protected $table = 'hrd_pengajuan_tidak_masuk';

    protected $fillable = [
        'employee_id',
        'jenis', // sakit/izin
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
        static::saving(function ($model) {
            $start = \Carbon\Carbon::parse($model->tanggal_mulai)->startOfDay();
            $end = \Carbon\Carbon::parse($model->tanggal_selesai)->startOfDay();
            $startTimestamp = $start->getTimestamp();
            $endTimestamp = $end->getTimestamp();
            $totalHari = (int)round(($endTimestamp - $startTimestamp) / 86400) + 1;
            $totalHari = abs($totalHari);
            if ($totalHari < 1) {
                $totalHari = 1;
            }
            $model->total_hari = $totalHari;
        });
    }
}
