<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
USE App\Models\ERM\RadiologiTest;
USE App\Models\ERM\RadiologiKategori;

class RadiologiTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Get category IDs for reference
        $categories = RadiologiKategori::pluck('id', 'nama')->toArray();
                
        // PEMERIKSAAN RONTGEN tests
        $rontgenTests = [
            'WATERS',
            'SKULL AP',
            'SKULL LAT',
            'SINUS PARANASALIS',
            'SKULL LAT ADENOID',
            'CALD WELL',
            'MASTOID SCHULLER DEX',
            'MASTOID SCHULLER SIN',
            // SPINE
            'CERVICAL AP',
            'CERVICAL LAT',
            'CERVICAL OBL DEX',
            'CERVICAL OBL SIN',
            'THORACALIS AP',
            'THORACALIS LAT',
            'LUMBO-SACRALIS AP',
            'LUMBO-SACRALIS LAT',
            'THORACO-LUMBALIS AP',
            'THORACO-LUMBALIS LAT',
            // SPINE LAIN
            'ANTEBR DEX AP/LAT',
            'ANTEBR SIN AP/LAT',
            'MANUS DEX',
            'MANUS SIN',
            // EKSTRIMITAS ATAS
            'GENU DEX AP/LAT',
            'GENU SIN AP/LAT',
            'THORAX PA',
            // KONTRAS
            'BOF/KUB**',
            'IVP**',
            'H.S.G**',
            'OSEPHAGOGRAM**',
            'CHOLANGIOGRAPHY**',
            'APPENDICOGRAM**',
        ];
        foreach ($rontgenTests as $name) {
            RadiologiTest::create([
                'nama' => $name,
                'radiologi_kategori_id' => $categories['PEMERIKSAAN RONTGEN'] ?? null,
                'harga' => rand(100000, 500000)
            ]);
        }

        // ULTRASONOGRAPHY tests
        $usgTests = [
            'USG UPPER ABD',
            'USG LOWER ABD',
            'USG UP+LOW ABD',
            'USG',
        ];
        foreach ($usgTests as $name) {
            RadiologiTest::create([
                'nama' => $name,
                'radiologi_kategori_id' => $categories['ULTRASONOGRAPHY'] ?? null,
                'harga' => rand(100000, 500000)
            ]);
        }

        // BMD tests
        $bmdTests = [
            'AP LIMBAL',
            'COXAE HIP DEX/SIN',
            'FORE ARM DEX/SIN',
            'LUMBAL/COXAE DEX/SIN',
            'WHOLE BODY',
        ];
        foreach ($bmdTests as $name) {
            RadiologiTest::create([
                'nama' => $name,
                'radiologi_kategori_id' => $categories['BMD'] ?? null,
                'harga' => rand(100000, 500000)
            ]);
        }

        // MAMMOGRAPHY tests
        $mammoTests = [
            'MAMMOGRAPHY DEX/SIN',
            'MAMMOGRAPHY DEX',
            'MAMMOGRAPHY SIN',
        ];
        foreach ($mammoTests as $name) {
            RadiologiTest::create([
                'nama' => $name,
                'radiologi_kategori_id' => $categories['MAMMOGRAPHY'] ?? null,
                'harga' => rand(100000, 500000)
            ]);
        }
    }
}
