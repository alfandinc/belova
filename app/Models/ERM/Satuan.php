<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Satuan master: the option list for every unit dropdown (satuan stok & dosis obat, sediaan produk R&D).
 * Rows elsewhere store the satuan as text (its `nama`), so renaming here rewrites those texts too.
 */
class Satuan extends Model
{
    protected $table = 'erm_satuan';

    protected $fillable = ['nama', 'untuk_stok', 'untuk_dosis'];

    protected $casts = [
        'untuk_stok' => 'boolean',
        'untuk_dosis' => 'boolean',
    ];

    private const CACHE_KEY = 'erm_satuan_options';

    /** Text columns that hold a satuan nama. */
    public const USAGE_COLUMNS = [
        ['erm_obat', 'satuan_stok'],
        ['erm_obat', 'satuan'],
        ['rnd_produk', 'sediaan'],
    ];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** Units stock, HPP and harga jual are counted in. */
    public static function stokOptions(): array
    {
        return self::options()['stok'];
    }

    /** Units for obat dosis (measurement units plus the stok units, for doses counted per piece). */
    public static function dosisOptions(): array
    {
        $options = self::options();

        return array_values(array_unique(array_merge($options['dosis'], $options['stok'])));
    }

    /** Measurement units only (without the stok units). */
    public static function dosisOnlyOptions(): array
    {
        return self::options()['dosis'];
    }

    /** Every satuan, for R&D sediaan (netto unit). */
    public static function allOptions(): array
    {
        return self::options()['all'];
    }

    private static function options(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $rows = self::query()->orderBy('id')->get(['nama', 'untuk_stok', 'untuk_dosis']);

            return [
                'stok' => $rows->where('untuk_stok', true)->pluck('nama')->values()->all(),
                'dosis' => $rows->where('untuk_dosis', true)->pluck('nama')->values()->all(),
                'all' => $rows->pluck('nama')->values()->all(),
            ];
        });
    }

    /** Number of rows (obat + produk R&D) that use this satuan. */
    public function usageCount(): int
    {
        return collect(self::USAGE_COLUMNS)->sum(
            fn ($column) => DB::table($column[0])->where($column[1], $this->nama)->count()
        );
    }

    /** Usage per satuan nama (lowercased key), counted once for the whole list. */
    public static function usageCounts(): array
    {
        $counts = [];
        foreach (self::USAGE_COLUMNS as [$table, $column]) {
            $rows = DB::table($table)->whereNotNull($column)->selectRaw("$column as nama, COUNT(*) as total")->groupBy($column)->get();
            foreach ($rows as $row) {
                $key = strtolower((string) $row->nama);
                $counts[$key] = ($counts[$key] ?? 0) + (int) $row->total;
            }
        }

        return $counts;
    }

    /** Rename and rewrite the stored text everywhere it is used, so all data stays aligned. */
    public function rename(string $nama): int
    {
        $old = $this->nama;

        return DB::transaction(function () use ($old, $nama) {
            $this->update(['nama' => $nama]);
            if ($old === $nama) {
                return 0;
            }

            return collect(self::USAGE_COLUMNS)->sum(
                fn ($column) => DB::table($column[0])->where($column[1], $old)->update([$column[1] => $nama])
            );
        });
    }
}
