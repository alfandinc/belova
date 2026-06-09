<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Rnd\RndNotif;

class RndProduk extends Model
{
    use HasFactory;

    protected $table = 'rnd_produk';

    protected $fillable = [
        'nama_produk',
        'brand_id',
        'produsen_vendor_id',
        'kemasan_premier_id',
        'kemasan_sekunder_id',
        'kemasan_primer_vendor_id',
        'kemasan_sekunder_vendor_id',
        'desain_kemasan_primer_id',
        'desain_kemasan_sekunder_id',
        'sediaan_id',
        'netto',
        'status_administrasi_fpp',
        'status_administrasi_spk',
        'status_administrasi_notif',
        'status_kemasan_primer',
        'status_kemasan_sekunder',
        'status_desain_kemasan_primer',
        'status_desain_kemasan_sekunder',
    ];

    public function brand()
    {
        return $this->belongsTo(RndMasterBrand::class, 'brand_id');
    }

    public function produsenVendor()
    {
        return $this->belongsTo(RndMasterVendor::class, 'produsen_vendor_id');
    }

    public function kemasanPremier()
    {
        return $this->belongsTo(RndMasterKemasan::class, 'kemasan_premier_id');
    }

    public function kemasanSekunder()
    {
        return $this->belongsTo(RndMasterKemasan::class, 'kemasan_sekunder_id');
    }

    public function kemasanPrimerVendor()
    {
        return $this->belongsTo(RndMasterVendor::class, 'kemasan_primer_vendor_id');
    }

    public function kemasanSekunderVendor()
    {
        return $this->belongsTo(RndMasterVendor::class, 'kemasan_sekunder_vendor_id');
    }

    public function desainKemasanPrimerVendor()
    {
        return $this->belongsTo(RndMasterVendor::class, 'desain_kemasan_primer_id');
    }

    public function desainKemasanSekunderVendor()
    {
        return $this->belongsTo(RndMasterVendor::class, 'desain_kemasan_sekunder_id');
    }

    public function sediaan()
    {
        return $this->belongsTo(RndMasterSediaan::class, 'sediaan_id');
    }

    public function bahanAktif()
    {
        return $this->belongsToMany(
            RndMasterBahanAktif::class,
            'rnd_produk_bahan_aktif',
            'produk_id',
            'bahan_aktif_id'
        );
    }

    public function sampleLogs()
    {
        return $this->hasMany(RndSampleLog::class, 'produk_id');
    }

    public function latestSampleLog()
    {
        return $this->hasOne(RndSampleLog::class, 'produk_id')->latestOfMany();
    }

    public function productLogs()
    {
        return $this->hasMany(RndProdukLog::class, 'produk_id');
    }

    public function notifs()
    {
        return $this->hasMany(RndNotif::class, 'produk_id');
    }

    public function latestNotif()
    {
        return $this->hasOne(RndNotif::class, 'produk_id')->latestOfMany();
    }
}