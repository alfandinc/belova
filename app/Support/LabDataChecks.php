<?php

namespace App\Support;

use App\Models\ERM\LabKategori;
use App\Models\ERM\LabPaket;
use App\Models\ERM\LabTest;

/**
 * "Data yang perlu action" on Master Lab: suspicious lab tests, paket and kategori. Shown as cards that filter
 * the table of their tab; nothing is changed automatically.
 * key => [label, hint, tab, filter]; the key prefix (test_, paket_, kategori_) is the tab.
 */
class LabDataChecks
{
    /** A lab test usually costs tens to hundreds of thousands; outside this range it is likely a typo or test data */
    public const HARGA_MIN = 1000;
    public const HARGA_MAX = 1000000;

    public static function checks(): array
    {
        $min = self::HARGA_MIN;
        $max = self::HARGA_MAX;

        return [
            'test_nama_uji' => ['Lab test seperti data uji', 'nama/kategori "TEST …", coba, dummy, asd; ganti atau tandai tidak tersedia', 'test',
                fn ($q) => $q->where(fn ($w) => self::namaUji($w, 'erm_lab_test.nama')
                    ->orWhereHas('labKategori', fn ($k) => self::namaUji($k, 'erm_lab_kategori.nama')))],
            'test_harga' => ['Harga lab test tidak wajar', 'kosong, di bawah Rp 1.000, atau Rp 1 juta ke atas', 'test',
                fn ($q) => $q->where(fn ($w) => $w->whereNull('erm_lab_test.harga')->orWhere('erm_lab_test.harga', '<', $min)->orWhere('erm_lab_test.harga', '>=', $max))],
            'test_off_di_paket' => ['Test tidak tersedia tapi ada di paket', 'paket tetap bisa diminta; keluarkan dari paket atau tersediakan lagi', 'test',
                fn ($q) => $q->where('erm_lab_test.available', 'no')->has('labPakets')],
            'paket_nama_uji' => ['Paket lab seperti data uji', 'nama "TEST …", coba, dummy, asd, atau hanya angka', 'paket',
                fn ($q) => $q->where(fn ($w) => self::namaUji($w, 'erm_lab_paket.nama'))],
            'paket_harga' => ['Harga paket lab tidak wajar', 'di bawah Rp 1.000 atau lebih mahal dari total harga satuan', 'paket',
                fn ($q) => $q->where(fn ($w) => $w->where('erm_lab_paket.harga_paket', '<', $min)
                    ->orWhereRaw('erm_lab_paket.harga_paket > (SELECT COALESCE(SUM(t.harga), 0) FROM erm_lab_paket_detail d JOIN erm_lab_test t ON t.id = d.lab_test_id WHERE d.lab_paket_id = erm_lab_paket.id)'))],
            'kategori_nama_uji' => ['Kategori lab seperti data uji', 'nama "TEST …", coba, dummy, asd; pindahkan test-nya lalu hapus', 'kategori',
                fn ($q) => $q->where(fn ($w) => self::namaUji($w, 'erm_lab_kategori.nama'))],
        ];
    }

    /**
     * Names that look like test input: starting with "test" (not "PP TEST" / "COOMBS TEST"), coba, dummy,
     * keyboard mashing (asd…, qwe…) or only digits.
     */
    public static function namaUji($q, string $column)
    {
        $col = "LOWER(TRIM({$column}))";

        return $q->where(fn ($w) => $w->whereRaw("{$col} = 'test'")
            ->orWhereRaw("{$col} LIKE 'test %'")
            ->orWhereRaw("{$col} LIKE '%coba%'")
            ->orWhereRaw("{$col} LIKE '%dummy%'")
            ->orWhereRaw("{$col} LIKE 'asd%'")
            ->orWhereRaw("{$col} LIKE 'qwe%'")
            ->orWhereRaw("{$col} REGEXP '^[0-9 .,-]+$'"));
    }

    /** Apply the card's filter when it belongs to this tab */
    public static function apply($query, ?string $key, string $tab): void
    {
        $check = self::checks()[$key] ?? null;
        if ($check && $check[2] === $tab) {
            $check[3]($query);
        }
    }

    /** key => [label, hint, count] for the cards */
    public static function counts(): array
    {
        $base = ['test' => fn () => LabTest::query(), 'paket' => fn () => LabPaket::query(), 'kategori' => fn () => LabKategori::query()];
        $out = [];
        foreach (self::checks() as $key => [$label, $hint, $tab, $filter]) {
            $out[$key] = ['label' => $label, 'hint' => $hint, 'count' => $filter($base[$tab]())->count()];
        }

        return $out;
    }
}
