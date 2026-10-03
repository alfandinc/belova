<?php

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Billing index list (same rows as the billing datatable), built by BillingController::exportVisitations().
 */
class BillingListExport extends DefaultValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles, WithCustomValueBinder, WithStrictNullComparison
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'Tanggal Visit',
            'Nama Pasien',
            'No RM',
            'Dokter',
            'Klinik',
            'Referral Type',
            'Referral Detail',
            'Total',
            'Kekurangan',
            'Retur',
            'Status',
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        // No RM and invoice numbers are text (keep leading zeros)
        if (in_array($cell->getColumn(), ['A', 'D'], true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
