<?php

namespace App\Models\ERM;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A proposed price set for one paket tindakan (erm_tindakan). Applied to the paket only after approval.
 */
class TindakanHargaPengajuan extends Model
{
    protected $table = 'erm_tindakan_harga_pengajuan';

    public const STATUS_LABEL = ObatHargaPengajuan::STATUS_LABEL;

    /** Price columns on erm_tindakan; each has a *_lama and *_baru column here */
    public const FIELDS = ['harga', 'harga_diskon', 'diskon_active', 'harga_paket_visit'];

    protected $fillable = [
        'tindakan_id',
        'harga_lama', 'harga_baru', 'harga_diskon_lama', 'harga_diskon_baru',
        'diskon_active_lama', 'diskon_active_baru', 'harga_paket_visit_lama', 'harga_paket_visit_baru',
        'biaya_bahan', 'alasan', 'status',
        'diajukan_by', 'disetujui_by', 'disetujui_at', 'ditolak_by', 'ditolak_at', 'alasan_tolak', 'dibatalkan_at',
    ];

    protected $casts = [
        'harga_lama' => 'float',
        'harga_baru' => 'float',
        'harga_diskon_lama' => 'float',
        'harga_diskon_baru' => 'float',
        'diskon_active_lama' => 'boolean',
        'diskon_active_baru' => 'boolean',
        'harga_paket_visit_lama' => 'float',
        'harga_paket_visit_baru' => 'float',
        'biaya_bahan' => 'float',
        'disetujui_at' => 'datetime',
        'ditolak_at' => 'datetime',
        'dibatalkan_at' => 'datetime',
    ];

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id');
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
