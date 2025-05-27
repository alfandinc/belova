<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WadahObatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('erm_wadah_obat')->insert([
            ['nama' => 'M F Pulveres', 'harga' => 1000.00, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'M F Capsules', 'harga' => 2000.00, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Cream / Lotion', 'harga' => 3000.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
