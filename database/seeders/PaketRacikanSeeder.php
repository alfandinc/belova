<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\PaketRacikan;
use App\Models\ERM\PaketRacikanDetail;
use App\Models\ERM\Obat;
use App\Models\ERM\WadahObat;

class PaketRacikanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some sample wadah and obat
        $wadah = WadahObat::first();
        $obats = Obat::take(3)->get();

        if ($wadah && $obats->count() >= 3) {
            // Create sample paket racikan
            $paket1 = PaketRacikan::create([
                'nama_paket' => 'Paket Racikan Demam',
                'deskripsi' => 'Paket racikan untuk mengatasi demam dan panas',
                'wadah_id' => $wadah->id,
                'bungkus_default' => 10,
                'aturan_pakai_default' => '3 x 1 hari',
                'is_active' => true,
                'created_by' => 1,
            ]);

            // Add obat to paket
            foreach ($obats as $index => $obat) {
                PaketRacikanDetail::create([
                    'paket_racikan_id' => $paket1->id,
                    'obat_id' => $obat->id,
                    'dosis' => ($index + 1) . ' tablet',
                ]);
            }

            $paket2 = PaketRacikan::create([
                'nama_paket' => 'Paket Racikan Batuk',
                'deskripsi' => 'Paket racikan untuk mengatasi batuk',
                'wadah_id' => $wadah->id,
                'bungkus_default' => 15,
                'aturan_pakai_default' => '2 x 1 hari',
                'is_active' => true,
                'created_by' => 1,
            ]);

            // Add obat to paket 2
            foreach ($obats->take(2) as $index => $obat) {
                PaketRacikanDetail::create([
                    'paket_racikan_id' => $paket2->id,
                    'obat_id' => $obat->id,
                    'dosis' => '0.5 tablet',
                ]);
            }
        }
    }
}
