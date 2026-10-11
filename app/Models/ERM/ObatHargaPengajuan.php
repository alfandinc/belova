<?php

namespace App\Models\ERM;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A proposed harga jual for one obat. Applied to erm_obat.harga_nonfornas only after approval.
 */
class ObatHargaPengajuan extends Model
{
    protected $table = 'erm_obat_harga_pengajuan';

    public const STATUS_LABEL = [
        'menunggu' => 'Menunggu approval',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];

    protected $fillable = [
        'obat_id', 'harga_lama', 'harga_baru', 'hpp', 'hna', 'margin_lama', 'margin_baru', 'alasan', 'status',
        'diajukan_by', 'disetujui_by', 'disetujui_at', 'ditolak_by', 'ditolak_at', 'alasan_tolak', 'dibatalkan_at',
    ];

    protected $casts = [
        'harga_lama' => 'float',
        'harga_baru' => 'float',
        'hpp' => 'float',
        'hna' => 'float',
        'margin_lama' => 'float',
        'margin_baru' => 'float',
        'disetujui_at' => 'datetime',
        'ditolak_at' => 'datetime',
        'dibatalkan_at' => 'datetime',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id')->withoutGlobalScope('active');
    }

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'diajukan_by');
    }

    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'disetujui_by');
    }

    public function penolak()
    {
        return $this->belongsTo(User::class, 'ditolak_by');
    }
}
