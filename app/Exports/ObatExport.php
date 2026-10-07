<?php
namespace App\Exports;

use App\Models\ERM\Obat;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ObatExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;
    protected $columns = [];
    /** @var array<string,string> */
    protected $headingMap = [
        'id' => 'ID',
        'kode_obat' => 'Kode Obat',
        'kode_obat_lama' => 'Kode Lama',
        'nama' => 'Nama',
        'hpp' => 'HPP',
        'hna' => 'HNA',
        'harga_nonfornas' => 'Harga Jual',
        'metode_bayar' => 'Metode Bayar',
        'kategori' => 'Kategori',
        'zat_aktif' => 'Zat Aktif',
        'dosis' => 'Dosis',
        'satuan' => 'Satuan Dosis',
        'satuan_stok' => 'Satuan Stok',
        'is_generik' => 'Generik',
        'status_aktif' => 'Status',
    ];
    /** @var string[] */
    protected $allowedColumns = [
        'id','kode_obat','kode_obat_lama','nama','hpp','hna','harga_nonfornas','metode_bayar','kategori','zat_aktif','dosis','satuan','satuan_stok','is_generik','status_aktif'
    ];
    public function __construct($request)
    {
        $this->request = $request;
        $cols = (array) ($request->input('columns', []));
        // If nothing provided, default to full set (previous behavior)
        if (empty($cols)) {
            $cols = $this->allowedColumns;
        }
        // Sanitize and preserve order
        $this->columns = array_values(array_intersect($cols, $this->allowedColumns));
    }
    public function collection()
    {
        // Same filters as the master obat table
        $query = Obat::withInactive()->with(['metodeBayar', 'zatAktifs'])->orderBy('nama');

        $controller = app(\App\Http\Controllers\ERM\ObatController::class);
        $controller->applyIndexFilters($query, $this->request);
        $controller->applySearch($query, (string) $this->request->input('q', ''));

        // Return full models so WithMapping can format the output
        return $query->get();
    }
    public function headings(): array
    {
        // Build headings based on selected columns
        $cols = $this->columns;
        if (empty($cols)) {
            $cols = $this->allowedColumns;
        }
        return array_map(function ($col) {
            return $this->headingMap[$col] ?? $col;
        }, $cols);
    }

    /**
     * Map each Obat model to the desired row format for the Excel.
     */
    public function map($obat): array
    {
        $row = [];
        $cols = $this->columns;
        if (empty($cols)) {
            $cols = $this->allowedColumns;
        }
        foreach ($cols as $col) {
            switch ($col) {
                case 'id':
                    $row[] = $obat->id; break;
                case 'kode_obat':
                    $row[] = $obat->kode_obat; break;
                case 'kode_obat_lama':
                    $row[] = $obat->kode_obat_lama; break;
                case 'nama':
                    $row[] = $obat->nama; break;
                case 'hpp':
                    $row[] = $obat->hpp; break;
                case 'hna':
                    $row[] = $obat->hna; break;
                case 'harga_nonfornas':
                    $row[] = $obat->harga_nonfornas; break;
                case 'metode_bayar':
                    $row[] = optional($obat->metodeBayar)->nama ?: '-'; break;
                case 'kategori':
                    $row[] = $obat->kategori; break;
                case 'zat_aktif':
                    $row[] = $obat->zatAktifs
                        ->pluck('nama')
                        ->filter()
                        ->implode(', ');
                    break;
                case 'dosis':
                    $row[] = $obat->dosis; break;
                case 'satuan':
                    $row[] = $obat->satuan; break;
                case 'satuan_stok':
                    $row[] = $obat->satuan_stok; break;
                case 'is_generik':
                    // Ensure we write a visible '0' or '1' string so Excel doesn't render it as empty
                    $raw = $obat->getAttributes()['is_generik'] ?? ($obat->is_generik ?? 0);
                    $row[] = (string) ((int) $raw); break;
                case 'status_aktif':
                    $row[] = $obat->status_aktif ? 'Aktif' : 'Tidak Aktif'; break;
                default:
                    $row[] = '';
            }
        }
        return $row;
    }
}
