<?php

namespace App\Models\ERM;

use App\Models\HRD\Employee;
use Illuminate\Database\Eloquent\Model;

/**
 * Who did one tindakan (kode tindakan) of a riwayat tindakan, with the jasa medis earned. tarif is the
 * snapshot of the tipe's tarif when first saved; nominal = tarif / jumlah_petugas of that tindakan + tipe.
 */
class SpkPetugas extends Model
{
    public const TIPE_DOKTER = 'dokter';
    public const TIPE_EMPLOYEE = 'employee';

    protected $table = 'erm_spk_petugas';

    protected $fillable = [
        'riwayat_tindakan_id', 'kode_tindakan_id', 'tipe', 'dokter_id', 'employee_id',
        'tarif', 'jumlah_petugas', 'nominal', 'created_by',
    ];

    protected $casts = [
        'tarif' => 'float',
        'nominal' => 'float',
        'jumlah_petugas' => 'integer',
    ];

    public function riwayatTindakan()
    {
        return $this->belongsTo(RiwayatTindakan::class, 'riwayat_tindakan_id');
    }

    public function kodeTindakan()
    {
        return $this->belongsTo(KodeTindakan::class, 'kode_tindakan_id');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withInactive();
    }

    /** Petugas key used by the SPK form: "dokter:5" / "employee:12" */
    public function petugasKey(): string
    {
        return $this->tipe . ':' . ($this->tipe === self::TIPE_DOKTER ? $this->dokter_id : $this->employee_id);
    }
}
