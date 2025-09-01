<?php

namespace App\Imports;

use App\Models\ERM\StokOpnameItem;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

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
            if (!isset($row['obat_id']) || !isset($row['stok_sistem']) || !isset($row['stok_fisik'])) {
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
            $stokSistem = (int) $row['stok_sistem'];
            $stokFisik = (int) $row['stok_fisik'];
            $selisih = $stokFisik - $stokSistem;
            StokOpnameItem::create([
                'stok_opname_id' => $this->stokOpnameId,
                'obat_id' => $row['obat_id'],
                'stok_sistem' => $stokSistem,
                'stok_fisik' => $stokFisik,
                'selisih' => $selisih,
                'notes' => $row['notes'] ?? null,
            ]);
            $this->imported++;
        }
    }
}
