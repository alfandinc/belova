<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('erm_pasien_merchandises', 'stock_qty')) {
            Schema::table('erm_pasien_merchandises', function (Blueprint $table) {
                $table->unsignedInteger('stock_qty')->default(0)->after('quantity');
            });
        }

        // Gifts made before the stock card existed never reduced stock, so they stay at 0.
        // The stock card row is written with the gift, so it sits right next to given_at.
        $records = DB::table('erm_pasien_merchandises')
            ->whereNull('cancelled_at')
            ->whereNotNull('given_at')
            ->get(['id', 'merchandise_id', 'quantity', 'given_at']);

        foreach ($records as $record) {
            $givenAt = Carbon::parse($record->given_at);

            $deducted = DB::table('erm_merchandise_kartu_stok')
                ->where('merchandise_id', $record->merchandise_id)
                ->where('type', 'out')
                ->where('notes', 'like', 'Pemberian merchandise ke pasien %')
                ->whereBetween('tanggal', [$givenAt->copy()->subMinute(), $givenAt->copy()->addMinute()])
                ->exists();

            if ($deducted) {
                DB::table('erm_pasien_merchandises')->where('id', $record->id)->update(['stock_qty' => $record->quantity]);
            }
        }
    }

    public function down()
    {
        if (Schema::hasColumn('erm_pasien_merchandises', 'stock_qty')) {
            Schema::table('erm_pasien_merchandises', function (Blueprint $table) {
                $table->dropColumn('stock_qty');
            });
        }
    }
};
