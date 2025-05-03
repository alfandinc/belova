<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PasienSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert 10 specific data into the pasien table
        DB::table('erm_pasiens')->insert([
            ['id' => 100001, 'nik' => '3276010101010001', 'nama' => 'John Doe', 'tanggal_lahir' => '1990-05-15', 'gender' => 'Laki-laki', 'agama' => 'Islam', 'marital_status' => 'Belum Menikah', 'pendidikan' => 'S1', 'pekerjaan' => 'Programmer', 'gol_darah' => 'O', 'notes' => 'Pasien dengan kondisi stabil', 'alamat' => 'Jl. Raya No. 123, Jakarta', 'no_hp' => '6281234567890', 'email' => 'john.doe@example.com', 'instagram' => '@john_doe'],
            ['id' => 100002, 'nik' => '3276010101010002', 'nama' => 'Jane Smith', 'tanggal_lahir' => '1992-07-22', 'gender' => 'Perempuan', 'agama' => 'Islam', 'marital_status' => 'Belum Menikah', 'pendidikan' => 'S2', 'pekerjaan' => 'Desainer Grafis', 'gol_darah' => 'A', 'notes' => 'Pasien dengan riwayat alergi', 'alamat' => 'Jl. Merdeka No. 456, Bandung', 'no_hp' => '6289876543210', 'email' => 'jane.smith@example.com', 'instagram' => '@jane_smith'],
            ['id' => 100003, 'nik' => '3276010101010003', 'nama' => 'Albert Einstein', 'tanggal_lahir' => '1985-03-14', 'gender' => 'Laki-laki', 'agama' => 'Islam', 'marital_status' => 'Menikah', 'pendidikan' => 'S3', 'pekerjaan' => 'Fisikawan', 'gol_darah' => 'B', 'notes' => 'Pasien dalam perawatan rutin', 'alamat' => 'Jl. Ilmu No. 789, Surabaya', 'no_hp' => '6281555555555', 'email' => 'albert.einstein@example.com', 'instagram' => '@alberteinstein'],
            ['id' => 100004, 'nik' => '3276010101010004', 'nama' => 'Maria Curie', 'tanggal_lahir' => '1988-11-07', 'gender' => 'Perempuan', 'agama' => 'Islam', 'marital_status' => 'Menikah', 'pendidikan' => 'S2', 'pekerjaan' => 'Ahli Kimia', 'gol_darah' => 'AB', 'notes' => 'Pasien dengan riwayat penyakit jantung', 'alamat' => 'Jl. Kimia No. 101, Yogyakarta', 'no_hp' => '6282000000000', 'email' => 'maria.curie@example.com', 'instagram' => '@mariacurie'],
            ['id' => 100005, 'nik' => '3276010101010005', 'nama' => 'Siti Nurhaliza', 'tanggal_lahir' => '1995-02-20', 'gender' => 'Perempuan', 'agama' => 'Islam', 'marital_status' => 'Menikah', 'pendidikan' => 'S1', 'pekerjaan' => 'Penyanyi', 'gol_darah' => 'O', 'notes' => 'Pasien dengan alergi makanan', 'alamat' => 'Jl. Musik No. 202, Makassar', 'no_hp' => '6283112233445', 'email' => 'siti.nurhaliza@example.com', 'instagram' => '@sitinurhaliza'],
            ['id' => 100006, 'nik' => '3276010101010006', 'nama' => 'Budi Santoso', 'tanggal_lahir' => '1983-08-30', 'gender' => 'Laki-laki', 'agama' => 'Islam', 'marital_status' => 'Menikah', 'pendidikan' => 'S1', 'pekerjaan' => 'Wiraswasta', 'gol_darah' => 'A', 'notes' => 'Pasien sehat, pemeriksaan rutin', 'alamat' => 'Jl. Raya No. 321, Bali', 'no_hp' => '6282123456789', 'email' => 'budi.santoso@example.com', 'instagram' => '@budi_santoso'],
            ['id' => 100007, 'nik' => '3276010101010007', 'nama' => 'Lina Marpaung', 'tanggal_lahir' => '1994-06-05', 'gender' => 'Perempuan', 'agama' => 'Islam', 'marital_status' => 'Belum Menikah', 'pendidikan' => 'S1', 'pekerjaan' => 'Admin', 'gol_darah' => 'B', 'notes' => 'Pasien dalam kondisi sehat', 'alamat' => 'Jl. Admin No. 444, Medan', 'no_hp' => '6283155557777', 'email' => 'lina.marpaung@example.com', 'instagram' => '@lina_marpaung'],
            ['id' => 100008, 'nik' => '3276010101010008', 'nama' => 'Rudi Hartono', 'tanggal_lahir' => '1986-12-10', 'gender' => 'Laki-laki', 'agama' => 'Islam', 'marital_status' => 'Menikah', 'pendidikan' => 'S2', 'pekerjaan' => 'Dokter', 'gol_darah' => 'O', 'notes' => 'Pasien dengan hipertensi', 'alamat' => 'Jl. Sehat No. 555, Surabaya', 'no_hp' => '6283223344556', 'email' => 'rudi.hartono@example.com', 'instagram' => '@rudi_hartono'],
            ['id' => 100009, 'nik' => '3276010101010009', 'nama' => 'Dewi Lestari', 'tanggal_lahir' => '1991-09-12', 'gender' => 'Perempuan', 'agama' => 'Islam', 'marital_status' => 'Belum Menikah', 'pendidikan' => 'S1', 'pekerjaan' => 'Penulis', 'gol_darah' => 'AB', 'notes' => 'Pasien sehat, pemeriksaan berkala', 'alamat' => 'Jl. Literasi No. 212, Semarang', 'no_hp' => '6283334445566', 'email' => 'dewi.lestari@example.com', 'instagram' => '@dewi_lestari'],
            ['id' => 100010, 'nik' => '3276010101010010', 'nama' => 'Ali Akbar', 'tanggal_lahir' => '1987-01-15', 'gender' => 'Laki-laki', 'agama' => 'Islam', 'marital_status' => 'Menikah', 'pendidikan' => 'S1', 'pekerjaan' => 'Arsitek', 'gol_darah' => 'A', 'notes' => 'Pasien rutin cek-up', 'alamat' => 'Jl. Arsitek No. 808, Jakarta', 'no_hp' => '6284445556667', 'email' => 'ali.akbar@example.com', 'instagram' => '@ali_akbar']
        ]);
    }
}
