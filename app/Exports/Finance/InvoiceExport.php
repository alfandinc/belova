<?php

namespace App\Exports\Finance;

use App\Models\ERM\Dokter;
use App\Models\Finance\Invoice;
use App\Services\Finance\BillingRowFormatter;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Invoice export (one row per invoice), downloaded from the "Download Invoice" modal on the Billing page.
 * Also has every column of the former Billing list export (No Invoice, Referral, Kekurangan, Retur, Status).
 * The modal preview uses row() too, so preview and file show the same values.
 */
class InvoiceExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, Responsable, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithStrictNullComparison, WithStyles
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    /** Column key => heading, in file order (keys are the row() keys / preview fields). */
    public const COLUMNS = [
        'invoice_number' => 'No Invoice',
        'tanggal_visit' => 'Tanggal Visit',
        'tanggal_dibayar' => 'Tanggal Dibayar',
        'no_rm' => 'No RM',
        'nama_pasien' => 'Nama Pasien',
        'nama_dokter' => 'Nama Dokter',
        'nama_klinik' => 'Nama Klinik',
        'referral_type' => 'Referral Type',
        'referral_detail' => 'Referral Detail',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
        'tax' => 'Tax',
        'total_amount' => 'Total Amount',
        'amount_paid' => 'Amount Paid',
        'change_amount' => 'Change Amount',
        'kekurangan' => 'Kekurangan',
        'retur' => 'Retur',
        'payment_method' => 'Paid Method',
        'status' => 'Status',
        'notes' => 'Notes',
    ];

    private const TEXT_COLUMNS = ['invoice_number', 'no_rm'];
    private const MONEY_COLUMNS = ['subtotal', 'discount', 'tax', 'total_amount', 'amount_paid', 'change_amount', 'kekurangan', 'retur'];

    private $startDate;
    private $endDate;
    private $klinikId;
    private $dokterId;

    public function __construct($startDate, $endDate, $klinikId = null, $dokterId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->klinikId = $klinikId;
        $this->dokterId = $dokterId;
    }

    public function query()
    {
        $withDokterUser = function ($morphTo) {
            $morphTo->morphWith([Dokter::class => ['user']]);
        };

        return Invoice::query()
            ->whereHas('visitation', function($q) {
                $q->whereBetween('tanggal_visitation', [$this->startDate, $this->endDate]);
                if ($this->klinikId) {
                    $q->where('klinik_id', $this->klinikId);
                }
                if ($this->dokterId) {
                    $q->where('dokter_id', $this->dokterId);
                }
            })
            ->with([
                // billing row counts for the status (all billings trashed -> Terhapus)
                'visitation' => function ($q) {
                    $q->select('erm_visitations.*')
                        ->selectRaw('(SELECT COUNT(*) FROM finance_billing fb WHERE fb.visitation_id = erm_visitations.id) as billing_total_count')
                        ->selectRaw('(SELECT COUNT(*) FROM finance_billing fb WHERE fb.visitation_id = erm_visitations.id AND fb.deleted_at IS NOT NULL) as billing_trashed_count');
                },
                'visitation.pasien',
                'visitation.dokter.user',
                'visitation.klinik',
                'visitation.referralable' => $withDokterUser,
                'visitation.pasien.referralable' => $withDokterUser,
                'piutangs',
            ]);
    }

    public function headings(): array
    {
        return array_values(self::COLUMNS);
    }

    public function map($invoice): array
    {
        $row = $this->row($invoice);

        return array_map(function ($key) use ($row) {
            return $row[$key];
        }, array_keys(self::COLUMNS));
    }

    /**
     * One invoice as [column key => value] (see COLUMNS).
     */
    public function row($invoice): array
    {
        $visitation = $invoice->visitation;
        $pasien = $visitation ? $visitation->pasien : null;
        $dokter = $visitation && $visitation->dokter ? $visitation->dokter->user->name ?? $visitation->dokter->id : null;
        $klinik = $visitation && $visitation->klinik ? $visitation->klinik->nama : null;

        [$paidMethod, $notes] = $this->paidMethodAndNotes($invoice);
        [$referralType, $referralDetail] = BillingRowFormatter::referralParts($visitation);
        $retur = (float) ($invoice->retur_amount ?? 0);

        return [
            'invoice_number' => $invoice->invoice_number,
            'tanggal_visit' => $visitation && $visitation->tanggal_visitation
                ? \Carbon\Carbon::parse($visitation->tanggal_visitation)->format('Y-m-d')
                : null,
            'tanggal_dibayar' => $invoice->payment_date
                ? \Carbon\Carbon::parse($invoice->payment_date)->format('Y-m-d H:i')
                : null,
            'no_rm' => optional($pasien)->id,
            'nama_pasien' => optional($pasien)->nama,
            'nama_dokter' => $dokter,
            'nama_klinik' => $klinik,
            'referral_type' => $referralType,
            'referral_detail' => $referralDetail,
            'subtotal' => $invoice->subtotal,
            'discount' => $invoice->discount,
            'tax' => $invoice->tax,
            'total_amount' => $invoice->total_amount,
            'amount_paid' => $invoice->amount_paid,
            'change_amount' => $invoice->change_amount,
            'kekurangan' => BillingRowFormatter::remainingAmount($invoice),
            'retur' => $retur > 0 ? $retur : null,
            'payment_method' => $paidMethod,
            'status' => BillingRowFormatter::statusLabel(
                $invoice,
                (int) optional($visitation)->billing_total_count,
                (int) optional($visitation)->billing_trashed_count
            ),
            'notes' => $notes,
        ];
    }

    /**
     * Payment method actually used (settled piutang -> its payment method) and a short note on what is still open.
     */
    private function paidMethodAndNotes($invoice): array
    {
        $paidMethod = $invoice->payment_method;
        $notes = '';

        $totalAmount = floatval($invoice->total_amount ?? 0);
        $amountPaid = floatval($invoice->amount_paid ?? 0);
        $isInvoiceFullyPaid = ($totalAmount > 0) && ($amountPaid >= $totalAmount);

        $piutangs = $invoice->piutangs ?? collect();

        $latestPiutang = $piutangs
            ->sortByDesc(function ($p) {
                return $p->payment_date ?? $p->updated_at ?? $p->created_at;
            })
            ->first();

        $piutangStatus = $latestPiutang ? strtolower(trim((string)($latestPiutang->payment_status ?? ''))) : '';
        $piutangAmount = $latestPiutang ? floatval($latestPiutang->amount ?? 0) : 0;
        $piutangPaid = $latestPiutang ? floatval($latestPiutang->paid_amount ?? 0) : 0;
        $piutangRemaining = max(0, $piutangAmount - $piutangPaid);

        $invoiceRemaining = max(0, $totalAmount - $amountPaid);

        $formatRupiah = function ($value) {
            return 'Rp ' . number_format(floatval($value), 0, ',', '.');
        };

        $settledPiutang = $piutangs->first(function ($piutang) {
            if (!$piutang) return false;
            $status = strtolower(trim((string)($piutang->payment_status ?? '')));
            if (in_array($status, ['paid', 'lunas', 'sudah bayar', 'sudah dibayar'], true)) return true;
            $amount = floatval($piutang->amount ?? 0);
            $paidAmount = floatval($piutang->paid_amount ?? 0);
            return $amount > 0 && $paidAmount >= $amount;
        });

        $isPiutangInvoice = ($invoice->payment_method === 'piutang');
        $isPiutangSettled = $isPiutangInvoice && ($settledPiutang || $isInvoiceFullyPaid);

        if ($isPiutangInvoice) {
            if ($isPiutangSettled) {
                // Use piutang payment method only when a real payment method is recorded
                $piutangForMethod = $piutangs
                    ->filter(function ($p) {
                        return $p && !empty($p->payment_method);
                    })
                    ->sortByDesc(function ($p) {
                        return $p->payment_date ?? $p->updated_at ?? $p->created_at;
                    })
                    ->first();

                if ($piutangForMethod && !empty($piutangForMethod->payment_method)) {
                    $paidMethod = $piutangForMethod->payment_method;
                }

                $notes = 'Lunas via piutang';
            } else {
                // Not settled yet
                if (in_array($piutangStatus, ['unpaid', 'belum bayar', 'belum dibayar', ''], true) && $piutangPaid <= 0) {
                    $notes = 'Piutang belum bayar';
                } else {
                    $remaining = $piutangRemaining > 0 ? $piutangRemaining : $invoiceRemaining;
                    $notes = 'Kekurangan: ' . $formatRupiah($remaining);
                    // If there is a payment method recorded for partial payments, show it
                    if ($latestPiutang && !empty($latestPiutang->payment_method)) {
                        $paidMethod = $latestPiutang->payment_method;
                    }
                }
            }
        } else {
            // Non-piutang invoices
            if (!$isInvoiceFullyPaid && $invoiceRemaining > 0) {
                $notes = 'Kekurangan: ' . $formatRupiah($invoiceRemaining);
            }
        }

        return [$paidMethod, $notes];
    }

    /** Excel column letter of a column key. */
    private static function letter(string $key): string
    {
        $index = array_search($key, array_keys(self::COLUMNS), true);

        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }

    public function bindValue(Cell $cell, $value)
    {
        // Invoice numbers and No RM are text (keep leading zeros)
        $textLetters = array_map([self::class, 'letter'], self::TEXT_COLUMNS);
        if ($value !== null && $cell->getRow() > 1 && in_array($cell->getColumn(), $textLetters, true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach (self::MONEY_COLUMNS as $key) {
            $formats[self::letter($key)] = '#,##0';
        }

        return $formats;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
