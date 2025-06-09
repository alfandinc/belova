<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\Sop;
use App\Models\ERM\Tindakan;

class SOPSeeder extends Seeder
{
    public function run()
    {
        // Example for a facial treatment tindakan
        $tindakan = Tindakan::where('nama', 'Facial Treatment')->first();

        if ($tindakan) {
            Sop::create([
                'tindakan_id' => $tindakan->id,
                'nama_sop' => 'Persiapan',
                'deskripsi' => 'Menyiapkan alat dan bahan untuk facial treatment',
                'urutan' => 1
            ]);

            Sop::create([
                'tindakan_id' => $tindakan->id,
                'nama_sop' => 'Pembersihan Awal',
                'deskripsi' => 'Membersihkan wajah pasien dengan pembersih khusus',
                'urutan' => 2
            ]);

            Sop::create([
                'tindakan_id' => $tindakan->id,
                'nama_sop' => 'Eksfoliasi',
                'deskripsi' => 'Melakukan eksfoliasi untuk mengangkat sel kulit mati',
                'urutan' => 3
            ]);

            Sop::create([
                'tindakan_id' => $tindakan->id,
                'nama_sop' => 'Masker Wajah',
                'deskripsi' => 'Aplikasi masker sesuai dengan jenis kulit pasien',
                'urutan' => 4
            ]);

            Sop::create([
                'tindakan_id' => $tindakan->id,
                'nama_sop' => 'Finishing',
                'deskripsi' => 'Aplikasi pelembab dan sunscreen sesuai kebutuhan',
                'urutan' => 5
            ]);
        }
    }
}
