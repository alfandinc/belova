<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\Obat;

class UpdateObatSeeder extends Seeder

{
    public function run()
    {
        $file = base_path('database/data/UpdateMasterObat.csv');
        if (!file_exists($file)) {
            echo "CSV file not found: $file\n";
            return;
        }

        $csv = array_map('str_getcsv', file($file));
        $header = array_map('trim', $csv[0]);
        unset($csv[0]);

        // Helper to convert comma decimal to dot
        $toDecimal = function($value) {
            $value = trim($value);
            if ($value === '') return null;
            // Remove thousands separator if present
            $value = str_replace(['.', ','], ['', '.'], $value);
            // If value contains more than one dot, keep only the last as decimal
            if (substr_count($value, '.') > 1) {
                $value = preg_replace('/\.(?=.*\.)/', '', $value);
            }
            return $value;
        };

                // Helper to normalize dosis field
        $normalizeDosis = function($value) {
            $value = trim($value);
            // If value looks like a decimal with comma, convert to dot
            if (preg_match('/^\d+,\d+$/', $value)) {
                return str_replace(',', '.', $value);
            }
            // If value looks like 'decimal/fraction', convert both and divide
            if (preg_match('/^(\d+,\d+|\d+)(\/)(\d+)$/', $value, $matches)) {
                $num = str_replace(',', '.', $matches[1]);
                $den = $matches[3];
                if (is_numeric($num) && is_numeric($den) && $den != 0) {
                    return (string)($num / $den);
                }
            }
            // Otherwise, return as-is
            return $value;
        };

        foreach ($csv as $row) {
            $data = array_combine($header, $row);

            if (empty($data['ID'])) continue;

            $obat = Obat::withoutGlobalScopes()->find($data['ID']);
            if ($obat) {
                $obat->dosis = (isset($data['Dosis']) && trim($data['Dosis']) !== '') ? $normalizeDosis($data['Dosis']) : null;
                $obat->satuan = (isset($data['Satuan']) && trim($data['Satuan']) !== '') ? $data['Satuan'] : null;
                $obat->hpp = (isset($data['HPP']) && trim($data['HPP']) !== '') ? $toDecimal($data['HPP']) : null;
                $obat->harga_net = (isset($data['Harga Net']) && trim($data['Harga Net']) !== '') ? $toDecimal($data['Harga Net']) : null;
                $obat->harga_nonfornas = (isset($data['Harga Non-Fornas']) && trim($data['Harga Non-Fornas']) !== '') ? $toDecimal($data['Harga Non-Fornas']) : null;
                // Always update kategori, even if only case changes
                $obat->kategori = isset($data['Kategori']) ? $data['Kategori'] : '';
                $obat->stok = (isset($data['Stok']) && trim($data['Stok']) !== '') ? $data['Stok'] : 0;
                $obat->hpp_jual = (isset($data['HPP Jual']) && trim($data['HPP Jual']) !== '') ? $toDecimal($data['HPP Jual']) : null;
                $obat->status_aktif = (isset($data['Status Aktif']) && trim($data['Status Aktif']) !== '') ? $data['Status Aktif'] : 0;
                $obat->save();
            } else {
                $obatName = isset($data['Nama']) ? $data['Nama'] : '';
                echo "Obat not found: ID {$data['ID']}, Nama '{$obatName}'.\n";
            }
        }

        echo "Obat table updated from CSV.\n";
    }
}
