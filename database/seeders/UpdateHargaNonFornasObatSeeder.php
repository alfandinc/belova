<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ERM\Obat;

class UpdateHargaNonFornasObatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvPath = base_path('database/data/updatehargajual.csv'); // Adjust path as needed
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: $csvPath");
            return;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->error("Failed to open CSV file: $csvPath");
            return;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            $this->command->error("CSV file is empty or invalid header.");
            fclose($handle);
            return;
        }

        $updated = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            // Adjust the key below to match your CSV column name
            $id = $data['ID'] ?? null;
            $hargaNonFornasRaw = $data['harga jual'] ?? null;
            if ($id && $hargaNonFornasRaw !== null && $hargaNonFornasRaw !== '') {
                // Convert European format "1.894,76" to float
                $hargaNonFornas = floatval(str_replace([".", ","], ["", "."], $hargaNonFornasRaw));
                $obat = Obat::withInactive()->find($id);
                if ($obat) {
                    $obat->harga_nonfornas = $hargaNonFornas;
                    $obat->save();
                    $updated++;
                }
            }
        }
        fclose($handle);
        $this->command->info("Updated harga_nonfornas for $updated obat(s).");
    }
}
