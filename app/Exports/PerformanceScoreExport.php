<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PerformanceScoreExport implements FromArray, WithHeadings
{
    protected $headers;
    protected $rows;

    public function __construct($headers, $rows)
    {
        $this->headers = $headers;
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
