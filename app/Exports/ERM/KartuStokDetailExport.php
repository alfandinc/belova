<?php
namespace App\Exports\ERM;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class KartuStokDetailExport implements FromArray, ShouldAutoSize
{
    protected $rows;

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    /**
     * Return array of rows to be exported.
     * Each element must be an array representing a single row.
     *
     * @return array
     */
    public function array(): array
    {
        return $this->rows;
    }
}
