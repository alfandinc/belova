<?php

namespace App\Exports;

use App\Http\Controllers\RndProdukController;
use App\Models\Rnd\RndProduk;
use App\Models\Rnd\RndProdukSertifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Produk R&D list with the same filters as the table (tahap card, brand, search). */
class RndProdukExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private Carbon $today;

    public function __construct(private Request $request)
    {
        $this->today = Carbon::today();
    }

    public function collection()
    {
        $query = RndProduk::query()
            ->with(['brand', 'produsenVendor', 'zatAktifs', 'kemasanPremier', 'kemasanSekunder', 'latestSampleLog', 'sertifikasi', 'pic:id,name', 'obat:id,kode_obat'])
            ->select('rnd_produk.*')
            ->orderByDesc('rnd_produk.id');
        RndProdukController::applyTahapFilter($query, $this->request->input('tahap'));
        RndProdukController::applyListFilters($query, $this->request);

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'Produk', 'Status Produk', 'PIC', 'Target Rilis', 'Produsen', 'Zat Aktif', 'Sample Terakhir', 'Status Sample',
            'Kemasan Primer', 'Status Kemasan Primer', 'Status Desain Primer', 'Kemasan Sekunder', 'Status Kemasan Sekunder', 'Status Desain Sekunder',
            'FPP', 'SPK', 'Notif BPOM', 'Masa Simpan (bulan)', 'Target HPP', 'Target Harga Jual', 'Estimasi Biaya', 'Kode Obat', 'Siap Rilis',
        ];
        foreach (RndProdukSertifikasi::JENIS as $meta) {
            $headings[] = $meta['short'] . ' berlaku s/d';
        }

        return $headings;
    }

    /** @param RndProduk $produk */
    public function map($produk): array
    {
        $row = [
            RndProdukController::releaseNama($produk),
            $produk->status_produk,
            optional($produk->pic)->name,
            optional($produk->target_rilis)->format('Y-m-d'),
            optional($produk->produsenVendor)->nama_vendor,
            $produk->zatAktifs->pluck('nama')->implode(', '),
            optional($produk->latestSampleLog)->no_produksi,
            optional($produk->latestSampleLog)->status_sample,
            optional($produk->kemasanPremier)->nama_kemasan,
            $produk->status_kemasan_primer,
            $produk->status_desain_kemasan_primer,
            optional($produk->kemasanSekunder)->nama_kemasan,
            $produk->status_kemasan_sekunder,
            $produk->status_desain_kemasan_sekunder,
            $produk->status_administrasi_fpp,
            $produk->status_administrasi_spk,
            $produk->status_administrasi_notif,
            $produk->masa_simpan_bulan,
            $produk->target_hpp,
            $produk->target_harga_jual,
            $produk->estimasi_biaya,
            optional($produk->obat)->kode_obat,
            $produk->obat_id ? 'Dirilis' : (collect($produk->releaseChecklist())->every(fn ($item) => $item['ok']) ? 'Ya' : 'Belum'),
        ];

        foreach (array_keys(RndProdukSertifikasi::JENIS) as $jenis) {
            $current = $produk->sertifikasi->where('jenis', $jenis)->sortByDesc(fn ($s) => [$s->tanggal_selesai, $s->id])->first();
            $row[] = $current
                ? $current->tanggal_selesai->format('Y-m-d') . ($current->tanggal_selesai->lt($this->today) ? ' (kedaluwarsa)' : '')
                : '';
        }

        return $row;
    }
}
