<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off corrections of data written by the old Kos BCL code (old values go to bcl_audit_logs):
 * 1. Old downgrade room changes stored the refund as `harga` (no revenue line), so finance reports
 *    counted it as revenue. Such records owe nothing: harga = 0.
 * 2. Old "refund downgrade to deposit" debited cash (1-10101) as if money came in. The debit side of a
 *    refund kept as deposit is the refund cost (5-10102).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $log = function (string $action, string $subject, string $description, array $data) use ($now) {
            if (Schema::hasTable('bcl_audit_logs')) {
                DB::table('bcl_audit_logs')->insert([
                    'user_id' => null,
                    'action' => $action,
                    'subject' => $subject,
                    'description' => $description,
                    'data' => json_encode($data),
                    'created_at' => $now,
                ]);
            }
        };

        $legacyMoves = DB::table('bcl_tr_renter as t')
            ->where('t.identity', 'Pindah Kamar')
            ->where('t.harga', '>', 0)
            ->whereNotExists(function ($q) {
                $q->from('bcl_fin_jurnal')->whereColumn('bcl_fin_jurnal.doc_id', 't.trans_id')->where('bcl_fin_jurnal.kode_akun', '4-10101');
            })
            ->get(['t.id', 't.trans_id', 't.harga']);
        foreach ($legacyMoves as $trx) {
            DB::table('bcl_tr_renter')->where('id', $trx->id)->update(['harga' => 0]);
            $log('data_fix', $trx->trans_id, 'Koreksi data lama: harga pindah kamar (downgrade) bukan tagihan', ['old_harga' => $trx->harga]);
        }

        $wrongCash = DB::table('bcl_fin_jurnal')
            ->where('identity', 'Topup Deposit (Downgrade)')
            ->where('kode_akun', '1-10101')
            ->where('pos', 'D')
            ->get(['id', 'doc_id', 'debet']);
        foreach ($wrongCash as $line) {
            DB::table('bcl_fin_jurnal')->where('id', $line->id)->update(['kode_akun' => '5-10102', 'identity' => 'Downgrade Kamar']);
            $log('data_fix', $line->doc_id, 'Koreksi data lama: refund downgrade ke deposit bukan kas masuk (1-10101 -> 5-10102)', ['jurnal_id' => $line->id, 'debet' => $line->debet]);
        }
    }

    public function down(): void
    {
        // Data corrections are not reverted automatically; the old values are kept in bcl_audit_logs.
    }
};
