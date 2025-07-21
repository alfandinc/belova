<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/inv-Barang.csv');

        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("CSV file not found or not readable at $path");
        }

        $file = fopen($path, 'r');
        $isHeader = true;

        while (($row = fgetcsv($file)) !== false) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }
            // Ubah 'NULL' string menjadi null
            $row = array_map(function ($value) {
                return strtoupper(trim($value)) === 'NULL' ? null : $value;
            }, $row);

            if (!DB::table('inv_ruangan')->where('id', $row[1])->exists()) {
                logger("Skipped: ruangan_id {$row[1]} not found");
                continue;
            }

            if (!DB::table('inv_tipe_barang')->where('id', $row[2])->exists()) {
                logger("Skipped: tipe_barang_id {$row[2]} not found");
                continue;
            }

            DB::table('inv_barang')->insert([
                'id'                        => $row[0],
                'ruangan_id'                 => $row[1],
                'tipe_barang_id'            => $row[2],
                'name'                   => $row[4],
                'kode'                   => $row[3],
                'satuan'                 => $row[5],
                'merk'                   => $row[6],
                'spec'                   => $row[7],
                // 'depreciation_rate'           => $row[8],
                'created_at'              => now(),
                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
