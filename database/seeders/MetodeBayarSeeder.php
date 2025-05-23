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
            ['id' => 1, 'nama' => 'Umum'],
            ['id' => 2, 'nama' => 'InHealth'],
        ];

        foreach ($mb as $item) {
            MetodeBayar::create($item);
        }
    }
}
