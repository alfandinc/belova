<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Something tried to change what a visit is billed for after its payment was processed (paid or piutang).
 * The invoice, its stock and its money stand; changes go through retur.
 */
class InvoiceLockedException extends RuntimeException
{
    public function __construct(string $message = 'Pembayaran kunjungan ini sudah diproses (lunas / piutang), jadi tagihannya tidak dapat diubah. Gunakan retur, atau buat kunjungan baru untuk layanan tambahan.')
    {
        parent::__construct($message);
    }

    public function render()
    {
        return response()->json(['success' => false, 'status' => 'error', 'message' => $this->getMessage()], 423);
    }
}
