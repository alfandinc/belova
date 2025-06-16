<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\LabTest;
use App\Models\ERM\LabKategori;

class LabTestSeeder extends Seeder
{
    public function run()
    {
        // Get category IDs for reference
        $categories = LabKategori::pluck('id', 'nama')->toArray();

        // HEMATOLOGI tests
        $hematologiTests = [
            'HEMATOLOGI LENGKAP (CBC+DIFF+LED)' => 150000,
            'HEMATOLOGI LENGKAP + RETIKULOSIT' => 180000,
            'LAJU ENDAP DARAH' => 50000,
            'HAPUSAN DARAH TEPI' => 120000,
            'GOLONGAN DARAH (ABO & Rh)' => 75000,
            'PROFIL IRON (SI+TIBC+FERRITIN)' => 250000,
            'SERUM IRON' => 100000,
            'TIBC' => 110000,
            'TRANSFERIN' => 130000,
            'FERRITIN' => 180000,
            'Hb ELEKTROFORESIS' => 300000,
            'G6PD' => 220000,
            'COOMBS TEST' => 160000,
            'Cd4' => 200000
        ];
        
        foreach ($hematologiTests as $name => $price) {
            LabTest::create([
                'nama' => $name,
                'lab_kategori_id' => $categories['HEMATOLOGI'],
                'harga' => $price
            ]);
        }
        
        // HEMOSTASIS tests
        $hemostasisTests = [
            'FAAL HEMOSTASIS' => 300000,
            'WAKTU PENDARAHAN (BT)' => 75000,
            'WAKTU PEMBEKUAN (CT)' => 75000,
            'PT (INR)' => 130000,
            'APTT' => 130000,
            'FIBRINOGEN' => 150000,
            'TES AGREGASI TROMBOSIT (TAT)' => 180000,
            'D-DIMER' => 250000,
            'VISKOSITAS PLASMA' => 200000,
            'VISKOSITAS DARAH' => 200000
        ];
        
        foreach ($hemostasisTests as $name => $price) {
            LabTest::create([
                'nama' => $name,
                'lab_kategori_id' => $categories['HEMOSTASIS'],
                'harga' => $price
            ]);
        }
        
        // URINALISA tests
        $urinalisaTests = [
            'URINALISIS LENGKAP' => 120000,
            'MIKROALBUMINURIA' => 150000,
            'PROTEIN BENCE JONES' => 180000,
            'ANALISA BATU GINJAL' => 250000
        ];
        
        foreach ($urinalisaTests as $name => $price) {
            LabTest::create([
                'nama' => $name,
                'lab_kategori_id' => $categories['URINALISA'], 
                'harga' => $price
            ]);
        }
        
        // Add more tests for other categories as needed
    }
}