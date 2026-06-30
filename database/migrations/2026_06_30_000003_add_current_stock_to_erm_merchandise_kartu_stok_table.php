<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('erm_merchandise_kartu_stok', function (Blueprint $table) {
            $table->integer('current_stock')->default(0)->after('qty');
        });

        $runningStock = [];

        $rows = DB::table('erm_merchandise_kartu_stok')
            ->select(['id', 'merchandise_id', 'type', 'qty'])
            ->orderBy('merchandise_id')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $merchandiseId = (int) $row->merchandise_id;
            $previous = $runningStock[$merchandiseId] ?? 0;
            $next = $row->type === 'in'
                ? $previous + (int) $row->qty
                : max(0, $previous - (int) $row->qty);

            DB::table('erm_merchandise_kartu_stok')
                ->where('id', $row->id)
                ->update(['current_stock' => $next]);

            $runningStock[$merchandiseId] = $next;
        }
    }

    public function down()
    {
        Schema::table('erm_merchandise_kartu_stok', function (Blueprint $table) {
            $table->dropColumn('current_stock');
        });
    }
};