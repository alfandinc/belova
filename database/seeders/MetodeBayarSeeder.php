<?php

namespace Database\Seeders;

use App\Models\ERM\MetodeBayar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MetodeBayarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mb = [
            'Umum',
            'InHealth',
        ];

        foreach ($mb as $mb) {
            MetodeBayar::create(['nama' => $mb]);
        }
    }
}
