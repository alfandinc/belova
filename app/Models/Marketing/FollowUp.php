<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $table = 'marketing_follow_ups';

    /** Kategori of the follow up logged when a birthday greeting is sent. */
    public const KATEGORI_ULANG_TAHUN = 'Ulang Tahun';

    /** Status respon: "Menunggu" = contacted, no reply yet. */
    public const STATUS_RESPON = ['Direspon', 'Tidak Direspon', 'Menunggu'];

    protected $fillable = [
        'pasien_id',
        'kategori',
        'sales_id',
        'status_respon',
        'bukti_respon',
        'rencana_tindak_lanjut',
        'status_booking',
        'catatan',
    ];

    public function pasien()
    {
        return $this->belongsTo(\App\Models\ERM\Pasien::class, 'pasien_id');
    }

    public function sales()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'sales_id')->withInactive();
    }
}
