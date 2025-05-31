<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\PaketTindakan;
use App\Models\ERM\Tindakan;
use Illuminate\Support\Facades\DB;

class PaketTindakanDetailSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil ID paket dan tindakan berdasarkan nama
        $paket1 = PaketTindakan::where('nama', 'Paket Perawatan Glowing')->first();
        $paket2 = PaketTindakan::where('nama', 'Paket Anti Aging Premium')->first();

        $tindakan = Tindakan::pluck('id', 'nama');

        // Paket 1: Facial, Peeling, Masker
        DB::table('erm_paket_tindakan_detail')->insert([
            ['paket_tindakan_id' => $paket1->id, 'tindakan_id' => $tindakan['Facial Treatment']],
            ['paket_tindakan_id' => $paket1->id, 'tindakan_id' => $tindakan['Chemical Peeling']],
            ['paket_tindakan_id' => $paket1->id, 'tindakan_id' => $tindakan['Masker Collagen']],
        ]);

        // Paket 2: Laser, Microneedling, Masker
        DB::table('erm_paket_tindakan_detail')->insert([
            ['paket_tindakan_id' => $paket2->id, 'tindakan_id' => $tindakan['Laser Rejuvenation']],
            ['paket_tindakan_id' => $paket2->id, 'tindakan_id' => $tindakan['Microneedling']],
            ['paket_tindakan_id' => $paket2->id, 'tindakan_id' => $tindakan['Masker Collagen']],
        ]);
    }
}
