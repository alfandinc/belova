<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TransactionRecorderService
{
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