<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Visitation extends Model
{

    
    protected $table = 'erm_visitations';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
        'pasien_id',
        'metode_bayar_id',
        'dokter_id',
        'user_id',
        'klinik_id',
        'status_kunjungan',
        'status_dokumen',
        'jenis_kunjungan',
        'tanggal_visitation',
        'waktu_kunjungan', // add this line
        'no_antrian',

    ];

        public function riwayatTindakanObats()
    {
        return $this->belongsToMany(
            Obat::class,
            'erm_riwayat_tindakan_obat',
            'riwayat_tindakan_id',
            'obat_id'
        )->withPivot('kode_tindakan_id', 'qty', 'dosis', 'satuan_dosis')->withTimestamps();
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function metodeBayar()
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }
    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }
    public function asesmenPerawat()
    {
        return $this->hasOne(AsesmenPerawat::class);
    }

    public function asesmenDalam()
    {
        return $this->hasOne(AsesmenDalam::class);
    }
    public function asesmenEstetika()
    {
        return $this->hasOne(AsesmenEstetika::class);
    }
    public function asesmenSaraf()
    {
        return $this->hasOne(AsesmenSaraf::class);
    }
    public function asesmenAnak()
    {
        return $this->hasOne(AsesmenAnak::class);
    }
    public function asesmenGigi()
    {
        return $this->hasOne(AsesmenGigi::class);
    }

    public function asesmenPenunjang()
    {
        return $this->hasOne(AsesmenPenunjang::class);
    }

    public function asesmenUmum()
    {
        return $this->hasOne(AsesmenUmum::class);
    }
    public function resepDokter()
    {
        return $this->hasMany(ResepDokter::class);
    }
    public function resepFarmasi()
    {
        return $this->hasMany(ResepFarmasi::class);
    }

    public function klinik()
    {
        return $this->belongsTo(Klinik::class, 'klinik_id');
    }
    public function cppt()
    {
        return $this->hasOne(Cppt::class, 'visitation_id');
    }

    public function labPermintaan()
    {
        return $this->hasMany(LabPermintaan::class, 'visitation_id');
    }

    public function riwayatTindakan()
    {
        return $this->hasMany(RiwayatTindakan::class, 'visitation_id');
    }

    public function invoice()
    {
        return $this->hasOne(\App\Models\Finance\Invoice::class, 'visitation_id');
    }

    public function billings()
    {
        return $this->hasMany(\App\Models\Finance\Billing::class, 'visitation_id');
    }
    
    public function suratDiagnosa()
    {
        return $this->hasOne(SuratDiagnosa::class, 'visitation_id');
    }

    public function screeningBatuk()
    {
        return $this->hasOne(ScreeningBatuk::class, 'visitation_id');
    }
}
