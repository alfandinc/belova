<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TransactionRecorderService
{
    public function recordInvoiceSettlement(
        Invoice $invoice,
        float $jumlahDibayar,
        float $jumlahKembalian,
        ?string $metodeBayar,
        ?string $deskripsiPembayaran = null,
        ?string $deskripsiKembalian = null,
        $tanggal = null
    ): array {
        $transactions = [];

        $paymentTransaction = $this->recordInvoicePayment(
            $invoice,
            $jumlahDibayar,
            $metodeBayar,
            $deskripsiPembayaran,
            $tanggal,
            'in'
        );

        if ($paymentTransaction) {
            $transactions[] = $paymentTransaction;
        }

        if ($jumlahKembalian > 0) {
            $changeTransaction = $this->recordInvoicePayment(
                $invoice,
                $jumlahKembalian,
                $metodeBayar,
                $deskripsiKembalian,
                $tanggal,
                'out'
            );

            if ($changeTransaction) {
                $transactions[] = $changeTransaction;
            }
        }

        return $transactions;
    }

    /**
     * The way money goes back to a patient: the invoice's method, or for piutang / asuransi (which are not ways of
     * paying) the last way money actually came in on this invoice.
     */
    public function refundMethodFor(Invoice $invoice): string
    {
        $isNotPayment = fn ($method) => empty($method) || $method === 'piutang' || str_starts_with((string) $method, 'asuransi_');

        if (!$isNotPayment($invoice->payment_method)) {
            return $invoice->payment_method;
        }

        $lastReceived = FinanceTransaction::where('invoice_id', $invoice->id)
            ->where('jenis_transaksi', 'in')
            ->orderByDesc('id')
            ->pluck('metode_bayar')
            ->first(fn ($method) => !$isNotPayment($method));

        return $lastReceived ?: 'cash';
    }

    public function recordInvoicePayment(
        Invoice $invoice,
        float $jumlah,
        ?string $metodeBayar,
        ?string $deskripsi = null,
        $tanggal = null,
        string $jenisTransaksi = 'in'
    ): ?FinanceTransaction {
        if ($jumlah <= 0) {
            return null;
        }

        return FinanceTransaction::create([
            'tanggal' => $tanggal ? Carbon::parse($tanggal) : now(),
            'visitation_id' => $invoice->visitation_id,
            'invoice_id' => $invoice->id,
            'jumlah' => $jumlah,
            'jenis_transaksi' => $jenisTransaksi,
            'metode_bayar' => $metodeBayar,
            'deskripsi' => $deskripsi,
            'user_id' => Auth::id(),
        ]);
    }
}