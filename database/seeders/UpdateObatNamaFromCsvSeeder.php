<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ERM\Obat;

class UpdateObatNamaFromCsvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvPath = database_path('data/update-namaobat.csv');
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: $csvPath");
            return;
        }

        if (($handle = fopen($csvPath, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                $id = $data['id'] ?? null;
                $nama = $data['nama'] ?? null;
                if ($id && $nama) {
                    Obat::where('id', $id)->update(['nama' => $nama]);
                }
            }
            fclose($handle);
            $this->command->info('Obat names updated from CSV.');
        } else {
            $this->command->error('Failed to open CSV file.');
        }
    }
}
