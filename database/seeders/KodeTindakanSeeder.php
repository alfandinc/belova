<?php

namespace Database\Seeders;

use App\Models\ERM\KodeTindakan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KodeTindakanSeeder extends Seeder
{
    /**
     * Parse Indonesian number format (using period as thousand separator and comma as decimal separator)
     * Example: 15.000,00 -> 15000.00
     */
    private function parseIndonesianNumber(string $value): float
    {
        // Remove any whitespace
        $value = trim($value);
        
        // If empty or not a valid format, return 0
        if (empty($value) || $value === '0') {
            return 0;
        }
        
        // Handle Indonesian format: 15.000,00
        // Replace comma with period for decimal separator
        $value = str_replace(',', '.', $value);
        
        // Find the last period (which should be the decimal separator)
        $lastDotPos = strrpos($value, '.');
        
        if ($lastDotPos !== false) {
            // Check if the last period has exactly 2 digits after it (decimal part)
            $afterLastDot = substr($value, $lastDotPos + 1);
            
            if (strlen($afterLastDot) === 2 && is_numeric($afterLastDot)) {
                // This is a decimal separator
                $integerPart = substr($value, 0, $lastDotPos);
                $decimalPart = $afterLastDot;
                
                // Remove periods from integer part (thousand separators)
                $integerPart = str_replace('.', '', $integerPart);
                
                // Combine integer and decimal parts
                $cleanValue = $integerPart . '.' . $decimalPart;
            } else {
                // No decimal part, all periods are thousand separators
                $cleanValue = str_replace('.', '', $value);
            }
        } else {
            // No periods at all
            $cleanValue = $value;
        }
        
        // Check if the result is numeric
        if (is_numeric($cleanValue)) {
            // Round to 2 decimal places to match DECIMAL(15,2) format
            return round((float) $cleanValue, 2);
        }
        
        return 0;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path to CSV file
        $csvFile = database_path('data/kode_tindakan.csv');
        
        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        // Truncate table before seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        KodeTindakan::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Reading CSV file: ' . $csvFile);

        $handle = fopen($csvFile, 'r');
        
        // Skip header row
        $header = fgetcsv($handle);
        $this->command->info('CSV Headers: ' . implode(', ', $header));

        $data = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map CSV columns to database fields
            // CSV Headers: ID, KODE TINDAKAN, NAMA TINDAKAN, JASA TERAPIS, HPP
            $data[] = [
                'kode' => $row[1] ?? '', // KODE TINDAKAN
                'nama' => $row[2] ?? '', // NAMA TINDAKAN
                'harga_jasmed' => $this->parseIndonesianNumber($row[3] ?? '0'), // JASA TERAPIS
                'hpp' => $this->parseIndonesianNumber($row[4] ?? '0'), // HPP
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches of 100
            if (count($data) >= 100) {
                KodeTindakan::insert($data);
                $data = [];
                $this->command->info("Processed {$rowCount} rows...");
            }
        }

        // Insert remaining data
        if (!empty($data)) {
            KodeTindakan::insert($data);
        }

        fclose($handle);

        $totalRecords = KodeTindakan::count();
        $this->command->info("Successfully seeded {$totalRecords} Kode Tindakan records from CSV.");
    }
}
