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