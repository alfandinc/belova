<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IcPendaftaran extends Model
{
    use HasFactory;

    protected $table = 'erm_ic_pendaftarans';

    protected $fillable = [
        'pasien_id',
        'pdf_path',
        'signature_path',
        'signed_at',
        'created_by'
    ];
}
