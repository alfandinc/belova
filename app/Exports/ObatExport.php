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
        'nama' => 'Nama',
        'hpp' => 'HPP',
        'hpp_jual' => 'HPP Jual',
        'harga_nonfornas' => 'Harga Non-Fornas',
        'metode_bayar' => 'Metode Bayar',
        'kategori' => 'Kategori',
        'dosis' => 'Dosis',
        'satuan' => 'Satuan',
        'is_generik' => 'Generik',
    ];
    /** @var string[] */
    protected $allowedColumns = [
        'id','kode_obat','nama','hpp','hpp_jual','harga_nonfornas','metode_bayar','kategori','dosis','satuan','is_generik'
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
        // Always export only active medications
        $query = Obat::where('status_aktif', 1)->with('metodeBayar');

        // Preserve optional filters if provided (kategori, metode_bayar_id)
        if ($this->request->has('kategori') && !empty($this->request->kategori)) {
            $query->where('kategori', $this->request->kategori);
        }
        if ($this->request->has('metode_bayar_id') && !empty($this->request->metode_bayar_id)) {
            $query->where('metode_bayar_id', $this->request->metode_bayar_id);
        }

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
                case 'nama':
                    $row[] = $obat->nama; break;
                case 'hpp':
                    $row[] = $obat->hpp; break;
                case 'hpp_jual':
                    $row[] = $obat->hpp_jual; break;
                case 'harga_nonfornas':
                    $row[] = $obat->harga_nonfornas; break;
                case 'metode_bayar':
                    $row[] = optional($obat->metodeBayar)->nama ?: '-'; break;
                case 'kategori':
                    $row[] = $obat->kategori; break;
                case 'dosis':
                    $row[] = $obat->dosis; break;
                case 'satuan':
                    $row[] = $obat->satuan; break;
                case 'is_generik':
                    // Ensure we write a visible '0' or '1' string so Excel doesn't render it as empty
                    $raw = $obat->getAttributes()['is_generik'] ?? ($obat->is_generik ?? 0);
                    $row[] = (string) ((int) $raw); break;
                default:
                    $row[] = '';
            }
        }
        return $row;
    }
}
