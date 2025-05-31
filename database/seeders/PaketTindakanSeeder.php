<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\PaketTindakan;

class PaketTindakanSeeder extends Seeder
{
    public function run(): void
    {
        $pakets = [
            [
                'nama' => 'Paket Perawatan Glowing',
                'harga_paket' => 750000,
            ],
            [
                'nama' => 'Paket Anti Aging Premium',
                'harga_paket' => 850000,
            ],
        ];

        foreach ($pakets as $data) {
            PaketTindakan::create($data);
        }
    }
}
