<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transfer Antar Gudang gets its own rejection fields. Until now a rejection stored the user in
 * approved_by / approved_at (and, briefly, the reason in keterangan after "[Ditolak] "); existing
 * rejected rows are moved to the new columns. Stok and kartu stok are not touched.
 */
return new class extends Migration
{
    private const REJECT_PREFIX = '[Ditolak] ';

    public function up(): void
    {
        Schema::table('erm_mutasi_gudang', function (Blueprint $t) {
            $t->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $t->timestamp('rejected_at')->nullable()->after('rejected_by');
            $t->string('rejected_reason', 500)->nullable()->after('rejected_at');
        });

        DB::table('erm_mutasi_gudang')->where('status', 'rejected')->orderBy('id')->each(function ($row) {
            $keterangan = $row->keterangan;
            $reason = null;
            if ($keterangan !== null && ($pos = strpos($keterangan, self::REJECT_PREFIX)) !== false) {
                $reason = trim(substr($keterangan, $pos + strlen(self::REJECT_PREFIX)));
                $keterangan = trim(substr($keterangan, 0, $pos)) ?: null;
            }

            DB::table('erm_mutasi_gudang')->where('id', $row->id)->update([
                'rejected_by' => $row->approved_by,
                'rejected_at' => $row->approved_at,
                'rejected_reason' => $reason !== null ? mb_substr($reason, 0, 500) : null,
                'approved_by' => null,
                'approved_at' => null,
                'keterangan' => $keterangan,
            ]);
        });
    }

    public function down(): void
    {
        // Back to the old layout: the rejecter in approved_by, the reason in keterangan
        DB::table('erm_mutasi_gudang')->where('status', 'rejected')->orderBy('id')->each(function ($row) {
            $keterangan = $row->keterangan;
            if ($row->rejected_reason) {
                $keterangan = trim(($keterangan ? $keterangan . "\n" : '') . self::REJECT_PREFIX . $row->rejected_reason);
            }

            DB::table('erm_mutasi_gudang')->where('id', $row->id)->update([
                'approved_by' => $row->rejected_by,
                'approved_at' => $row->rejected_at,
                'keterangan' => $keterangan,
            ]);
        });

        Schema::table('erm_mutasi_gudang', function (Blueprint $t) {
            $t->dropForeign(['rejected_by']);
            $t->dropColumn(['rejected_by', 'rejected_at', 'rejected_reason']);
        });
    }
};
