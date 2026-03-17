<?php

namespace App\Exports\Finance;

use App\Models\Finance\FinanceTransaction;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, Responsable
{
    use Exportable;

    private $startDate;
    private $endDate;
    private $jenisTransaksi;
    private $metodeBayar;
    private $search;

    public function __construct($startDate = null, $endDate = null, $jenisTransaksi = null, $metodeBayar = null, $search = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->jenisTransaksi = $jenisTransaksi;
        $this->metodeBayar = $metodeBayar;
        $this->search = $search;
    }

    public function query()
    {
        $query = FinanceTransaction::query()
            ->with(['invoice', 'visitation.pasien'])
            ->select('finance_transactions.*');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('tanggal', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
        }

        if (!empty($this->jenisTransaksi)) {
            $query->where('jenis_transaksi', $this->jenisTransaksi);
        }

        if (!empty($this->metodeBayar)) {
            $query->where('metode_bayar', $this->metodeBayar);
        }

        if (!empty($this->search)) {
            $value = trim((string) $this->search);
            $query->where(function ($q) use ($value) {
                $q->where('finance_transactions.visitation_id', 'like', "%{$value}%")
                    ->orWhere('finance_transactions.invoice_id', 'like', "%{$value}%")
                    ->orWhere('finance_transactions.metode_bayar', 'like', "%{$value}%")
                    ->orWhere('finance_transactions.jenis_transaksi', 'like', "%{$value}%")
                    ->orWhere('finance_transactions.deskripsi', 'like', "%{$value}%")
                    ->orWhereHas('visitation.pasien', function ($pq) use ($value) {
                        $pq->where('nama', 'like', "%{$value}%")
                            ->orWhere('id', 'like', "%{$value}%");
                    })
                    ->orWhereHas('invoice', function ($iq) use ($value) {
                        $iq->where('invoice_number', 'like', "%{$value}%");
                    });
            });
        }

        return $query->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Pasien',
            'No Invoice',
            'Jumlah',
            'Jenis Transaksi',
            'Metode Bayar',
            'Deskripsi',
        ];
    }

    public function map($transaction): array
    {
        $pasien = optional(optional($transaction->visitation)->pasien);
        $pasienLabel = $pasien && !empty($pasien->nama) ? $pasien->nama : '-';
        if ($pasien && !empty($pasien->id)) {
            $pasienLabel .= ' (' . $pasien->id . ')';
        }

        return [
            optional($transaction->tanggal)->format('Y-m-d H:i:s'),
            $pasienLabel,
            optional($transaction->invoice)->invoice_number ?? '-',
            (float) ($transaction->jumlah ?? 0),
            strtolower((string) ($transaction->jenis_transaksi ?? 'in')) === 'out' ? 'Out' : 'In',
            $transaction->metode_bayar ?? '-',
            $transaction->deskripsi ?? '-',
        ];
    }
}