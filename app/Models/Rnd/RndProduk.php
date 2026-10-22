<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RndProduk extends Model
{
    use HasFactory;

    protected $table = 'rnd_produk';

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_DITUNDA = 'ditunda';
    public const STATUS_DIBATALKAN = 'dibatalkan';
    public const STATUS_PRODUK = [self::STATUS_AKTIF, self::STATUS_DITUNDA, self::STATUS_DIBATALKAN];

    protected $fillable = [
        'obat_id',
        'nama_produk',
        'status_produk',
        'pic_user_id',
        'target_rilis',
        'netto_satuan',
        'masa_simpan_bulan',
        'target_hpp',
        'target_harga_jual',
        'estimasi_biaya',
        'brand_id',
        'produsen_vendor_id',
        'kemasan_premier_id',
        'kemasan_sekunder_id',
        'kemasan_primer_vendor_id',
        'kemasan_sekunder_vendor_id',
        'desain_kemasan_primer_id',
        'desain_kemasan_sekunder_id',
        'sediaan',
        'netto',
        'status_administrasi_fpp',
        'status_administrasi_spk',
        'status_administrasi_notif',
        'status_kemasan_primer',
        'status_kemasan_sekunder',
        'status_desain_kemasan_primer',
        'status_desain_kemasan_sekunder',
    ];

    protected $casts = [
        'target_rilis' => 'date:Y-m-d',
        'masa_simpan_bulan' => 'integer',
        'target_hpp' => 'float',
        'target_harga_jual' => 'float',
        'estimasi_biaya' => 'float',
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

    /** Master Obat record this product was released as (also when that obat is inactive). */
    public function obat()
    {
        return $this->belongsTo(\App\Models\ERM\Obat::class, 'obat_id')->withoutGlobalScope('active');
    }

    /** Active ingredients come from the shared Zat Aktif master (the former R&D Bahan Aktif master was merged into it). */
    public function zatAktifs()
    {
        return $this->belongsToMany(
            \App\Models\ERM\ZatAktif::class,
            'rnd_produk_zat_aktif',
            'produk_id',
            'zataktif_id'
        )->withTimestamps();
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

    public function pic()
    {
        return $this->belongsTo(\App\Models\User::class, 'pic_user_id');
    }

    public function sertifikasi()
    {
        return $this->hasMany(RndProdukSertifikasi::class, 'produk_id');
    }

    /** Packaging design jobs requested to the graphic designer (Request Desain). */
    public function designRequests()
    {
        return $this->hasMany(\App\Models\Marketing\DesignRequest::class, 'rnd_produk_id');
    }

    /** Notifikasi BPOM that is valid longest (the current one). */
    public function latestNotif()
    {
        return $this->hasOne(RndProdukSertifikasi::class, 'produk_id')
            ->ofMany(['tanggal_selesai' => 'max', 'id' => 'max'], fn ($q) => $q->where('jenis', 'notif_bpom'));
    }

    /**
     * What must be done before the product can be released as a NEW obat. Linking to an existing obat
     * (older R&D products already sold) skips this list.
     *
     * @return array<int, array{key: string, label: string, ok: bool, detail: string}>
     */
    public function releaseChecklist(): array
    {
        $this->loadMissing(['latestSampleLog', 'sertifikasi']);
        $today = Carbon::today();
        $done = fn ($value) => $value === 'done';

        $items = [
            ['key' => 'status_produk', 'label' => 'Status produk aktif', 'ok' => $this->status_produk === self::STATUS_AKTIF, 'detail' => ucfirst((string) $this->status_produk)],
            ['key' => 'sample', 'label' => 'Sample terakhir done', 'ok' => $done(optional($this->latestSampleLog)->status_sample),
                'detail' => $this->latestSampleLog ? ($this->latestSampleLog->no_produksi . ': ' . ($this->latestSampleLog->status_sample ?: 'belum')) : 'belum ada sample'],
            ['key' => 'kemasan_primer', 'label' => 'Kemasan & desain primer done', 'ok' => $done($this->status_kemasan_primer) && $done($this->status_desain_kemasan_primer),
                'detail' => 'kemasan ' . ($this->status_kemasan_primer ?: 'belum') . ', desain ' . ($this->status_desain_kemasan_primer ?: 'belum')],
        ];

        if ($this->kemasan_sekunder_id) {
            $items[] = ['key' => 'kemasan_sekunder', 'label' => 'Kemasan & desain sekunder done', 'ok' => $done($this->status_kemasan_sekunder) && $done($this->status_desain_kemasan_sekunder),
                'detail' => 'kemasan ' . ($this->status_kemasan_sekunder ?: 'belum') . ', desain ' . ($this->status_desain_kemasan_sekunder ?: 'belum')];
        }

        foreach (['fpp' => 'FPP', 'spk' => 'SPK', 'notif' => 'Notifikasi BPOM'] as $key => $label) {
            $value = $this->{'status_administrasi_' . $key};
            $items[] = ['key' => 'administrasi_' . $key, 'label' => "Administrasi {$label} done", 'ok' => $done($value), 'detail' => $value ?: 'belum'];
        }

        foreach (RndProdukSertifikasi::wajibJenis() as $jenis) {
            $current = $this->sertifikasi->where('jenis', $jenis)->sortByDesc(fn ($s) => [$s->tanggal_selesai, $s->id])->first();
            $items[] = [
                'key' => 'sertifikasi_' . $jenis,
                'label' => RndProdukSertifikasi::label($jenis) . ' masih berlaku',
                'ok' => $current !== null && $current->tanggal_selesai->gte($today),
                'detail' => $current ? 'berlaku s/d ' . $current->tanggal_selesai->translatedFormat('d M Y') : 'belum ada',
            ];
        }

        return $items;
    }

    /** Same rules as releaseChecklist(), as a query (for the "Siap rilis" filter and counts). */
    public function scopeSiapRilis($query)
    {
        $today = Carbon::today()->toDateString();
        $query->whereNull('rnd_produk.obat_id')
            ->where('rnd_produk.status_produk', self::STATUS_AKTIF)
            ->where('rnd_produk.status_kemasan_primer', 'done')
            ->where('rnd_produk.status_desain_kemasan_primer', 'done')
            ->where(fn ($q) => $q->whereNull('rnd_produk.kemasan_sekunder_id')
                ->orWhere(fn ($s) => $s->where('rnd_produk.status_kemasan_sekunder', 'done')->where('rnd_produk.status_desain_kemasan_sekunder', 'done')))
            ->where('rnd_produk.status_administrasi_fpp', 'done')
            ->where('rnd_produk.status_administrasi_spk', 'done')
            ->where('rnd_produk.status_administrasi_notif', 'done')
            ->whereRaw("(select s.status_sample from rnd_sample_log s where s.produk_id = rnd_produk.id order by s.id desc limit 1) = 'done'");

        foreach (RndProdukSertifikasi::wajibJenis() as $jenis) {
            $query->whereHas('sertifikasi', fn ($s) => $s->where('jenis', $jenis)->whereDate('tanggal_selesai', '>=', $today));
        }

        return $query;
    }

    public function additionalDocuments()
    {
        return $this->hasMany(\App\Models\Rnd\RndProdukDocument::class, 'produk_id');
    }

    public function timelines()
    {
        return $this->hasMany('App\\Models\\Rnd\\RndProdukTimeline', 'produk_id');
    }
}