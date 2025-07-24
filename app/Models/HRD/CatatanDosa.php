<?php
namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatatanDosa extends Model
{
    use HasFactory;

    protected $table = 'hrd_catatan_dosa';

    protected $fillable = [
        'employee_id',
        'jenis_pelanggaran',
        'kategori',
        'deskripsi',
        'bukti',
        'status_tindaklanjut',
        'tindakan',
        'timestamp',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
