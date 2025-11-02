<?php

namespace App\Imports;

use App\Models\ERM\StokOpnameItem;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class StokOpnameImport implements ToCollection, WithHeadingRow
{
    protected $stokOpnameId;
    public function __construct($stokOpnameId)
    {
        $this->stokOpnameId = $stokOpnameId;
    }
    public $imported = 0;
    public $skipped = 0;
    public $skippedRows = [];
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            \Log::info('Import row', $row->toArray());
            // Required: obat_id and stok_sistem and stok_fisik (stok_fisik can be zero)
            if (!isset($row['obat_id']) || (!$row->has('stok_sistem') && !isset($row['stok_sistem'])) || (!$row->has('stok_fisik') && !isset($row['stok_fisik']))) {
                $this->skipped++;
                $this->skippedRows[] = $row->toArray();
                continue;
            }
            // Skip if obat_id does not exist in erm_obat
            if (!\App\Models\ERM\Obat::where('id', $row['obat_id'])->exists()) {
                $this->skipped++;
                $this->skippedRows[] = $row->toArray();
                continue;
            }
            $stokSistem = is_numeric($row['stok_sistem']) ? $row['stok_sistem'] + 0 : 0;
            $stokFisik = is_numeric($row['stok_fisik']) ? $row['stok_fisik'] + 0 : 0;
            $selisih = $stokFisik - $stokSistem;

            // Try to find matching ObatStokGudang by obat_id + batch for the stok opname's gudang
            $batchName = isset($row['batch']) ? trim($row['batch']) : null;
            $batchId = null;
            $expiration = null;

            if ($batchName) {
                $stokOpname = \App\Models\ERM\StokOpname::find($this->stokOpnameId);
                $gudangId = $stokOpname ? $stokOpname->gudang_id : null;
                if ($gudangId) {
                    $stokGudang = \App\Models\ERM\ObatStokGudang::where('obat_id', $row['obat_id'])
                        ->where('gudang_id', $gudangId)
                        ->where('batch', $batchName)
                        ->first();
                    if ($stokGudang) {
                        $batchId = $stokGudang->id;
                        $expiration = $stokGudang->expiration_date;
                    }
                }
            }

            // If expiration_date column provided in import, use it (overrides found expiration)
            if (isset($row['expiration_date']) && $row['expiration_date']) {
                $raw = $row['expiration_date'];
                // If Excel provided a numeric serial date (e.g., 45997), convert it
                if (is_numeric($raw)) {
                    try {
                        $dt = ExcelDate::excelToDateTimeObject((float) $raw);
                        $expiration = $dt->format('Y-m-d');
                    } catch (\Exception $e) {
                        // fallback: store raw value
                        $expiration = $raw;
                    }
                } elseif ($raw instanceof \DateTimeInterface) {
                    $expiration = $raw->format('Y-m-d');
                } else {
                    // try parseable string (e.g., 2025-09-06 or 06/09/2025)
                    try {
                        $expiration = Carbon::parse($raw)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // keep raw if cannot parse
                        $expiration = $raw;
                    }
                }
            }

            StokOpnameItem::create([
                'stok_opname_id' => $this->stokOpnameId,
                'obat_id' => $row['obat_id'],
                'batch_id' => $batchId,
                'batch_name' => $batchName,
                'expiration_date' => $expiration,
                'stok_sistem' => $stokSistem,
                'stok_fisik' => $stokFisik,
                'selisih' => $selisih,
                'notes' => $row['notes'] ?? null,
            ]);
            $this->imported++;
        }
    }
}
