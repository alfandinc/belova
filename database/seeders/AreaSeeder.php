<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    public function run()
    {
        // Matikan foreign key checks agar tidak error saat hapus data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Kosongkan tabel dengan aman
        DB::table('area_villages')->delete();
        DB::table('area_districts')->delete();
        DB::table('area_regencies')->delete();
        DB::table('area_provinces')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // === Insert Provinces ===
        $provinces = array_map('str_getcsv', file(database_path('data/provinces.csv')));
        $seen = [];
        $skipped = 0;
        foreach ($provinces as $row) {
            $id = $row[0];
            if (!isset($seen[$id])) {
                DB::table('area_provinces')->insert([
                    'id' => $id,
                    'name' => $row[1],
                ]);
                $seen[$id] = true;
            } else {
                $skipped++;
            }
        }
        echo "Provinces skipped (duplicate): $skipped\n";

        // === Insert Regencies ===
        $regencies = array_map('str_getcsv', file(database_path('data/regencies.csv')));
        $seen = [];
        $skipped = 0;
        foreach ($regencies as $row) {
            $id = $row[0];
            if (!isset($seen[$id])) {
                DB::table('area_regencies')->insert([
                    'id' => $id,
                    'province_id' => $row[1],
                    'name' => $row[2],
                ]);
                $seen[$id] = true;
            } else {
                $skipped++;
            }
        }
        echo "Regencies skipped (duplicate): $skipped\n";

        // === Insert Districts ===
        $districts = array_map('str_getcsv', file(database_path('data/districts.csv')));
        $seen = [];
        $skipped = 0;
        foreach ($districts as $row) {
            $id = $row[0];
            if (!isset($seen[$id])) {
                DB::table('area_districts')->insert([
                    'id' => $id,
                    'regency_id' => $row[1],
                    'name' => $row[2],
                ]);
                $seen[$id] = true;
            } else {
                $skipped++;
            }
        }
        echo "Districts skipped (duplicate): $skipped\n";

        // === Insert Villages ===
        $villages = array_map('str_getcsv', file(database_path('data/villages.csv')));
        $seen = [];
        $skipped = 0;
        foreach ($villages as $row) {
            $id = $row[0];
            if (!isset($seen[$id])) {
                DB::table('area_villages')->insert([
                    'id' => $id,
                    'district_id' => $row[1],
                    'name' => $row[2],
                ]);
                $seen[$id] = true;
            } else {
                $skipped++;
            }
        }
        echo "Villages skipped (duplicate): $skipped\n";
    }
}
