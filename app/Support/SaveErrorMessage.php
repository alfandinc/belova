<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Throwable;

/**
 * Turns an exception thrown while saving a form into a message the user can act on (shown in the error Swal),
 * e.g. which field is too long. The raw error stays in the log.
 */
class SaveErrorMessage
{
    /** Column names that don't read well when humanized */
    private const LABELS = [
        'anamnesis1' => 'Anamnesis', 'anamnesis2' => 'Anamnesis (lanjutan)',
        'ext_atas' => 'Ekstremitas atas', 'ext_bawah' => 'Ekstremitas bawah', 'hsl' => 'Hasil',
        'ket_status_lokalis' => 'Keterangan status lokalis', 'dpip' => 'DPJP Ranap', 'rtl' => 'Rencana tindak lanjut',
        'standing_order' => 'Rencana asuhan / standing order', 'homecare' => 'Kontrol klinik / homecare',
        'allo_dengan' => 'Alloanamnesis dengan', 'hasil_allo' => 'Hasil alloanamnesis',
        'td' => 'Tekanan darah', 'rr' => 'Respirasi (RR)', 'tb' => 'Tinggi badan', 'bb' => 'Berat badan',
        'lla' => 'Lingkar lengan atas', 'imt' => 'IMT', 'lk' => 'Lingkar kepala',
    ];

    public static function from(Throwable $e): string
    {
        if (!$e instanceof QueryException) {
            return 'Gagal menyimpan: ' . $e->getMessage();
        }

        $code = (int) ($e->errorInfo[1] ?? 0);
        $raw = $e->errorInfo[2] ?? $e->getMessage();
        $field = preg_match("/column '([^']+)'/i", $raw, $m) ? self::label($m[1]) : null;

        return match (true) {
            $code === 1406 && $field !== null => "Isian \"{$field}\" terlalu panjang. Persingkat teksnya lalu simpan lagi.",
            in_array($code, [1366, 1292], true) && $field !== null => "Format isian \"{$field}\" tidak sesuai (mis. huruf di kolom angka / tanggal).",
            $code === 1264 && $field !== null => "Nilai isian \"{$field}\" di luar batas yang diizinkan.",
            $code === 1048 && $field !== null => "Isian \"{$field}\" wajib diisi.",
            $code === 1452 => 'Data terkait tidak ditemukan (mis. kunjungan / pasien sudah dihapus). Muat ulang halaman lalu coba lagi.',
            in_array($code, [1205, 1213], true) => 'Data sedang disimpan oleh pengguna lain. Tunggu sebentar lalu simpan lagi.',
            in_array($code, [2002, 2006, 2013], true) => 'Koneksi ke database terputus. Coba simpan lagi.',
            default => 'Gagal menyimpan ke database: ' . $raw,
        };
    }

    private static function label(string $column): string
    {
        return self::LABELS[$column] ?? ucfirst(str_replace('_', ' ', preg_replace('/_?(\d+)$/', ' $1', $column)));
    }
}
