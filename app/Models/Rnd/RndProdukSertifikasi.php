<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Legalitas & sertifikasi of an R&D product (Notifikasi BPOM, Uji Cemaran, Halal, ...), each with a validity period.
 * A renewal is a new row; the row with the latest tanggal_selesai per jenis is the one that counts.
 */
class RndProdukSertifikasi extends Model
{
    protected $table = 'rnd_produk_sertifikasi';

    /** Sertifikat ending within this many days shows as "segera habis". */
    public const WARNING_DAYS = 90;

    /**
     * Jenis => label, short label (table chip), wajib untuk rilis obat baru, nomor wajib, per batch (sample), default penerbit.
     * Uji iritasi, uji klaim and kontrak maklon only apply to some products (a claim on the label, a maklon producer),
     * so they are recorded when relevant but do not block a release.
     */
    public const JENIS = [
        'notif_bpom' => ['label' => 'Notifikasi BPOM', 'short' => 'NA', 'wajib' => true, 'nomor_wajib' => true, 'per_batch' => false, 'penerbit' => 'BPOM'],
        'uji_cemar' => ['label' => 'Uji Cemaran (Mikroba & Logam Berat)', 'short' => 'Cemar', 'wajib' => true, 'nomor_wajib' => false, 'per_batch' => true, 'penerbit' => null],
        'halal' => ['label' => 'Sertifikat Halal', 'short' => 'Halal', 'wajib' => true, 'nomor_wajib' => true, 'per_batch' => false, 'penerbit' => 'BPJPH'],
        'dip' => ['label' => 'Dokumen Informasi Produk (DIP)', 'short' => 'DIP', 'wajib' => true, 'nomor_wajib' => false, 'per_batch' => false, 'penerbit' => null],
        'coa' => ['label' => 'Certificate of Analysis (COA)', 'short' => 'COA', 'wajib' => true, 'nomor_wajib' => false, 'per_batch' => true, 'penerbit' => null],
        'uji_stabilitas' => ['label' => 'Uji Stabilitas', 'short' => 'Stabilitas', 'wajib' => true, 'nomor_wajib' => false, 'per_batch' => false, 'penerbit' => null],
        'uji_iritasi' => ['label' => 'Uji Iritasi / Patch Test', 'short' => 'Iritasi', 'wajib' => false, 'nomor_wajib' => false, 'per_batch' => false, 'penerbit' => null],
        'uji_klaim' => ['label' => 'Uji Klaim (SPF, dll.)', 'short' => 'Klaim', 'wajib' => false, 'nomor_wajib' => false, 'per_batch' => false, 'penerbit' => null],
        'kontrak_maklon' => ['label' => 'Kontrak Maklon', 'short' => 'Kontrak', 'wajib' => false, 'nomor_wajib' => false, 'per_batch' => false, 'penerbit' => null],
    ];

    protected $fillable = [
        'produk_id',
        'jenis',
        'nomor',
        'penerbit',
        'tanggal_mulai',
        'tanggal_selesai',
        'sample_log_id',
        'doc_path',
        'original_name',
        'catatan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public static function wajibJenis(): array
    {
        return array_keys(array_filter(self::JENIS, fn ($jenis) => $jenis['wajib']));
    }

    public static function label(string $jenis): string
    {
        return self::JENIS[$jenis]['label'] ?? $jenis;
    }

    public function produk()
    {
        return $this->belongsTo(RndProduk::class, 'produk_id');
    }

    public function sampleLog()
    {
        return $this->belongsTo(RndSampleLog::class, 'sample_log_id');
    }

    /** berlaku | segera_habis | kedaluwarsa */
    public function masaBerlaku(?Carbon $today = null): string
    {
        $today ??= Carbon::today();

        if ($this->tanggal_selesai->lt($today)) {
            return 'kedaluwarsa';
        }

        return $this->tanggal_selesai->lte($today->copy()->addDays(self::WARNING_DAYS)) ? 'segera_habis' : 'berlaku';
    }

    /** Rows that are the current one of their jenis for the product (no row of the same jenis ends later). */
    public function scopeTerkini($query)
    {
        return $query->whereNotExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('rnd_produk_sertifikasi as newer')
                ->whereColumn('newer.produk_id', 'rnd_produk_sertifikasi.produk_id')
                ->whereColumn('newer.jenis', 'rnd_produk_sertifikasi.jenis')
                ->where(function ($q) {
                    $q->whereColumn('newer.tanggal_selesai', '>', 'rnd_produk_sertifikasi.tanggal_selesai')
                        ->orWhere(function ($same) {
                            $same->whereColumn('newer.tanggal_selesai', 'rnd_produk_sertifikasi.tanggal_selesai')
                                ->whereColumn('newer.id', '>', 'rnd_produk_sertifikasi.id');
                        });
                });
        });
    }

    /** Current sertifikasi that end within WARNING_DAYS or already ended, for products still in use. */
    public function scopePerluPerpanjangan($query)
    {
        return $query->terkini()
            ->whereDate('rnd_produk_sertifikasi.tanggal_selesai', '<=', Carbon::today()->addDays(self::WARNING_DAYS))
            ->whereHas('produk', fn ($p) => $p->where('status_produk', '!=', RndProduk::STATUS_DIBATALKAN));
    }
}
