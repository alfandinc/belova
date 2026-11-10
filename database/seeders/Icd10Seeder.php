<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Icd10Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/icd-10.csv');

        // Open the file and read each line
        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("CSV file not found or not readable at $path");
        }

        $file = fopen($path, 'r');

        // The CSV has no header row (its first line is A00), only a UTF-8 BOM; blank lines are skipped.
        // Existing codes are left alone, so running it again does not duplicate anything.
        $existing = DB::table('erm_icd10')->pluck('code')->flip();
        while (($row = fgetcsv($file)) !== false) {
            $code = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? '')));
            if ($code === '' || isset($existing[$code])) {
                continue;
            }
            $existing[$code] = true;

            DB::table('erm_icd10')->insert([
                'code' => $code,
                'description' => $row[1] ?? '',
                'category' => $row[2] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
