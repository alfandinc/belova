<?php
namespace App\Helpers;

class TerbilangHelper
{
    public static function terbilang($angka)
    {
        $angka = abs($angka);
        $bilangan = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $hasil = "";
        if ($angka < 12) {
            $hasil = " " . $bilangan[$angka];
        } elseif ($angka < 20) {
            $hasil = self::terbilang($angka - 10) . " belas";
        } elseif ($angka < 100) {
            $hasil = self::terbilang($angka / 10) . " puluh" . self::terbilang($angka % 10);
        } elseif ($angka < 200) {
            $hasil = " seratus" . self::terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $hasil = self::terbilang($angka / 100) . " ratus" . self::terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $hasil = " seribu" . self::terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil = self::terbilang($angka / 1000) . " ribu" . self::terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $hasil = self::terbilang($angka / 1000000) . " juta" . self::terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $hasil = self::terbilang($angka / 1000000000) . " milyar" . self::terbilang(fmod($angka,1000000000));
        } elseif ($angka < 1000000000000000) {
            $hasil = self::terbilang($angka / 1000000000000) . " triliun" . self::terbilang(fmod($angka,1000000000000));
        }
        return trim($hasil);
    }
}
