<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\Tindakan;

class TindakanSeeder extends Seeder
{
    public function run(): void
    {
        $tindakans = [
            ['nama' => 'Facial Treatment', 'harga' => 150000, 'deskripsi' => 'Perawatan wajah dasar untuk membersihkan dan menyegarkan kulit.', 'spesialis_id' => 6],
            ['nama' => 'Laser Hair Removal', 'harga' => 400000, 'deskripsi' => 'Penghilangan bulu dengan teknologi laser untuk hasil permanen.', 'spesialis_id' => 6],
            ['nama' => 'Botox Injection', 'harga' => 600000, 'deskripsi' => 'Injeksi botox untuk mengurangi kerutan dan garis halus pada wajah.', 'spesialis_id' => 6],
            ['nama' => 'Filler Treatment', 'harga' => 700000, 'deskripsi' => 'Pengisian volume pada area wajah yang kehilangan elastisitas.', 'spesialis_id' => 6],
            ['nama' => 'Chemical Peeling', 'harga' => 250000, 'deskripsi' => 'Pengelupasan kulit menggunakan bahan kimia untuk regenerasi sel kulit baru.', 'spesialis_id' => 6],
            ['nama' => 'Laser Rejuvenation', 'harga' => 500000, 'deskripsi' => 'Terapi laser untuk meremajakan kulit dan mengurangi kerutan.', 'spesialis_id' => 6],
            ['nama' => 'Microneedling', 'harga' => 300000, 'deskripsi' => 'Teknik perawatan kulit menggunakan jarum mikro untuk merangsang kolagen.', 'spesialis_id' => 6],
            ['nama' => 'Masker Collagen', 'harga' => 100000, 'deskripsi' => 'Masker untuk melembapkan dan mengencangkan kulit wajah.', 'spesialis_id' => 6],
        ];

        foreach ($tindakans as $data) {
            Tindakan::create($data);
        }
    }
}
