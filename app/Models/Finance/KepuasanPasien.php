<?php

namespace App\Models\Finance;

use App\Models\ERM\Dokter;
use App\Models\ERM\Klinik;
use App\Models\ERM\Pasien;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Satisfaction survey (rating 1–5 + optional keluhan) given on the customer display; one per invoice. */
class KepuasanPasien extends Model
{
    protected $table = 'finance_kepuasan_pasien';

    protected $fillable = [
        'invoice_id', 'visitation_id', 'pasien_id', 'klinik_id', 'dokter_id',
        'layar_pelanggan_id', 'kasir_user_id', 'rating', 'keluhan',
    ];

    protected $casts = ['rating' => 'integer'];

    public const LABELS = [
        1 => 'Sangat Tidak Puas',
        2 => 'Tidak Puas',
        3 => 'Cukup',
        4 => 'Puas',
        5 => 'Sangat Puas',
    ];

    public const EMOJI = [1 => '😡', 2 => '🙁', 3 => '😐', 4 => '🙂', 5 => '😍'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function klinik()
    {
        return $this->belongsTo(Klinik::class, 'klinik_id');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_user_id');
    }
}
