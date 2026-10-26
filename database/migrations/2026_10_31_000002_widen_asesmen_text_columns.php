<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Asesmen free-text fields (anamnesis, riwayat, pemeriksaan fisik, masalah, rencana & tindak lanjut, …) were
 * varchar(191), so a longer text could not be saved. Turn every varchar column of the asesmen tables into TEXT,
 * except keys, flags and short measurements. The columns are read from the live schema (prod drifts from local).
 */
return new class extends Migration
{
    private const TABLES = [
        'erm_asesmen_anak', 'erm_asesmen_dalam', 'erm_asesmen_estetika', 'erm_asesmen_gigi', 'erm_asesmen_penunjang',
        'erm_asesmen_perawats', 'erm_asesmen_saraf', 'erm_asesmen_tht', 'erm_asesmen_umum',
    ];

    // Keys, flags (autoanamnesis has a default), vital signs / GCS / measurements, dates and image paths
    private const KEEP = [
        'visitation_id', 'user_id', 'autoanamnesis', 'alloanamnesis',
        'td', 'n', 'r', 's', 'e', 'm', 'v', 'bb', 'tb', 'lk', 'imt', 'lla', 'nadi', 'suhu', 'rr', 'skor',
        'presentase', 'porsi', 'tanggal_homecare',
    ];

    public function up(): void
    {
        foreach ($this->columns('varchar') as $table => $columns) {
            $this->modify($table, $columns, 'TEXT NULL');
        }
    }

    public function down(): void
    {
        // Longer texts saved in the meantime would be cut, so only shrink what still fits
        foreach ($this->columns('text') as $table => $columns) {
            $fits = array_filter($columns, fn ($c) => !DB::table($table)->whereRaw('CHAR_LENGTH(`' . $c . '`) > 191')->exists());
            $this->modify($table, $fits, 'VARCHAR(191) NULL');
        }
    }

    private function columns(string $type): array
    {
        $rows = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->whereIn('TABLE_NAME', self::TABLES)
            ->where('DATA_TYPE', $type)
            ->get(['TABLE_NAME', 'COLUMN_NAME']);

        $result = [];
        foreach ($rows as $row) {
            $column = $row->COLUMN_NAME;
            if (in_array($column, self::KEEP, true) || str_ends_with($column, '_gambar')) {
                continue;
            }
            // down(): only the columns this migration widened (keluhan_utama etc. were TEXT already)
            if ($type === 'text' && in_array($column, ['keluhan_utama', 'diagnosakerja_6'], true)) {
                continue;
            }
            $result[$row->TABLE_NAME][] = $column;
        }

        return $result;
    }

    private function modify(string $table, array $columns, string $definition): void
    {
        if (!$columns) {
            return;
        }
        $parts = array_map(fn ($c) => 'MODIFY `' . $c . '` ' . $definition, $columns);
        DB::statement('ALTER TABLE `' . $table . '` ' . implode(', ', $parts));
    }
};
