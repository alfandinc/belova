<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SopTindakanSeeder extends Seeder
{
    public function run(): void
    {
        // === 1. Seed ke erm_tindakan dengan id, nama, harga, spesialis_id ===
        $tindakanPath = database_path('data/tindakan_baru.csv');
        $tindakanRows = array_map('str_getcsv', file($tindakanPath));
        $tindakanHeader = array_map('trim', array_shift($tindakanRows));

        $tindakanMap = [];

        foreach ($tindakanRows as $row) {
            $data = array_combine($tindakanHeader, $row);

            $id = (int) $data['id'];
            $nama = trim($data['nama']);

            // ✅ Perbaikan: parsing harga Indonesia "95.000,00" menjadi 95000
            $harga = (int) str_replace(['.', ',00'], '', $data['harga']);

            $spesialisId = !empty($data['spesialis_id']) ? (int) $data['spesialis_id'] : null;

            // Insert/update tindakan
            DB::table('erm_tindakan')->updateOrInsert(
                ['id' => $id],
                [
                    'nama' => $nama,
                    'harga' => $harga,
                    'spesialis_id' => $spesialisId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            $tindakanMap[strtolower($nama)] = $id;
        }

        echo "✅ tindakan (id, nama, harga, spesialis_id) berhasil dimasukkan.\n";

        // === 2. Seed ke erm_sop berdasarkan nama_tindakan ===
        $sopPath = database_path('data/sop_tindakan.csv');
        if (!file_exists($sopPath)) {
            echo "⚠️ File sop_tindakan.csv tidak ditemukan. Melewati bagian SOP.\n";
            return;
        }

        $sopRows = array_map('str_getcsv', array_map(function ($line) {
            return preg_replace('/^\xEF\xBB\xBF/', '', $line); // remove BOM
        }, file($sopPath)));

        $sopHeader = array_map('trim', array_shift($sopRows));

        $countSop = 0;
        foreach ($sopRows as $row) {
            $data = array_combine($sopHeader, $row);
            $namaTindakan = strtolower(trim($data['nama_tindakan']));
            $namaSop = trim($data['nama_sop']);

            if (!isset($tindakanMap[$namaTindakan])) {
                echo "‼️ Tindakan tidak ditemukan: {$data['nama_tindakan']}\n";
                continue;
            }

            DB::table('erm_sop')->insert([
                'tindakan_id' => $tindakanMap[$namaTindakan],
                'nama_sop' => $namaSop,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $countSop++;
        }

        echo "✅ {$countSop} SOP tindakan berhasil dimasukkan ke erm_sop.\n";
    }
}
