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
        'jam_mulai_diajukan',
        'jam_selesai_diajukan',
        'total_jam_diajukan',
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
        return $this->belongsTo(Employee::class)->withInactive();
    }
    
    /**
     * Calculate the total hours between start and end time when saving
     */
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if ($model->jam_mulai && $model->jam_selesai && $model->tanggal) {
                $tanggalStr = is_object($model->tanggal) ? $model->tanggal->format('Y-m-d') : $model->tanggal;
                $start = \Carbon\Carbon::parse($tanggalStr . ' ' . $model->jam_mulai);
                // If jam_selesai < jam_mulai, assume next day
                if ($model->jam_selesai > $model->jam_mulai) {
                    $end = \Carbon\Carbon::parse($tanggalStr . ' ' . $model->jam_selesai);
                } else {
                    $end = \Carbon\Carbon::parse($tanggalStr . ' ' . $model->jam_selesai)->addDay();
                }
                $minutes = $start->diffInMinutes($end);
                $model->total_jam = $minutes;
            }
        });
    }

    /**
     * Get total lembur in format "X jam Y menit"
     */
    public function getTotalJamFormattedAttribute()
    {
        return self::formatMinutes($this->total_jam);
    }

    /**
     * Originally requested total (before an approver reduced it), formatted. Null when never reduced.
     */
    public function getTotalJamDiajukanFormattedAttribute()
    {
        return $this->total_jam_diajukan === null ? null : self::formatMinutes($this->total_jam_diajukan);
    }

    public static function formatMinutes($minutes)
    {
        $minutes = (int) $minutes;
        $jam = floor($minutes / 60);
        $menit = $minutes % 60;
        $result = [];
        if ($jam > 0) $result[] = $jam . ' jam';
        if ($menit > 0) $result[] = $menit . ' menit';
        if (empty($result)) return '0 menit';
        return implode(' ', $result);
    }
}
