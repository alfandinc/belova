<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateKodeTindakanPricesSeeder extends Seeder
{
    public function run()
    {
        $path = base_path('database/data/update-kodetindakan.csv');

        if (!file_exists($path)) {
            $this->command->error("CSV file not found: $path");
            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command->error("Failed to open CSV file: $path");
            return;
        }

        $header = null;
        $updated = 0;
        $notFound = 0;
        $rowNumber = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            // skip empty rows
            if ($rowNumber === 1) {
                // try to detect header row
                $header = $row;
                continue;
            }

            if (!$header) {
                continue;
            }

            // map header => value
            $data = array_combine($header, $row);
            if ($data === false) {
                // malformed row
                continue;
            }

            // KODE TINDAKAN column contains the code
            $kode = isset($data['KODE TINDAKAN']) ? trim($data['KODE TINDAKAN']) : null;
            if (empty($kode)) {
                continue;
            }

            // HARGA NORMAL -> harga_jual
            $hargaNormal = $this->parseNumber(isset($data['HARGA NORMAL']) ? $data['HARGA NORMAL'] : null);
            // HARGA EXTRA DISKON -> harga_bottom
            $hargaExtra = $this->parseNumber(isset($data['HARGA EXTRA DISKON']) ? $data['HARGA EXTRA DISKON'] : null);

            $values = [];
            if ($hargaNormal !== null) {
                $values['harga_jual'] = $hargaNormal;
            }
            if ($hargaExtra !== null) {
                $values['harga_bottom'] = $hargaExtra;
            }

            if (empty($values)) {
                continue;
            }

            $affected = DB::table('erm_kode_tindakan')->where('kode', $kode)->update($values);
            if ($affected) {
                $updated += $affected;
            } else {
                $notFound++;
                $this->command->info("Kode not found or not updated: $kode");
            }
        }

        fclose($handle);

        $this->command->info("Update completed. Rows updated: $updated. Kode not found: $notFound");
    }

    /**
     * Parse a number formatted like "95.000,00" or "1.400.000,00" into float/int
     * Returns null if input is empty or not parseable.
     */
    protected function parseNumber($value)
    {
        if ($value === null) return null;
        $v = trim($value);
        if ($v === '') return null;

        // remove thousand dots and replace decimal comma with dot
        // also remove any non numeric, non comma, non dot characters
        $v = preg_replace('/[^0-9,\.\-]/', '', $v);

        // If contains comma and dot, assume dot is thousand separator and comma decimal
        if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } elseif (strpos($v, ',') !== false && strpos($v, '.') === false) {
            // numbers like "95000,00"
            $v = str_replace(',', '.', $v);
        } else {
            // numbers like "95000.00" or "95000"
            // keep as is
        }

        if ($v === '') return null;

        if (!is_numeric($v)) return null;

        // return as string to avoid float precision issues for money; DB expects numeric
        return $v;
    }
}
