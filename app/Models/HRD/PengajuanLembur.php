<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanLembur extends Model
{
    use HasFactory;

    protected $table = 'hrd_pengajuan_lembur';

    protected $fillable = [
        'employee_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'total_jam',
        'alasan',
        'status_manager',
        'notes_manager',
        'tanggal_persetujuan_manager',
        'status_hrd',
        'notes_hrd',
        'tanggal_persetujuan_hrd'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_persetujuan_manager' => 'datetime',
        'tanggal_persetujuan_hrd' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    /**
     * Calculate the total hours between start and end time when saving
     */
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if ($model->jam_mulai && $model->jam_selesai) {
                $start = strtotime($model->jam_mulai);
                $end = strtotime($model->jam_selesai);
                if ($start !== false && $end !== false && $end > $start) {
                    $model->total_jam = intval(($end - $start) / 60);
                } else {
                    $model->total_jam = 0;
                }
            }
        });
    }

    /**
     * Get total lembur in format "X jam Y menit"
     */
    public function getTotalJamFormattedAttribute()
    {
        $minutes = (int) $this->total_jam;
        $jam = floor($minutes / 60);
        $menit = $minutes % 60;
        $result = [];
        if ($jam > 0) $result[] = $jam . ' jam';
        if ($menit > 0) $result[] = $menit . ' menit';
        if (empty($result)) return '0 menit';
        return implode(' ', $result);
    }
}
