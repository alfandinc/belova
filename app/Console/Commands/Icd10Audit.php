<?php

namespace App\Console\Commands;

use App\Support\Icd10Code;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ICD-10 readiness for SatuSehat:
 * - master: rows without a code (left from an import) can be removed with --hapus-kosong;
 * - diagnosa kerja 1-5: how many are real ICD-10 codes, category codes (should use the subcode), or free text.
 * Nothing in the asesmen is changed: a doctor has to pick the right code.
 */
class Icd10Audit extends Command
{
    protected $signature = 'icd10:audit
        {--hapus-kosong : Delete master rows that have no code and no description}
        {--lengkapi : Add codes from database/data/icd-10.csv that are missing in the master (never duplicates)}
        {--contoh=10 : How many examples of free-text diagnoses to show}';

    protected $description = 'Cek kesiapan ICD-10 (master + diagnosa kerja) untuk dikirim ke SatuSehat';

    public function handle(): int
    {
        $this->info('Master ICD-10');
        $total = DB::table('erm_icd10')->count();
        $emptyQuery = DB::table('erm_icd10')
            ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', ''));
        $empty = (clone $emptyQuery)->count();
        $usedEmpty = DB::table('erm_kategori_tindakan_icd10')->whereIn('icd10_id', (clone $emptyQuery)->select('id'))->count();
        $this->line("  Total baris: {$total}, kode unik: " . DB::table('erm_icd10')->where('code', '!=', '')->distinct()->count('code'));
        $this->line("  Baris kosong (tanpa kode & deskripsi): {$empty}" . ($usedEmpty ? " — {$usedEmpty} dipakai di kategori tindakan, tidak dihapus" : ''));

        if ($this->option('hapus-kosong') && $empty > 0) {
            $deleted = (clone $emptyQuery)
                ->whereNotIn('id', DB::table('erm_kategori_tindakan_icd10')->select('icd10_id'))
                ->delete();
            $this->info("  Dihapus: {$deleted} baris kosong.");
        } elseif ($empty > 0) {
            $this->line('  (jalankan dengan --hapus-kosong untuk menghapusnya)');
        }

        // Compare with the ICD-10 2010 source file (the seeder skipped its first line as a header, so A00 was never loaded)
        $csvPath = base_path('database/data/icd-10.csv');
        if (is_readable($csvPath)) {
            $existing = DB::table('erm_icd10')->where('code', '!=', '')->pluck('code')->flip();
            $missing = [];
            $file = fopen($csvPath, 'r');
            while (($row = fgetcsv($file)) !== false) {
                $code = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? '')));
                if ($code === '' || isset($existing[$code]) || isset($missing[$code])) {
                    continue;
                }
                $missing[$code] = ['code' => $code, 'description' => trim((string) ($row[1] ?? '')), 'category' => trim((string) ($row[2] ?? '')) ?: null];
            }
            fclose($file);
            $this->line('  Kode di file ICD-10 2010 yang belum ada di master: ' . count($missing)
                . ($missing ? ' (' . implode(', ', array_slice(array_keys($missing), 0, 10)) . (count($missing) > 10 ? ', ...' : '') . ')' : ''));
            if ($missing && $this->option('lengkapi')) {
                $now = now();
                foreach (array_chunk(array_values($missing), 500) as $chunk) {
                    DB::table('erm_icd10')->insert(array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk));
                }
                $this->info('  Ditambahkan: ' . count($missing) . ' kode.');
            } elseif ($missing) {
                $this->line('  (jalankan dengan --lengkapi untuk menambahkannya)');
            }
        }

        $this->newLine();
        $this->info('Diagnosa kerja 1-5 di asesmen dokter');
        $counts = ['kode' => 0, 'kategori' => 0, 'teks' => 0];
        $examples = [];
        $codesSeen = [];

        DB::table('erm_asesmen_penunjang')->select(array_merge(['id', 'visitation_id'], Icd10Code::DIAGNOSA_FIELDS))
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$counts, &$examples, &$codesSeen) {
                foreach ($rows as $row) {
                    foreach (Icd10Code::DIAGNOSA_FIELDS as $field) {
                        $text = trim((string) $row->{$field});
                        if ($text === '' || $text === '-') {
                            continue;
                        }
                        $code = Icd10Code::extract($text);
                        if ($code) {
                            $codesSeen[$code][] = $text;
                        } else {
                            $counts['teks']++;
                            if (count($examples) < (int) $this->option('contoh')) {
                                $examples[] = "{$row->visitation_id}: {$text}";
                            }
                        }
                    }
                }
            });

        // Codes: do they exist, and are they categories that have subcodes?
        $known = DB::table('erm_icd10')->whereIn('code', array_keys($codesSeen))->pluck('code')->flip();
        $categories = array_flip(Icd10Code::categoriesWithSubcodes(array_keys($codesSeen)));
        foreach ($codesSeen as $code => $texts) {
            if (!isset($known[$code])) {
                $counts['teks'] += count($texts);
            } elseif (isset($categories[$code])) {
                $counts['kategori'] += count($texts);
            } else {
                $counts['kode'] += count($texts);
            }
        }

        $this->table(['Jenis', 'Jumlah', 'Bisa dikirim ke SatuSehat'], [
            ['Kode ICD-10 spesifik', $counts['kode'], 'Ya'],
            ['Kode kategori (punya subkode)', $counts['kategori'], 'Terkirim, tapi sebaiknya subkode'],
            ['Teks bebas / kode tidak dikenal', $counts['teks'], 'Tidak'],
        ]);
        if ($examples) {
            $this->line('Contoh teks bebas:');
            foreach ($examples as $e) {
                $this->line('  - ' . $e);
            }
        }

        return self::SUCCESS;
    }
}
